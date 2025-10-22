<?php
/**
 * Input Validation and Sanitization Helper Functions
 * Provides comprehensive input validation and sanitization for security
 */

/**
 * Sanitize string input
 * @param string $input Input to sanitize
 * @param int $max_length Maximum allowed length (0 = no limit)
 * @return string Sanitized string
 */
function sanitizeString($input, $max_length = 0) {
    $sanitized = trim($input);
    $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
    
    if ($max_length > 0 && strlen($sanitized) > $max_length) {
        $sanitized = substr($sanitized, 0, $max_length);
    }
    
    return $sanitized;
}

/**
 * Validate and sanitize integer input
 * @param mixed $input Input to validate
 * @param int $min Minimum allowed value
 * @param int $max Maximum allowed value
 * @return int|false Sanitized integer or false if invalid
 */
function validateInteger($input, $min = 0, $max = PHP_INT_MAX) {
    $value = filter_var($input, FILTER_VALIDATE_INT);
    
    if ($value === false || $value < $min || $value > $max) {
        return false;
    }
    
    return $value;
}

/**
 * Validate and sanitize email input
 * @param string $input Email to validate
 * @return string|false Sanitized email or false if invalid
 */
function validateEmail($input) {
    $email = filter_var(trim($input), FILTER_VALIDATE_EMAIL);
    return $email !== false ? $email : false;
}

/**
 * Validate admin action reason
 * @param string $reason Reason text to validate
 * @param int $max_length Maximum allowed length
 * @return string|false Sanitized reason or false if invalid
 */
function validateAdminReason($reason, $max_length = 500) {
    $reason = trim($reason);
    
    // Allow empty reasons
    if (empty($reason)) {
        return '';
    }
    
    // Check length
    if (strlen($reason) > $max_length) {
        return false;
    }
    
    // Remove potentially dangerous content
    $reason = strip_tags($reason);
    $reason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
    
    return $reason;
}

/**
 * Validate search query
 * @param string $query Search query to validate
 * @param int $max_length Maximum allowed length
 * @return string|false Sanitized query or false if invalid
 */
function validateSearchQuery($query, $max_length = 100) {
    $query = trim($query);
    
    if (empty($query)) {
        return '';
    }
    
    if (strlen($query) > $max_length) {
        return false;
    }
    
    // Remove potentially dangerous characters
    $query = preg_replace('/[<>"\']/', '', $query);
    $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
    
    return $query;
}

/**
 * Validate pagination parameters
 * @param mixed $page Page number
 * @param mixed $per_page Items per page
 * @return array Validated pagination parameters
 */
function validatePagination($page, $per_page = 20) {
    $page = validateInteger($page, 1, 1000);
    if ($page === false) {
        $page = 1;
    }
    
    $per_page = validateInteger($per_page, 1, 100);
    if ($per_page === false) {
        $per_page = 20;
    }
    
    return ['page' => $page, 'per_page' => $per_page];
}

/**
 * Validate status filter
 * @param string $status Status to validate
 * @param array $allowed_statuses Array of allowed status values
 * @return string Validated status or 'all' if invalid
 */
function validateStatusFilter($status, $allowed_statuses = ['all', 'active', 'stopped', 'ended', 'cancelled']) {
    $status = trim($status);
    
    if (in_array($status, $allowed_statuses, true)) {
        return $status;
    }
    
    return 'all';
}

/**
 * Validate and sanitize array of IDs
 * @param array $ids Array of IDs to validate
 * @param int $max_count Maximum number of IDs allowed
 * @return array Array of validated integer IDs
 */
function validateIdArray($ids, $max_count = 100) {
    if (!is_array($ids)) {
        return [];
    }
    
    $validated_ids = [];
    $count = 0;
    
    foreach ($ids as $id) {
        if ($count >= $max_count) {
            break;
        }
        
        $validated_id = validateInteger($id, 1);
        if ($validated_id !== false) {
            $validated_ids[] = $validated_id;
            $count++;
        }
    }
    
    return $validated_ids;
}

/**
 * Sanitize output for HTML display
 * @param string $output Output to sanitize
 * @return string Sanitized output
 */
function sanitizeOutput($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate file upload
 * @param array $file $_FILES array element
 * @param array $allowed_types Array of allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array Validation result with 'valid' boolean and 'error' message
 */
function validateFileUpload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    return ['valid' => true, 'error' => null];
}