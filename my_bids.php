<?php
/**
 * My Bids Page - Placeholder
 * Will be implemented in future tasks
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';

// Require authentication
requireAuth();

// Set page title for header
$page_title = 'My Bids';

// Include header
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">My Bids</h1>
        <p class="text-lg text-gray-600 mb-8">Track all your auction bids in one place.</p>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <p class="text-blue-800">
                Coming soon: View and manage all your bids here!
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>