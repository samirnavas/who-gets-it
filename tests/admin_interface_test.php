<?php
/**
 * Admin Interface Tests
 * Tests for admin authentication, bid management, auction management, and audit logging
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/bid_helper.php';
require_once __DIR__ . '/../includes/auction_helper.php';

class AdminInterfaceTest {
    private $test_results = [];
    private $test_user_id = null;
    private $test_admin_id = null;
    private $test_item_id = null;
    private $test_bid_id = null;
    
    public function __construct() {
        echo "Starting Admin Interface Tests...\n\n";
        $this->setupTestData();
    }
    
    public function __destruct() {
        $this->cleanupTestData();
    }
    
    /**
     * Setup test data for testing
     */
    private function setupTestData() {
        try {
            // Create test user
            $sql = "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)";
            executeQuery($sql, ['test_user_' . time(), password_hash('password', PASSWORD_DEFAULT), 'user']);
            $this->test_user_id = getDbConnection()->lastInsertId();
            
            // Create test admin
            $sql = "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)";
            executeQuery($sql, ['test_admin_' . time(), password_hash('password', PASSWORD_DEFAULT), 'admin']);
            $this->test_admin_id = getDbConnection()->lastInsertId();
            
            // Create test item
            $sql = "INSERT INTO items (title, description, starting_bid, current_bid, end_time, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            executeQuery($sql, [
                'Test Item for Admin Tests',
                'Test description',
                10.00,
                10.00,
                date('Y-m-d H:i:s', strtotime('+1 day')),
                $this->test_user_id,
                'active'
            ]);
            $this->test_item_id = getDbConnection()->lastInsertId();
            
            // Create test bid
            $sql = "INSERT INTO bids (item_id, user_id, bid_amount, status) VALUES (?, ?, ?, ?)";
            executeQuery($sql, [$this->test_item_id, $this->test_user_id, 15.00, 'active']);
            $this->test_bid_id = getDbConnection()->lastInsertId();
            
            echo "✓ Test data setup complete\n";
        } catch (Exception $e) {
            echo "✗ Test data setup failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Cleanup test data after testing
     */
    private function cleanupTestData() {
        try {
            if ($this->test_bid_id) {
                executeQuery("DELETE FROM bids WHERE id = ?", [$this->test_bid_id]);
            }
            if ($this->test_item_id) {
                executeQuery("DELETE FROM items WHERE id = ?", [$this->test_item_id]);
            }
            if ($this->test_user_id) {
                executeQuery("DELETE FROM users WHERE id = ?", [$this->test_user_id]);
            }
            if ($this->test_admin_id) {
                executeQuery("DELETE FROM users WHERE id = ?", [$this->test_admin_id]);
            }
            executeQuery("DELETE FROM admin_actions WHERE admin_id IN (?, ?)", [$this->test_user_id, $this->test_admin_id]);
            
            echo "\n✓ Test data cleanup complete\n";
        } catch (Exception $e) {
            echo "\n✗ Test data cleanup failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        $this->testAdminAuthentication();
        $this->testAdminAuthorization();
        $this->testBidManagementWorkflow();
        $this->testAuctionManagementWorkflow();
        $this->testAuditLogging();
        
        $this->printResults();
    }
    
    /**
     * Test admin authentication functionality
     */
    public function testAdminAuthentication() {
        echo "Testing Admin Authentication...\n";
        
        // Test 1: isAdmin() function with regular user
        $_SESSION['user_id'] = $this->test_user_id;
        $result = isAdmin();
        $this->addResult('Admin check for regular user', !$result, 'Regular user should not be admin');
        
        // Test 2: isAdmin() function with admin user
        $_SESSION['user_id'] = $this->test_admin_id;
        $result = isAdmin();
        $this->addResult('Admin check for admin user', $result, 'Admin user should be admin');
        
        // Test 3: getCurrentUserRole() function
        $role = getCurrentUserRole();
        $this->addResult('Get admin user role', $role === 'admin', 'Admin user role should be "admin"');
        
        // Test 4: isLoggedIn() function
        $logged_in = isLoggedIn();
        $this->addResult('User logged in check', $logged_in, 'User should be logged in');
        
        // Test 5: getCurrentUserId() function
        $user_id = getCurrentUserId();
        $this->addResult('Get current user ID', $user_id === $this->test_admin_id, 'Should return correct admin user ID');
        
        echo "✓ Admin authentication tests completed\n\n";
    }
    
    /**
     * Test admin authorization functionality
     */
    public function testAdminAuthorization() {
        echo "Testing Admin Authorization...\n";
        
        // Test 1: Admin role assignment
        $_SESSION['user_id'] = $this->test_admin_id;
        $result = assignAdminRole($this->test_user_id);
        $this->addResult('Assign admin role', $result, 'Should successfully assign admin role');
        
        // Verify role assignment
        $user = getUserById($this->test_user_id);
        $this->addResult('Verify admin role assignment', $user && $user['role'] === 'admin', 'User should now have admin role');
        
        // Test 2: Admin role removal
        $result = removeAdminRole($this->test_user_id);
        $this->addResult('Remove admin role', $result, 'Should successfully remove admin role');
        
        // Verify role removal
        $user = getUserById($this->test_user_id);
        $this->addResult('Verify admin role removal', $user && $user['role'] === 'user', 'User should now have user role');
        
        // Test 3: Prevent self-role removal
        $result = removeAdminRole($this->test_admin_id);
        $this->addResult('Prevent self admin role removal', !$result, 'Should not allow admin to remove own role');
        
        // Test 4: Non-admin cannot assign roles
        $_SESSION['user_id'] = $this->test_user_id;
        $result = assignAdminRole($this->test_user_id);
        $this->addResult('Non-admin role assignment prevention', !$result, 'Non-admin should not be able to assign roles');
        
        echo "✓ Admin authorization tests completed\n\n";
    }
    
    /**
     * Test bid management workflow
     */
    public function testBidManagementWorkflow() {
        echo "Testing Bid Management Workflow...\n";
        
        // Set admin session
        $_SESSION['user_id'] = $this->test_admin_id;
        
        // Test 1: Get bids for admin interface
        $bids_data = getBidsForAdmin(1, 10, 'all', '');
        $this->addResult('Get bids for admin', 
            is_array($bids_data) && isset($bids_data['bids']) && isset($bids_data['total_count']), 
            'Should return bids data structure');
        
        // Test 2: Stop bid functionality
        $result = stopBid($this->test_bid_id, 'Test reason for stopping bid');
        $this->addResult('Stop bid', $result, 'Should successfully stop bid');
        
        // Verify bid was stopped
        $bid = fetchOne("SELECT * FROM bids WHERE id = ?", [$this->test_bid_id]);
        $this->addResult('Verify bid stopped', 
            $bid && $bid['status'] === 'stopped' && $bid['stopped_by'] == $this->test_admin_id, 
            'Bid should be marked as stopped with admin ID');
        
        // Test 3: Bid filtering by status
        $active_bids = getBidsForAdmin(1, 10, 'active', '');
        $stopped_bids = getBidsForAdmin(1, 10, 'stopped', '');
        $this->addResult('Bid status filtering', 
            is_array($active_bids) && is_array($stopped_bids), 
            'Should filter bids by status');
        
        // Test 4: Search functionality
        $search_results = getBidsForAdmin(1, 10, 'all', 'Test Item');
        $this->addResult('Bid search functionality', 
            is_array($search_results) && isset($search_results['bids']), 
            'Should return search results');
        
        // Test 5: Attempt to stop already stopped bid
        $result = stopBid($this->test_bid_id, 'Trying to stop again');
        $this->addResult('Stop already stopped bid', !$result, 'Should not allow stopping already stopped bid');
        
        echo "✓ Bid management workflow tests completed\n\n";
    }
    
    /**
     * Test auction management workflow
     */
    public function testAuctionManagementWorkflow() {
        echo "Testing Auction Management Workflow...\n";
        
        // Set admin session
        $_SESSION['user_id'] = $this->test_admin_id;
        
        // Test 1: Get auctions for admin interface
        $auctions_data = getAuctionsForAdmin(1, 10, 'all', '');
        $this->addResult('Get auctions for admin', 
            is_array($auctions_data) && isset($auctions_data['auctions']) && isset($auctions_data['total_count']), 
            'Should return auctions data structure');
        
        // Test 2: Get auction statistics
        $stats = getAuctionStatistics();
        $this->addResult('Get auction statistics', 
            is_array($stats) && isset($stats['auctions_active']), 
            'Should return auction statistics');
        
        // Test 3: End auction functionality
        $result = endAuction($this->test_item_id, 'Test reason for ending auction');
        $this->addResult('End auction', 
            is_array($result) && $result['success'], 
            'Should successfully end auction');
        
        // Verify auction was ended
        $item = fetchOne("SELECT * FROM items WHERE id = ?", [$this->test_item_id]);
        $this->addResult('Verify auction ended', 
            $item && $item['status'] === 'ended' && $item['ended_by'] == $this->test_admin_id, 
            'Auction should be marked as ended with admin ID');
        
        // Test 4: Auction filtering by status
        $active_auctions = getAuctionsForAdmin(1, 10, 'active', '');
        $ended_auctions = getAuctionsForAdmin(1, 10, 'ended', '');
        $this->addResult('Auction status filtering', 
            is_array($active_auctions) && is_array($ended_auctions), 
            'Should filter auctions by status');
        
        // Test 5: Search functionality
        $search_results = getAuctionsForAdmin(1, 10, 'all', 'Test Item');
        $this->addResult('Auction search functionality', 
            is_array($search_results) && isset($search_results['auctions']), 
            'Should return search results');
        
        // Test 6: Attempt to end already ended auction
        $result = endAuction($this->test_item_id, 'Trying to end again');
        $this->addResult('End already ended auction', 
            is_array($result) && !$result['success'], 
            'Should not allow ending already ended auction');
        
        echo "✓ Auction management workflow tests completed\n\n";
    }
    
    /**
     * Test audit logging functionality
     */
    public function testAuditLogging() {
        echo "Testing Audit Logging...\n";
        
        // Set admin session
        $_SESSION['user_id'] = $this->test_admin_id;
        
        // Test 1: Check if admin actions were logged during previous tests
        $admin_actions = fetchAll("SELECT * FROM admin_actions WHERE admin_id = ? ORDER BY created_at DESC", [$this->test_admin_id]);
        $this->addResult('Admin actions logged', 
            count($admin_actions) > 0, 
            'Should have logged admin actions');
        
        // Test 2: Verify stop_bid action was logged
        $stop_bid_actions = fetchAll("SELECT * FROM admin_actions WHERE admin_id = ? AND action_type = 'stop_bid'", [$this->test_admin_id]);
        $this->addResult('Stop bid action logged', 
            count($stop_bid_actions) > 0, 
            'Should have logged stop bid action');
        
        // Test 3: Verify end_auction action was logged
        $end_auction_actions = fetchAll("SELECT * FROM admin_actions WHERE admin_id = ? AND action_type = 'end_auction'", [$this->test_admin_id]);
        $this->addResult('End auction action logged', 
            count($end_auction_actions) > 0, 
            'Should have logged end auction action');
        
        // Test 4: Verify action details are complete
        if (!empty($admin_actions)) {
            $action = $admin_actions[0];
            $this->addResult('Action details complete', 
                isset($action['admin_id']) && isset($action['action_type']) && isset($action['target_id']) && isset($action['created_at']), 
                'Action should have complete details');
        }
        
        // Test 5: Test admin actions retrieval for dashboard
        $recent_actions = fetchAll("
            SELECT aa.*, u.username as admin_username 
            FROM admin_actions aa 
            JOIN users u ON aa.admin_id = u.id 
            ORDER BY aa.created_at DESC 
            LIMIT 10
        ");
        $this->addResult('Recent actions retrieval', 
            is_array($recent_actions), 
            'Should retrieve recent actions with admin usernames');
        
        echo "✓ Audit logging tests completed\n\n";
    }
    
    /**
     * Add test result
     */
    private function addResult($test_name, $passed, $description) {
        $this->test_results[] = [
            'name' => $test_name,
            'passed' => $passed,
            'description' => $description
        ];
        
        $status = $passed ? '✓' : '✗';
        echo "  $status $test_name: $description\n";
    }
    
    /**
     * Print final test results
     */
    private function printResults() {
        $total_tests = count($this->test_results);
        $passed_tests = array_filter($this->test_results, function($result) {
            return $result['passed'];
        });
        $passed_count = count($passed_tests);
        $failed_count = $total_tests - $passed_count;
        
        echo "=== ADMIN INTERFACE TEST RESULTS ===\n";
        echo "Total Tests: $total_tests\n";
        echo "Passed: $passed_count\n";
        echo "Failed: $failed_count\n";
        
        if ($failed_count > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->test_results as $result) {
                if (!$result['passed']) {
                    echo "  ✗ {$result['name']}: {$result['description']}\n";
                }
            }
        }
        
        $success_rate = round(($passed_count / $total_tests) * 100, 1);
        echo "\nSuccess Rate: $success_rate%\n";
        
        if ($success_rate >= 90) {
            echo "🎉 Excellent! Admin interface tests are performing well.\n";
        } elseif ($success_rate >= 75) {
            echo "👍 Good! Most admin interface tests are passing.\n";
        } else {
            echo "⚠️  Warning! Several admin interface tests are failing.\n";
        }
    }
}

// Run the tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $test = new AdminInterfaceTest();
        $test->runAllTests();
    } catch (Exception $e) {
        echo "Test execution failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>