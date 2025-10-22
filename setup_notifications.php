<?php
/**
 * Web-accessible setup script for notifications system
 * Run this once to add the notifications table
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';

// Only allow admins to run this
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied. Admin privileges required.');
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_notifications'])) {
    try {
        // Read and execute the notifications table SQL
        $sql = file_get_contents('sql/notifications_table.sql');
        
        if ($sql === false) {
            throw new Exception("Could not read notifications_table.sql file");
        }
        
        // Execute the SQL
        $pdo = getDbConnection();
        $pdo->exec($sql);
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Check if table already exists
$table_exists = false;
try {
    $pdo = getDbConnection();
    $result = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $table_exists = $result->rowCount() > 0;
} catch (Exception $e) {
    // Ignore error
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Notifications System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Setup Notifications System</h1>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-800">Notifications system setup completed successfully!</p>
                </div>
            </div>
            <a href="index.php" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 text-center block">
                Return to Home
            </a>
        <?php elseif (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-red-800">Error: <?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($table_exists): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <p class="text-blue-800">Notifications table already exists. No setup needed.</p>
            </div>
            <a href="index.php" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 text-center block">
                Return to Home
            </a>
        <?php else: ?>
            <div class="mb-4">
                <p class="text-gray-600 mb-4">This will create the notifications table in your database to enable the notification system.</p>
                
                <form method="POST">
                    <button type="submit" name="setup_notifications" 
                            class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                        Setup Notifications System
                    </button>
                </form>
            </div>
            
            <a href="admin/index.php" class="text-blue-600 hover:text-blue-800 text-sm text-center block">
                Back to Admin Panel
            </a>
        <?php endif; ?>
    </div>
</body>
</html>