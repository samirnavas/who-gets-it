<?php
/**
 * User Registration Page
 * Handles user registration with validation and password hashing
 */

session_start();
require_once '../config/db_connect.php'; // Make sure this path is correct

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif ($password !== $confirm_password) {
        // Kept this check as the "confirm password" field still exists
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if username already exists
            $checkUser = fetchOne(
                "SELECT id FROM users WHERE username = ?", 
                [$username]
            );
            
            if ($checkUser) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Hash password and create user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                executeQuery(
                    "INSERT INTO users (username, password_hash) VALUES (?, ?)",
                    [$username, $password_hash]
                );
                
                $success = 'Registration successful! You can now log in.';
                
                // Redirect to login page after 2 seconds
                header('refresh:2;url=login.php');
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - College Auction</title>
    <meta name="description" content="Join the College Auction platform by creating your account. Quick and secure registration process.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <meta name="theme-color" content="#3B82F6">
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-lg z-50">
        Skip to main content
    </a>

    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-purple-400/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-purple-400/20 to-pink-400/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-6 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            
            <header class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl mb-4 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 leading-tight">
                    Create Your Account
                </h1>
                <p class="text-base text-gray-600 max-w-sm mx-auto leading-relaxed">
                    Join the Auction community
                </p>
            </header>

            <main id="main-content">
                <div class="bg-white/80 backdrop-blur-sm border border-white/20 shadow-xl rounded-2xl p-8">
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center space-x-3" role="alert" aria-live="polite">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-start space-x-3" role="alert" aria-live="polite">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <span><?php echo htmlspecialchars($success); ?></span>
                                <p class="text-sm mt-1 opacity-80">Redirecting to login page...</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <fieldset class="space-y-4">
                            <legend class="sr-only">Account Registration Information</legend>
                            
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                    Username <span class="text-red-500" aria-label="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    required
                                    minlength="3"
                                    maxlength="50"
                                    autocomplete="username"
                                    aria-describedby="username-help"
                                    data-testid="username-input"
                                >
                                <p id="username-help" class="mt-1 text-xs text-gray-500">
                                    Choose a unique username (3-50 characters)
                                </p>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password <span class="text-red-500" aria-label="required">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder=""
                                        required
                                        autocomplete="new-password"
                                        data-testid="password-input"
                                    >
                                    <button 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                        onclick="togglePasswordVisibility('password')"
                                        aria-label="Toggle password visibility"
                                    >
                                        <svg class="w-5 h-5" id="password-eye-closed" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                        </svg>
                                        <svg class="w-5 h-5 hidden" id="password-eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="pb-4"> <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password <span class="text-red-500" aria-label="required">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder=""
                                        required
                                        autocomplete="new-password"
                                        aria-describedby="confirm-password-help"
                                        data-testid="confirm-password-input"
                                    >
                                    <button 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                        onclick="togglePasswordVisibility('confirm_password')"
                                        aria-label="Toggle confirm password visibility"
                                    >
                                        <svg class="w-5 h-5" id="confirm_password-eye-closed" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                        </svg>
                                        <svg class="w-5 h-5 hidden" id="confirm_password-eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p id="confirm-password-help" class="mt-1 text-xs text-gray-500">
                                    Re-enter your password to confirm
                                </p>
                            </div>

                            

                            <div>
                                <button 
                                    type="submit" 
                                    class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 group relative overflow-hidden"
                                    data-testid="submit-button"
                                >
                                    <span class="relative z-10 flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                        Create Account
                                    </span>
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left z-0"></div>
                                </button>
                            </div>
                        </fieldset>
                    </form>
                </div>

                <div class="mt-8 text-center">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 bg-gradient-to-br from-blue-50 via-white to-purple-50 text-gray-500">
                                Already have an account?
                            </span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a 
                            href="login.php" 
                            class="inline-flex items-center w-full sm:w-auto justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:shadow-md"
                            data-testid="login-link"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Sign in to your account
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-4 max-w-sm mx-auto shadow-xl">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 text-base">Creating your account...</span>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeClosed = document.getElementById(fieldId + '-eye-closed');
            const eyeOpen = document.getElementById(fieldId + '-eye-open');
            
            if (field.type === 'password') {
                field.type = 'text';
                eyeClosed.classList.add('hidden');
                eyeOpen.classList.remove('hidden');
            } else {
                field.type = 'password';
                eyeClosed.classList.remove('hidden');
                eyeOpen.classList.add('hidden');
            }
        }

        // Enhanced form submission with loading state (Simplified)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const loadingOverlay = document.getElementById('loading-overlay');
            
            if (form && loadingOverlay) {
                form.addEventListener('submit', function(e) {
                    // Simple check to see if required fields are likely filled
                    const username = document.getElementById('username').value;
                    const pass = document.getElementById('password').value;
                    const confirmPass = document.getElementById('confirm_password').value;

                    // If fields are not empty, show loading.
                    // The server will do the final validation.
                    if (username && pass && confirmPass) {
                        loadingOverlay.classList.remove('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>