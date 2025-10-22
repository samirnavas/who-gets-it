<?php
/**
 * Admin User Management - Manage user accounts and admin roles
 * Allows admins to view users and assign/remove admin roles
 */

session_start();
require_once '../includes/auth_helper.php';
require_once '../includes/header.php';

// Require admin privileges
requireAdmin();

$message = '';
$error = '';

// Handle role assignment/removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    
    if ($_POST['action'] === 'assign_admin') {
        if (assignAdminRole($user_id)) {
            $message = "Admin role assigned successfully.";
        } else {
            $error = "Failed to assign admin role.";
        }
    } elseif ($_POST['action'] === 'remove_admin') {
        if (removeAdminRole($user_id)) {
            $message = "Admin role removed successfully.";
        } else {
            $error = "Failed to remove admin role.";
        }
    }
}

// Get all users
try {
    $users = fetchAll("
        SELECT id, username, role, created_at,
               (SELECT COUNT(*) FROM bids WHERE user_id = users.id) as total_bids,
               (SELECT COUNT(*) FROM items WHERE user_id = users.id) as total_items
        FROM users 
        ORDER BY role DESC, username ASC
    ");
} catch (Exception $e) {
    $error = "Error loading users: " . $e->getMessage();
    $users = [];
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>User Management</h1>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>All Users</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <p class="text-muted">No users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Total Bids</th>
                                        <th>Total Items</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($user['username']) ?>
                                                <?php if ($user['id'] == getCurrentUserId()): ?>
                                                    <span class="badge badge-info">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $user['role'] === 'admin' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= $user['total_bids'] ?></td>
                                            <td><?= $user['total_items'] ?></td>
                                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                                    <?php if ($user['role'] === 'user'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="action" value="assign_admin">
                                                            <button type="submit" class="btn btn-sm btn-success" 
                                                                    onclick="return confirm('Are you sure you want to assign admin role to <?= htmlspecialchars($user['username']) ?>?')">
                                                                Make Admin
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="action" value="remove_admin">
                                                            <button type="submit" class="btn btn-sm btn-warning" 
                                                                    onclick="return confirm('Are you sure you want to remove admin role from <?= htmlspecialchars($user['username']) ?>?')">
                                                                Remove Admin
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Current User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Admin Users Summary -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Current Administrators</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $admins = array_filter($users, function($user) {
                                return $user['role'] === 'admin';
                            });
                            
                            if (empty($admins)): ?>
                                <p class="text-muted">No administrators found.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($admins as $admin): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($admin['username']) ?>
                                            <?php if ($admin['id'] == getCurrentUserId()): ?>
                                                <span class="badge badge-info">You</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>User Statistics</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><strong>Total Users:</strong> <?= count($users) ?></li>
                                <li><strong>Administrators:</strong> <?= count($admins) ?></li>
                                <li><strong>Regular Users:</strong> <?= count($users) - count($admins) ?></li>
                                <li><strong>Total Bids:</strong> <?= array_sum(array_column($users, 'total_bids')) ?></li>
                                <li><strong>Total Items:</strong> <?= array_sum(array_column($users, 'total_items')) ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>