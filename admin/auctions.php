<?php
/**
 * Admin Auction Management Interface
 * Provides comprehensive auction oversight and control functionality
 */

session_start();
require_once '../includes/auth_helper.php';
require_once '../includes/auction_helper.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/validation_helper.php';
require_once '../includes/audit_helper.php';
require_once '../includes/header.php';

// Require admin privileges
requireAdmin();

// Handle AJAX requests for auction actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Validate CSRF token
    if (!checkCSRFToken()) {
        logSecurityEvent('csrf_validation_failed', 'CSRF token validation failed in admin auctions', [
            'action' => $_POST['action'] ?? 'unknown',
            'admin_id' => getCurrentUserId()
        ]);
        echo json_encode(['success' => false, 'message' => 'Security validation failed']);
        exit;
    }
    
    if ($_POST['action'] === 'end_auction') {
        $item_id = validateInteger($_POST['item_id'] ?? 0, 1);
        $reason = validateAdminReason($_POST['reason'] ?? '');
        
        if ($item_id === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid auction ID']);
            exit;
        }
        
        if ($reason === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid reason format']);
            exit;
        }
        
        // Validate permissions
        $validation = validateAdminActionPermissions('end_auction', $item_id);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['error']]);
            exit;
        }
        
        $result = endAuction($item_id, $reason);
        
        if ($result['success']) {
            logAdminAction('end_auction', $item_id, $reason, [
                'winner_id' => $result['winner_id'] ?? null,
                'winning_bid' => $result['winning_bid'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
        
        echo json_encode($result);
        exit;
    }
    
    if ($_POST['action'] === 'cancel_auction') {
        $item_id = validateInteger($_POST['item_id'] ?? 0, 1);
        $reason = validateAdminReason($_POST['reason'] ?? '');
        
        if ($item_id === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid auction ID']);
            exit;
        }
        
        if ($reason === false || empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Reason is required for cancelling auctions']);
            exit;
        }
        
        // Validate permissions
        $validation = validateAdminActionPermissions('cancel_auction', $item_id);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['error']]);
            exit;
        }
        
        $result = cancelAuction($item_id, $reason);
        
        if ($result['success']) {
            logAdminAction('cancel_auction', $item_id, $reason, [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
        
        echo json_encode($result);
        exit;
    }
    
    if ($_POST['action'] === 'auto_end_expired') {
        $ended_auctions = autoEndExpiredAuctions();
        
        // Log the auto-end action
        logAdminAction('auto_end_expired', 0, 'Auto-ended expired auctions', [
            'ended_count' => count($ended_auctions),
            'ended_auction_ids' => $ended_auctions,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        echo json_encode(['success' => true, 'ended_count' => count($ended_auctions), 'ended_ids' => $ended_auctions]);
        exit;
    }
}

// Get and validate filter parameters
$pagination = validatePagination($_GET['page'] ?? 1, $_GET['per_page'] ?? 20);
$page = $pagination['page'];
$per_page = $pagination['per_page'];
$status_filter = validateStatusFilter($_GET['status'] ?? 'all', ['all', 'active', 'ended', 'cancelled']);
$search = validateSearchQuery($_GET['search'] ?? '');

// Get auctions data
$auctions_data = getAuctionsForAdmin($page, $per_page, $status_filter, $search);
$auctions = $auctions_data['auctions'];
$total_count = $auctions_data['total_count'];
$total_pages = ceil($total_count / $per_page);

// Get statistics for quick actions
$stats = getAuctionStatistics();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Auction Management</h1>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <!-- Quick Actions -->
            <?php if (isset($stats['auctions_expired']) && $stats['auctions_expired'] > 0): ?>
                <div class="alert alert-warning" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="alert-heading mb-1">Expired Auctions Found</h5>
                            <p class="mb-0"><?= $stats['auctions_expired'] ?> auctions have passed their end time and need to be processed.</p>
                        </div>
                        <button type="button" class="btn btn-warning" onclick="autoEndExpiredAuctions()">
                            Auto-End Expired Auctions
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title"><?= $stats['auctions_active'] ?? 0 ?></h5>
                            <p class="card-text">Active Auctions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title"><?= $stats['auctions_ended'] ?? 0 ?></h5>
                            <p class="card-text">Ended Auctions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-warning">
                        <div class="card-body">
                            <h5 class="card-title"><?= $stats['auctions_ending_soon'] ?? 0 ?></h5>
                            <p class="card-text">Ending Soon (24h)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?= $stats['auctions_expired'] ?? 0 ?></h5>
                            <p class="card-text">Expired</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status Filter</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Auctions</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active Auctions</option>
                                <option value="ended" <?= $status_filter === 'ended' ? 'selected' : '' ?>>Ended Auctions</option>
                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled Auctions</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Search by item title or owner..." value="<?= htmlspecialchars($search) ?>">
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
            
            <!-- Results Summary -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-muted">
                        Showing <?= count($auctions) ?> of <?= $total_count ?> auctions
                        <?php if (!empty($search)): ?>
                            for "<?= htmlspecialchars($search) ?>"
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <!-- Auctions Table -->
            <?php if (empty($auctions)): ?>
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading">No auctions found</h4>
                    <p class="mb-0">
                        <?php if (!empty($search)): ?>
                            No auctions match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            No auctions have been created yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Auction ID</th>
                                    <th>Item</th>
                                    <th>Owner</th>
                                    <th>Current Bid</th>
                                    <th>Bids</th>
                                    <th>Status</th>
                                    <th>End Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auctions as $auction): ?>
                                    <?php
                                    $now = time();
                                    $end_time = strtotime($auction['end_time']);
                                    $is_expired = $end_time <= $now;
                                    $time_remaining = $end_time - $now;
                                    ?>
                                    <tr id="auction-row-<?= $auction['id'] ?>">
                                        <td>
                                            <strong>#<?= $auction['id'] ?></strong>
                                        </td>
                                        <td>
                                            <a href="../item.php?id=<?= $auction['id'] ?>" target="_blank" class="text-decoration-none">
                                                <strong><?= htmlspecialchars($auction['title']) ?></strong>
                                            </a>
                                            <?php if ($auction['image_url']): ?>
                                                <br><small class="text-muted">Has image</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($auction['owner_username']) ?></strong>
                                        </td>
                                        <td>
                                            <strong>$<?= number_format($auction['current_bid'], 2) ?></strong>
                                            <?php if ($auction['winner_username']): ?>
                                                <br><small class="text-success">Winner: <?= htmlspecialchars($auction['winner_username']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?= $auction['active_bid_count'] ?> Active</span>
                                            <?php if ($auction['stopped_bid_count'] > 0): ?>
                                                <br><span class="badge bg-warning"><?= $auction['stopped_bid_count'] ?> Stopped</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($auction['status'] === 'active'): ?>
                                                <?php if ($is_expired): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php elseif ($time_remaining < 3600): ?>
                                                    <span class="badge bg-warning">Ending Soon</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            <?php elseif ($auction['status'] === 'ended'): ?>
                                                <span class="badge bg-secondary">Ended</span>
                                                <?php if ($auction['ended_by_username']): ?>
                                                    <br><small class="text-muted">by <?= htmlspecialchars($auction['ended_by_username']) ?></small>
                                                <?php endif; ?>
                                            <?php elseif ($auction['status'] === 'cancelled'): ?>
                                                <span class="badge bg-dark">Cancelled</span>
                                                <?php if ($auction['ended_by_username']): ?>
                                                    <br><small class="text-muted">by <?= htmlspecialchars($auction['ended_by_username']) ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y g:i A', strtotime($auction['end_time'])) ?></small>
                                            <?php if ($auction['status'] === 'active' && !$is_expired): ?>
                                                <br><small class="text-muted">
                                                    <?php
                                                    if ($time_remaining < 3600) {
                                                        echo floor($time_remaining / 60) . 'm remaining';
                                                    } elseif ($time_remaining < 86400) {
                                                        echo floor($time_remaining / 3600) . 'h remaining';
                                                    } else {
                                                        echo floor($time_remaining / 86400) . 'd remaining';
                                                    }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($auction['status'] === 'active'): ?>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-warning" 
                                                            onclick="showEndAuctionModal(<?= $auction['id'] ?>, '<?= htmlspecialchars($auction['title']) ?>')">
                                                        End Auction
                                                    </button>
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="showCancelAuctionModal(<?= $auction['id'] ?>, '<?= htmlspecialchars($auction['title']) ?>')">
                                                        Cancel Auction
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No actions</span>
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
                    <nav aria-label="Auctions pagination" class="mt-4">
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

<!-- End Auction Modal -->
<div class="modal fade" id="endAuctionModal" tabindex="-1" aria-labelledby="endAuctionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="endAuctionModalLabel">End Auction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to end this auction?</p>
                <div class="alert alert-info">
                    <strong>Item:</strong> <span id="end-modal-item"></span><br>
                    <strong>Action:</strong> The auction will be ended and the highest bidder will be declared the winner.
                </div>
                <div class="mb-3">
                    <label for="end-reason" class="form-label">Reason for ending (optional)</label>
                    <textarea class="form-control" id="end-reason" rows="3" 
                              placeholder="Enter reason for ending this auction..."></textarea>
                </div>
                <?= getCSRFTokenField() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmEndAuction()">End Auction</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Auction Modal -->
<div class="modal fade" id="cancelAuctionModal" tabindex="-1" aria-labelledby="cancelAuctionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelAuctionModalLabel">Cancel Auction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this auction?</p>
                <div class="alert alert-warning">
                    <strong>Item:</strong> <span id="cancel-modal-item"></span><br>
                    <strong>Warning:</strong> This action will cancel the auction and no winner will be declared.
                </div>
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label">Reason for cancelling (required)</label>
                    <textarea class="form-control" id="cancel-reason" rows="3" 
                              placeholder="Enter reason for cancelling this auction..." required></textarea>
                </div>
                <?= getCSRFTokenField() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancelAuction()">Cancel Auction</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAuctionId = null;

function showEndAuctionModal(auctionId, itemTitle) {
    currentAuctionId = auctionId;
    document.getElementById('end-modal-item').textContent = itemTitle;
    document.getElementById('end-reason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('endAuctionModal'));
    modal.show();
}

function showCancelAuctionModal(auctionId, itemTitle) {
    currentAuctionId = auctionId;
    document.getElementById('cancel-modal-item').textContent = itemTitle;
    document.getElementById('cancel-reason').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('cancelAuctionModal'));
    modal.show();
}

function confirmEndAuction() {
    if (!currentAuctionId) return;
    
    const reason = document.getElementById('end-reason').value.trim();
    const csrfToken = document.querySelector('#endAuctionModal input[name="csrf_token"]').value;
    
    fetch('auctions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=end_auction&item_id=${currentAuctionId}&reason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('endAuctionModal'));
            modal.hide();
            
            // Show success message
            alert(data.message);
            
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error ending auction: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error ending auction. Please try again.');
    });
}

function confirmCancelAuction() {
    if (!currentAuctionId) return;
    
    const reason = document.getElementById('cancel-reason').value.trim();
    const csrfToken = document.querySelector('#cancelAuctionModal input[name="csrf_token"]').value;
    
    if (!reason) {
        alert('Please provide a reason for cancelling the auction.');
        return;
    }
    
    fetch('auctions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=cancel_auction&item_id=${currentAuctionId}&reason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelAuctionModal'));
            modal.hide();
            
            // Show success message
            alert(data.message);
            
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error cancelling auction: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error cancelling auction. Please try again.');
    });
}

function autoEndExpiredAuctions() {
    if (!confirm('Are you sure you want to auto-end all expired auctions? This action cannot be undone.')) {
        return;
    }
    
    // Generate a CSRF token for this action
    const csrfToken = '<?= generateCSRFToken() ?>';
    
    fetch('auctions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=auto_end_expired&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.ended_count > 0) {
                alert(`Successfully ended ${data.ended_count} expired auctions.`);
                window.location.reload();
            } else {
                alert('No expired auctions found to end.');
            }
        } else {
            alert('Error auto-ending auctions. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error auto-ending auctions. Please try again.');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>