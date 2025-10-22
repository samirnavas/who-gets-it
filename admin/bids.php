<?php
/**
 * Admin Bid Management Interface
 * Provides comprehensive bid oversight and control functionality
 */

session_start();
require_once '../includes/auth_helper.php';
require_once '../includes/bid_helper.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/validation_helper.php';
require_once '../includes/audit_helper.php';
require_once '../includes/header.php';

// Require admin privileges
requireAdmin();

// Handle AJAX requests for stopping bids
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Validate CSRF token
    if (!checkCSRFToken()) {
        logSecurityEvent('csrf_validation_failed', 'CSRF token validation failed in admin bids', [
            'action' => $_POST['action'] ?? 'unknown',
            'admin_id' => getCurrentUserId()
        ]);
        echo json_encode(['success' => false, 'error' => 'Security validation failed']);
        exit;
    }
    
    if ($_POST['action'] === 'stop_bid') {
        $bid_id = validateInteger($_POST['bid_id'] ?? 0, 1);
        $reason = validateAdminReason($_POST['reason'] ?? '');
        
        if ($bid_id === false) {
            echo json_encode(['success' => false, 'error' => 'Invalid bid ID']);
            exit;
        }
        
        if ($reason === false) {
            echo json_encode(['success' => false, 'error' => 'Invalid reason format']);
            exit;
        }
        
        // Validate permissions
        $validation = validateAdminActionPermissions('stop_bid', $bid_id);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'error' => $validation['error']]);
            exit;
        }
        
        $success = stopBid($bid_id, $reason);
        
        if ($success) {
            logAdminAction('stop_bid', $bid_id, $reason, [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
        
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($_POST['action'] === 'bulk_stop_bids') {
        $bid_ids = validateIdArray($_POST['bid_ids'] ?? [], 50);
        $reason = validateAdminReason($_POST['reason'] ?? '');
        
        if (empty($bid_ids)) {
            echo json_encode(['success' => false, 'error' => 'No valid bid IDs provided']);
            exit;
        }
        
        if ($reason === false) {
            echo json_encode(['success' => false, 'error' => 'Invalid reason format']);
            exit;
        }
        
        $results = [];
        $successful_count = 0;
        
        foreach ($bid_ids as $bid_id) {
            // Validate permissions for each bid
            $validation = validateAdminActionPermissions('stop_bid', $bid_id);
            if ($validation['valid']) {
                $success = stopBid($bid_id, $reason);
                $results[$bid_id] = $success;
                
                if ($success) {
                    $successful_count++;
                    logAdminAction('stop_bid', $bid_id, $reason, [
                        'bulk_action' => true,
                        'total_bids' => count($bid_ids),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                }
            } else {
                $results[$bid_id] = false;
            }
        }
        
        // Log bulk action summary
        logAdminAction('bulk_stop_bids', 0, $reason, [
            'total_bids' => count($bid_ids),
            'successful_bids' => $successful_count,
            'bid_ids' => $bid_ids,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        echo json_encode(['results' => $results]);
        exit;
    }
}

// Get and validate filter parameters
$pagination = validatePagination($_GET['page'] ?? 1, $_GET['per_page'] ?? 20);
$page = $pagination['page'];
$per_page = $pagination['per_page'];
$status_filter = validateStatusFilter($_GET['status'] ?? 'all', ['all', 'active', 'stopped']);
$search = validateSearchQuery($_GET['search'] ?? '');

// Get bids data
$bids_data = getBidsForAdmin($page, $per_page, $status_filter, $search);
$bids = $bids_data['bids'];
$total_count = $bids_data['total_count'];
$total_pages = ceil($total_count / $per_page);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Bid Management</h1>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status Filter</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Bids</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active Bids</option>
                                <option value="stopped" <?= $status_filter === 'stopped' ? 'selected' : '' ?>>Stopped Bids</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Search by item title or username..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="card mb-4" id="bulk-actions" style="display: none;">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="bulk-reason" class="form-label">Reason for bulk action</label>
                            <input type="text" id="bulk-reason" class="form-control" 
                                   placeholder="Enter reason for stopping selected bids...">
                            <?= getCSRFTokenField() ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="button" class="btn btn-warning" onclick="bulkStopBids()">
                                    Stop Selected Bids
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Results Summary -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-muted">
                        Showing <?= count($bids) ?> of <?= $total_count ?> bids
                        <?php if (!empty($search)): ?>
                            for "<?= htmlspecialchars($search) ?>"
                        <?php endif; ?>
                    </span>
                </div>
                <div>
                    <span id="selected-count" class="text-muted" style="display: none;">
                        <span id="selected-number">0</span> bids selected
                    </span>
                </div>
            </div>
            
            <!-- Bids Table -->
            <?php if (empty($bids)): ?>
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading">No bids found</h4>
                    <p class="mb-0">
                        <?php if (!empty($search)): ?>
                            No bids match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            No bids have been placed yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Bid ID</th>
                                    <th>Item</th>
                                    <th>Bidder</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bids as $bid): ?>
                                    <tr id="bid-row-<?= $bid['id'] ?>">
                                        <td>
                                            <?php if ($bid['status'] === 'active'): ?>
                                                <input type="checkbox" class="form-check-input bid-checkbox" 
                                                       value="<?= $bid['id'] ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>#<?= $bid['id'] ?></strong>
                                        </td>
                                        <td>
                                            <a href="../item.php?id=<?= $bid['item_id'] ?>" target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars($bid['item_title']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($bid['username']) ?></strong>
                                        </td>
                                        <td>
                                            <strong>$<?= number_format($bid['bid_amount'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($bid['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Stopped</span>
                                                <?php if ($bid['stopped_by_username']): ?>
                                                    <br><small class="text-muted">
                                                        by <?= htmlspecialchars($bid['stopped_by_username']) ?>
                                                        <?php if ($bid['stopped_at']): ?>
                                                            <br><?= date('M j, Y g:i A', strtotime($bid['stopped_at'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y g:i A', strtotime($bid['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($bid['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="showStopBidModal(<?= $bid['id'] ?>, '<?= htmlspecialchars($bid['username']) ?>', '<?= htmlspecialchars($bid['item_title']) ?>')">
                                                    Stop Bid
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Already stopped</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Bids pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stop Bid Modal -->
<div class="modal fade" id="stopBidModal" tabindex="-1" aria-labelledby="stopBidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stopBidModalLabel">Stop Bid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to stop this bid?</p>
                <div class="alert alert-info">
                    <strong>Bidder:</strong> <span id="modal-bidder"></span><br>
                    <strong>Item:</strong> <span id="modal-item"></span>
                </div>
                <div class="mb-3">
                    <label for="stop-reason" class="form-label">Reason for stopping (optional)</label>
                    <textarea class="form-control" id="stop-reason" rows="3" 
                              placeholder="Enter reason for stopping this bid..."></textarea>
                </div>
                <?= getCSRFTokenField() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmStopBid()">Stop Bid</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBidId = null;

// Handle select all checkbox
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.bid-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

// Handle individual checkboxes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('bid-checkbox')) {
        updateBulkActions();
        
        // Update select-all checkbox
        const checkboxes = document.querySelectorAll('.bid-checkbox');
        const checkedBoxes = document.querySelectorAll('.bid-checkbox:checked');
        const selectAll = document.getElementById('select-all');
        
        if (checkedBoxes.length === 0) {
            selectAll.indeterminate = false;
            selectAll.checked = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAll.indeterminate = false;
            selectAll.checked = true;
        } else {
            selectAll.indeterminate = true;
            selectAll.checked = false;
        }
    }
});

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.bid-checkbox:checked');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    const selectedNumber = document.getElementById('selected-number');
    
    if (checkedBoxes.length > 0) {
        bulkActions.style.display = 'block';
        selectedCount.style.display = 'inline';
        selectedNumber.textContent = checkedBoxes.length;
    } else {
        bulkActions.style.display = 'none';
        selectedCount.style.display = 'none';
    }
}

function showStopBidModal(bidId, bidder, item) {
    currentBidId = bidId;
    document.getElementById('modal-bidder').textContent = bidder;
    document.getElementById('modal-item').textContent = item;
    document.getElementById('stop-reason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('stopBidModal'));
    modal.show();
}

function confirmStopBid() {
    if (!currentBidId) return;
    
    const reason = document.getElementById('stop-reason').value.trim();
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    fetch('bids.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=stop_bid&bid_id=${currentBidId}&reason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('stopBidModal'));
            modal.hide();
            
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error stopping bid: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error stopping bid. Please try again.');
    });
}

function bulkStopBids() {
    const checkedBoxes = document.querySelectorAll('.bid-checkbox:checked');
    const bidIds = Array.from(checkedBoxes).map(cb => cb.value);
    const reason = document.getElementById('bulk-reason').value.trim();
    const csrfToken = document.querySelector('#bulk-actions input[name="csrf_token"]').value;
    
    if (bidIds.length === 0) {
        alert('Please select at least one bid to stop.');
        return;
    }
    
    if (!confirm(`Are you sure you want to stop ${bidIds.length} selected bids?`)) {
        return;
    }
    
    fetch('bids.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=bulk_stop_bids&bid_ids[]=${bidIds.join('&bid_ids[]=')}&reason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.results) {
            const successful = Object.values(data.results).filter(result => result).length;
            const total = Object.keys(data.results).length;
            
            if (successful === total) {
                alert(`Successfully stopped ${successful} bids.`);
            } else {
                alert(`Stopped ${successful} out of ${total} bids. Some bids may have already been stopped.`);
            }
            
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error performing bulk action. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error performing bulk action. Please try again.');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>