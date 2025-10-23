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

<div class="container-compact mx-auto">
    <div class="card-compact bg-white">
        <div class="page-header-compact">
            <h1 class="text-2xl font-bold text-gray-900">Create Auction Item</h1>
            <p class="text-gray-600 text-compact">List your item and start receiving bids</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-compact bg-red-100 border border-red-400 text-red-700">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-compact bg-green-100 border border-green-400 text-green-700">
                <?php echo htmlspecialchars($success); ?>
                <div class="mt-2">
                    <a href="index.php" class="text-green-800 hover:text-green-900 font-medium underline text-compact"></a>
                    View all auctions
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-responsive form-compact" data-validate>
            <div class="space-y-compact"></div>
            <div class="form-field">
                <label for="title" class="form-label form-label-required">Item Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>"
                    class="form-input" required maxlength="255" placeholder="Enter item title">
            </div>

            <div class="form-field">
                <label for="description" class="form-label form-label-required">Description</label>
                <textarea id="description" name="description" rows="3" class="form-textarea" required maxlength="1000"
                    placeholder="Describe your item, condition, features, etc."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <div class="form-field">
                <label for="image_url" class="form-label form-label-optional">Image URL</label>
                <input type="url" id="image_url" name="image_url"
                    value="<?php echo htmlspecialchars($image_url ?? ''); ?>" class="form-input"
                    placeholder="https://example.com/image.jpg">
                <div class="form-help-text">
                    Optional: Image URL (placeholder used if empty)
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-compact">
                <div class="form-field">
                    <label for="starting_bid" class="form-label form-label-required">Starting Bid ($)</label>
                    <input type="number" id="starting_bid" name="starting_bid"
                        value="<?php echo htmlspecialchars($starting_bid ?? ''); ?>" class="form-input" required
                        min="0.01" step="0.01" placeholder="0.00">
                </div>

                <div class="form-field">
                    <label for="end_time" class="form-label form-label-required">End Time</label>
                    <input type="datetime-local" id="end_time" name="end_time"
                        value="<?php echo htmlspecialchars($end_time ?? ''); ?>" class="form-input" required
                        min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>

            <div class="form-help-text text-center text-compact-sm">
                End time must be in the future
            </div>

            <div class="flex flex-col sm:flex-row gap-compact pt-2">
                <button type="submit" class="form-button-primary flex-1">
                    Create Auction
                </button>

                <a href="index.php" class="form-button-secondary flex-1 text-center">
                    Cancel
                </a>
            </div>
    </div>
    </form>
</div>
</div>