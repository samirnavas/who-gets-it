<?php
/**
 * My Bids Page - Enhanced bid display interface
 * Shows comprehensive bid history with status indicators and filtering
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';
require_once 'includes/bid_helper.php';

// Require authentication
requireAuth();

$user_id = getCurrentUserId();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Get user bids with pagination and filtering
$bid_data = getUserBids($user_id, $page, $per_page, $status_filter);
$bids = $bid_data['bids'];
$total_count = $bid_data['total_count'];
$total_pages = ceil($total_count / $per_page);

// Set page title for header
$page_title = 'My Bids';

// Include header
include 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">My Bids</h1>
        <p class="text-lg text-gray-600">Track all your auction bids in one place.</p>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <?php
                $filters = [
                    'all' => 'All Bids',
                    'active' => 'Active',
                    'won' => 'Won',
                    'lost' => 'Lost',
                    'stopped' => 'Stopped'
                ];
                
                foreach ($filters as $filter_key => $filter_label):
                    $is_active = $status_filter === $filter_key;
                    $class = $is_active 
                        ? 'border-blue-500 text-blue-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm';
                ?>
                    <a href="?status=<?= htmlspecialchars($filter_key) ?>" class="<?= $class ?>">
                        <?= htmlspecialchars($filter_label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <?php if (empty($bids)): ?>
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No bids found</h3>
            <p class="mt-1 text-sm text-gray-500">
                <?php if ($status_filter === 'all'): ?>
                    You haven't placed any bids yet.
                <?php else: ?>
                    No bids found with status "<?= htmlspecialchars($filters[$status_filter]) ?>".
                <?php endif; ?>
            </p>
            <div class="mt-6">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Browse Auctions
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Bid History Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($bids as $bid): ?>
                    <?php
                    $status_info = $bid['bid_status_display'];
                    $end_time = strtotime($bid['end_time']);
                    $now = time();
                    $time_remaining = $end_time - $now;
                    ?>
                    <li data-bid-id="<?= $bid['id'] ?>">
                        <div class="px-4 py-4 sm:px-6 bid-content">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <!-- Item Image -->
                                    <div class="flex-shrink-0 h-16 w-16">
                                        <?php if (!empty($bid['image_url'])): ?>
                                            <img class="h-16 w-16 rounded-lg object-cover" src="<?= htmlspecialchars($bid['image_url']) ?>" alt="<?= htmlspecialchars($bid['item_title']) ?>">
                                        <?php else: ?>
                                            <div class="h-16 w-16 rounded-lg bg-gray-200 flex items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Bid Details -->
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-blue-600 truncate">
                                                <a href="item.php?id=<?= $bid['item_id'] ?>" class="hover:underline">
                                                    <?= htmlspecialchars($bid['item_title']) ?>
                                                </a>
                                            </p>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <p>
                                                Your bid: <span class="font-medium text-gray-900">$<?= number_format($bid['bid_amount'], 2) ?></span>
                                            </p>
                                            <span class="mx-2">•</span>
                                            <p>
                                                Current bid: <span class="font-medium text-gray-900 current-bid-amount">$<?= number_format($bid['current_bid'], 2) ?></span>
                                            </p>
                                        </div>
                                        <div class="mt-1 flex items-center text-sm text-gray-500">
                                            <p>Placed on <?= date('M j, Y \a\t g:i A', strtotime($bid['created_at'])) ?></p>
                                            <?php if ($bid['status'] === 'stopped' && !empty($bid['stopped_by_username'])): ?>
                                                <span class="mx-2">•</span>
                                                <p>Stopped by admin</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status and Time -->
                                <div class="flex flex-col items-end">
                                    <!-- Status Badge -->
                                    <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_info['class'] ?>">
                                        <?= htmlspecialchars($status_info['message']) ?>
                                    </span>
                                    
                                    <!-- Time Information -->
                                    <div class="mt-2 text-sm text-gray-500 text-right time-remaining">
                                        <?php if ($bid['item_status'] === 'active' && $time_remaining > 0): ?>
                                            <p class="font-medium">
                                                <?php
                                                if ($time_remaining > 86400) {
                                                    echo ceil($time_remaining / 86400) . ' days left';
                                                } elseif ($time_remaining > 3600) {
                                                    echo ceil($time_remaining / 3600) . ' hours left';
                                                } else {
                                                    echo ceil($time_remaining / 60) . ' minutes left';
                                                }
                                                ?>
                                            </p>
                                            <p class="text-xs">Ends <?= date('M j, g:i A', $end_time) ?></p>
                                        <?php elseif ($bid['item_status'] === 'ended' || $bid['item_status'] === 'cancelled' || $time_remaining <= 0): ?>
                                            <p class="font-medium text-gray-600">Auction ended</p>
                                            <p class="text-xs"><?= date('M j, g:i A', $end_time) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Information for Stopped Bids -->
                            <?php if ($bid['status'] === 'stopped'): ?>
                                <div class="mt-3 p-3 bg-red-50 rounded-md">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">Bid Stopped</h3>
                                            <div class="mt-1 text-sm text-red-700">
                                                <p>This bid was stopped by an administrator and is no longer active in the auction.</p>
                                                <?php if (!empty($bid['stopped_at'])): ?>
                                                    <p class="mt-1">Stopped on <?= date('M j, Y \a\t g:i A', strtotime($bid['stopped_at'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $page - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $page + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= ($page - 1) * $per_page + 1 ?></span> to 
                            <span class="font-medium"><?= min($page * $per_page, $total_count) ?></span> of 
                            <span class="font-medium"><?= $total_count ?></span> bids
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php if ($page > 1): ?>
                                <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $is_current = $i === $page;
                                $class = $is_current 
                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium';
                            ?>
                                <?php if ($is_current): ?>
                                    <span class="<?= $class ?>"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $i ?>" class="<?= $class ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?status=<?= urlencode($status_filter) ?>&page=<?= $page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Real-time Status Update JavaScript -->
<script>
class BidStatusUpdater {
    constructor() {
        this.updateInterval = 30000; // 30 seconds
        this.intervalId = null;
        this.isUpdating = false;
        this.currentStatus = '<?= htmlspecialchars($status_filter) ?>';
        this.currentPage = <?= $page ?>;
        this.lastUpdateTime = 0;
        
        this.init();
    }
    
    init() {
        // Start polling if there are active bids
        if (document.querySelectorAll('[data-bid-id]').length > 0) {
            this.startPolling();
        }
        
        // Add visual indicator for real-time updates
        this.addUpdateIndicator();
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }
    
    startPolling() {
        if (this.intervalId) return;
        
        this.intervalId = setInterval(() => {
            this.updateBidStatuses();
        }, this.updateInterval);
        
        // Initial update
        this.updateBidStatuses();
    }
    
    stopPolling() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    async updateBidStatuses() {
        if (this.isUpdating) return;
        
        this.isUpdating = true;
        this.showUpdateIndicator();
        
        try {
            const response = await fetch(`api/bid_status.php?status=${this.currentStatus}&page=${this.currentPage}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateBidElements(data.bids);
                this.lastUpdateTime = data.timestamp;
                this.showUpdateSuccess();
            } else {
                console.error('Failed to update bid statuses:', data.error);
                this.showUpdateError();
            }
        } catch (error) {
            console.error('Error updating bid statuses:', error);
            this.showUpdateError();
        } finally {
            this.isUpdating = false;
            this.hideUpdateIndicator();
        }
    }
    
    updateBidElements(bids) {
        bids.forEach(bid => {
            const bidElement = document.querySelector(`[data-bid-id="${bid.id}"]`);
            if (!bidElement) return;
            
            // Update status badge
            const statusBadge = bidElement.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${bid.status_display.class}`;
                statusBadge.textContent = bid.status_display.message;
            }
            
            // Update current bid amount
            const currentBidElement = bidElement.querySelector('.current-bid-amount');
            if (currentBidElement) {
                currentBidElement.textContent = `$${parseFloat(bid.current_bid).toFixed(2)}`;
            }
            
            // Update time remaining
            const timeElement = bidElement.querySelector('.time-remaining');
            if (timeElement && bid.item_status === 'active') {
                const timeText = this.formatTimeRemaining(bid.time_remaining);
                timeElement.innerHTML = timeText;
            }
            
            // Update stopped bid information
            const stoppedInfo = bidElement.querySelector('.stopped-info');
            if (bid.status === 'stopped' && !stoppedInfo) {
                this.addStoppedBidInfo(bidElement, bid);
            }
            
            // Add visual feedback for status changes
            this.highlightChangedElement(bidElement);
        });
    }
    
    formatTimeRemaining(timeRemaining) {
        if (timeRemaining <= 0) {
            return '<p class="font-medium text-gray-600">Auction ended</p>';
        }
        
        let timeText;
        if (timeRemaining > 86400) {
            timeText = `${Math.ceil(timeRemaining / 86400)} days left`;
        } else if (timeRemaining > 3600) {
            timeText = `${Math.ceil(timeRemaining / 3600)} hours left`;
        } else {
            timeText = `${Math.ceil(timeRemaining / 60)} minutes left`;
        }
        
        const endDate = new Date((Date.now() + timeRemaining * 1000));
        const endDateStr = endDate.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            hour: 'numeric', 
            minute: '2-digit' 
        });
        
        return `<p class="font-medium">${timeText}</p><p class="text-xs">Ends ${endDateStr}</p>`;
    }
    
    addStoppedBidInfo(bidElement, bid) {
        if (!bid.stopped_at) return;
        
        const stoppedDate = new Date(bid.stopped_at).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
        
        const stoppedInfoHtml = `
            <div class="stopped-info mt-3 p-3 bg-red-50 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Bid Stopped</h3>
                        <div class="mt-1 text-sm text-red-700">
                            <p>This bid was stopped by an administrator and is no longer active in the auction.</p>
                            <p class="mt-1">Stopped on ${stoppedDate}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        bidElement.querySelector('.bid-content').insertAdjacentHTML('beforeend', stoppedInfoHtml);
    }
    
    highlightChangedElement(element) {
        element.classList.add('bg-blue-50');
        setTimeout(() => {
            element.classList.remove('bg-blue-50');
        }, 2000);
    }
    
    addUpdateIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'update-indicator';
        indicator.className = 'fixed top-4 right-4 z-50 hidden';
        indicator.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg border p-3 flex items-center space-x-2">
                <div class="update-spinner hidden">
                    <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="update-success hidden">
                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="update-error hidden">
                    <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <span class="text-sm text-gray-700 update-text">Checking for updates...</span>
            </div>
        `;
        document.body.appendChild(indicator);
    }
    
    showUpdateIndicator() {
        const indicator = document.getElementById('update-indicator');
        const spinner = indicator.querySelector('.update-spinner');
        const text = indicator.querySelector('.update-text');
        
        indicator.classList.remove('hidden');
        spinner.classList.remove('hidden');
        text.textContent = 'Checking for updates...';
    }
    
    hideUpdateIndicator() {
        setTimeout(() => {
            const indicator = document.getElementById('update-indicator');
            indicator.classList.add('hidden');
            indicator.querySelector('.update-spinner').classList.add('hidden');
            indicator.querySelector('.update-success').classList.add('hidden');
            indicator.querySelector('.update-error').classList.add('hidden');
        }, 2000);
    }
    
    showUpdateSuccess() {
        const indicator = document.getElementById('update-indicator');
        const spinner = indicator.querySelector('.update-spinner');
        const success = indicator.querySelector('.update-success');
        const text = indicator.querySelector('.update-text');
        
        spinner.classList.add('hidden');
        success.classList.remove('hidden');
        text.textContent = 'Updated';
    }
    
    showUpdateError() {
        const indicator = document.getElementById('update-indicator');
        const spinner = indicator.querySelector('.update-spinner');
        const error = indicator.querySelector('.update-error');
        const text = indicator.querySelector('.update-text');
        
        spinner.classList.add('hidden');
        error.classList.remove('hidden');
        text.textContent = 'Update failed';
    }
}

// Initialize the bid status updater when the page loads
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($bids)): ?>
    new BidStatusUpdater();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>