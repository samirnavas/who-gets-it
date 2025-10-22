<?php
/**
 * Create Item Page
 * Protected page for logged-in users to create new auction items
 */

session_start();
require_once 'config/db_connect.php';
require_once 'includes/auth_helper.php';

// Require authentication - redirect to login if not logged in
requireAuth('/create_item.php');

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $starting_bid = $_POST['starting_bid'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    // Validation
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif (empty($description)) {
        $error = 'Description is required.';
    } elseif (empty($starting_bid) || !is_numeric($starting_bid) || $starting_bid <= 0) {
        $error = 'Starting bid must be a positive number.';
    } elseif (empty($end_time)) {
        $error = 'End time is required.';
    } else {
        // Validate end time is in the future
        $end_datetime = new DateTime($end_time);
        $now = new DateTime();
        
        if ($end_datetime <= $now) {
            $error = 'End time must be in the future.';
        } else {
            try {
                // Set default image if none provided
                if (empty($image_url)) {
                    $image_url = 'https://via.placeholder.com/300x200?text=No+Image';
                }
                
                // Insert new item into database
                $sql = "INSERT INTO items (user_id, title, description, image_url, starting_bid, current_bid, end_time) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $params = [
                    getCurrentUserId(),
                    $title,
                    $description,
                    $image_url,
                    $starting_bid,
                    $starting_bid, // current_bid equals starting_bid initially
                    $end_datetime->format('Y-m-d H:i:s')
                ];
                
                executeQuery($sql, $params);
                
                $success = 'Item created successfully! Your auction is now live.';
                
                // Clear form data after successful submission
                $title = $description = $image_url = $starting_bid = $end_time = '';
                
            } catch (Exception $e) {
                $error = 'Failed to create item. Please try again.';
                error_log("Create item error: " . $e->getMessage());
            }
        }
    }
}

// Set page title for header
$page_title = 'Create New Item';
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Create New Auction Item</h1>
            <p class="text-gray-600 mt-2">List your item for auction and start receiving bids</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
                <div class="mt-2">
                    <a href="index.php" class="text-green-800 hover:text-green-900 font-medium underline">
                        View all auctions
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Item Title *
                </label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    value="<?php echo htmlspecialchars($title ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                    maxlength="255"
                    placeholder="Enter a descriptive title for your item"
                >
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Description *
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                    placeholder="Provide a detailed description of your item, including condition, features, etc."
                ><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <div>
                <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">
                    Image URL
                </label>
                <input 
                    type="url" 
                    id="image_url" 
                    name="image_url" 
                    value="<?php echo htmlspecialchars($image_url ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="https://example.com/image.jpg (optional - placeholder will be used if empty)"
                >
                <p class="text-sm text-gray-500 mt-1">
                    Optional: Provide a URL to an image of your item. If left empty, a placeholder image will be used.
                </p>
            </div>

            <div>
                <label for="starting_bid" class="block text-sm font-medium text-gray-700 mb-1">
                    Starting Bid ($) *
                </label>
                <input 
                    type="number" 
                    id="starting_bid" 
                    name="starting_bid" 
                    value="<?php echo htmlspecialchars($starting_bid ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                    min="0.01"
                    step="0.01"
                    placeholder="0.00"
                >
            </div>

            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">
                    Auction End Time *
                </label>
                <input 
                    type="datetime-local" 
                    id="end_time" 
                    name="end_time" 
                    value="<?php echo htmlspecialchars($end_time ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                    min="<?php echo date('Y-m-d\TH:i'); ?>"
                >
                <p class="text-sm text-gray-500 mt-1">
                    Select when you want the auction to end. Must be a future date and time.
                </p>
            </div>

            <div class="flex gap-4">
                <button 
                    type="submit" 
                    class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200"
                >
                    Create Auction Item
                </button>
                
                <a 
                    href="index.php" 
                    class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200 text-center"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="bg-white border-t border-gray-200 mt-12">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-gray-500 text-sm">
            © <?php echo date('Y'); ?> College Auction. All rights reserved.
        </p>
    </div>
</footer>

</body>
</html>