<?php
/**
 * Authentication Helper Functions
 * Provides utility functions for authentication checks and admin role management
 */

// Include database connection
require_once __DIR__ . '/../config/db_connect.php';

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null Username if logged in, null otherwise
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Require authentication - redirect to login if not logged in
 * @param string $redirect_url URL to redirect to after login
 */
function requireAuth($redirect_url = '') {
    if (!isLoggedIn()) {
        $login_url = '/auth/login.php';
        if (!empty($redirect_url)) {
            $login_url .= '?redirect=' . urlencode($redirect_url);
        }
        header("Location: $login_url");
        exit();
    }
}

/**
 * Redirect if already logged in
 * @param string $redirect_url URL to redirect to if logged in
 */
function redirectIfLoggedIn($redirect_url = '/index.php') {
    if (isLoggedIn()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Check if current user is an admin
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $sql = "SELECT role FROM users WHERE id = ?";
        $user = fetchOne($sql, [getCurrentUserId()]);
        return $user && $user['role'] === 'admin';
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current user's role
 * @return string|null User role ('user' or 'admin') if logged in, null otherwise
 */
function getCurrentUserRole() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $sql = "SELECT role FROM users WHERE id = ?";
        $user = fetchOne($sql, [getCurrentUserId()]);
        return $user ? $user['role'] : null;
    } catch (Exception $e) {
        error_log("Error getting user role: " . $e->getMessage());
        return null;
    }
}

/**
 * Require admin privileges - redirect to access denied if not admin
 * @param string $redirect_url URL to redirect to if not admin (default: access denied page)
 */
function requireAdmin($redirect_url = null) {
    requireAuth(); // First ensure user is logged in
    
    if (!isAdmin()) {
        if ($redirect_url === null) {
            // Default redirect to index with access denied message
            $redirect_url = '/index.php?error=access_denied';
        }
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Assign admin role to a user
 * @param int $user_id User ID to assign admin role to
 * @return bool True if successful, false otherwise
 */
function assignAdminRole($user_id) {
    // Only existing admins can assign admin roles
    if (!isAdmin()) {
        return false;
    }
    
    try {
        $sql = "UPDATE users SET role = 'admin' WHERE id = ?";
        $stmt = executeQuery($sql, [$user_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error assigning admin role: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove admin role from a user (set to regular user)
 * @param int $user_id User ID to remove admin role from
 * @return bool True if successful, false otherwise
 */
function removeAdminRole($user_id) {
    // Only existing admins can remove admin roles
    if (!isAdmin()) {
        return false;
    }
    
    // Prevent removing admin role from self
    if ($user_id == getCurrentUserId()) {
        return false;
    }
    
    try {
        $sql = "UPDATE users SET role = 'user' WHERE id = ?";
        $stmt = executeQuery($sql, [$user_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error removing admin role: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all admin users
 * @return array Array of admin user records
 */
function getAllAdmins() {
    if (!isAdmin()) {
        return [];
    }
    
    try {
        $sql = "SELECT id, username, created_at FROM users WHERE role = 'admin' ORDER BY username";
        return fetchAll($sql);
    } catch (Exception $e) {
        error_log("Error getting admin users: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user information by ID
 * @param int $user_id User ID to get information for
 * @return array|false User record or false if not found
 */
function getUserById($user_id) {
    if (!isAdmin()) {
        return false;
    }
    
    try {
        $sql = "SELECT id, username, role, created_at FROM users WHERE id = ?";
        return fetchOne($sql, [$user_id]);
    } catch (Exception $e) {
        error_log("Error getting user by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a user exists and get their role
 * @param string $username Username to check
 * @return array|false User record with role or false if not found
 */
function getUserByUsername($username) {
    if (!isAdmin()) {
        return false;
    }
    
    try {
        $sql = "SELECT id, username, role, created_at FROM users WHERE username = ?";
        return fetchOne($sql, [$username]);
    } catch (Exception $e) {
        error_log("Error getting user by username: " . $e->getMessage());
        return false;
    }
}