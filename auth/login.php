<?php
/**
 * Enhanced User Login Page
 * Handles user authentication with session management, enhanced UI, and accessibility
 */

session_start();
require_once '../config/db_connect.php'; // Make sure this path is correct
require_once '../includes/csrf_helper.php'; // Make sure this path is correct

$error = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        // 2. Get and trim inputs
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // 3. Validation
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            try {
                // 4. Fetch user from database
                $user = fetchOne(
                    "SELECT id, username, password_hash FROM users WHERE username = ?",
                    [$username]
                );
                
                // 5. Verify password and authenticate
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Authentication successful
                    session_regenerate_id(true); // Security: regenerate session ID
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Set success message for redirect (flash message)
                    $_SESSION['login_success'] = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
                    
                    // Redirect to main page
                    header('Location: ../index.php');
                    exit();
                } else {
                    // Invalid credentials
                    $error = 'Invalid username or password. Please check your credentials and try again.';
                }
            } catch (Exception $e) {
                // Database or system error
                $error = 'Login failed due to a system error. Please try again in a moment.';
                // Log the detailed error for the admin
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}

// Generate a new CSRF token for the form
$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Auction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Small helper to hide the password toggle's default focus ring */
        #togglePassword:focus {
            outline: none;
            box-shadow: none;
        }
    </style>
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 leading-tight">
                    Welcome Back
                </h1>
                <p class="text-base text-gray-600 max-w-sm mx-auto leading-relaxed">
                    Sign in to your account
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

                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="mb-5">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                Username
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                required
                                autocomplete="username"
                                autofocus
                            >
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    required
                                    autocomplete="current-password"
                                >
                                <button 
                                    type="button" 
                                    id="togglePassword" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 text-gray-500 hover:text-gray-700"
                                    aria-label="Show password"
                                >
                                    <svg id="icon-show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg id="icon-hide" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064 7 9.542 7 1.865 0 3.62.594 5.026 1.582M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.893 4.893l14.214 14.214" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mb-6">
                            <div class="text-sm">
                                <a href="forgot-password.php" class="font-medium text-blue-600 hover:text-blue-800">
                                    Forgot Password?
                                </a>
                            </div>
                        </div>

                        <div>
                            <button 
                                type="submit" 
                                class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 group relative overflow-hidden"
                            >
                                <span class="relative z-10 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    Sign In
                                </span>
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left z-0"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            <div class="mt-8 text-center">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-gradient-to-br from-blue-50 via-white to-purple-50 text-gray-500">
                            Don't have an account?
                        </span>
                    </div>
                </div>
                <div class="mt-4">
                    <a 
                        href="register.php" 
                        class="inline-flex items-center w-full sm:w-auto justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:shadow-md"
                        data-testid="register-link"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Create one here
                    </a>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="../index.php" class="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Auction Site
                </a>
            </div>

        </div> </div> <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const iconShow = document.getElementById('icon-show');
            const iconHide = document.getElementById('icon-hide');

            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function() {
                    // Check the current type of the password input
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle the icon and accessible label
                    if (type === 'text') {
                        iconShow.classList.add('hidden');
                        iconHide.classList.remove('hidden');
                        toggleButton.setAttribute('aria-label', 'Hide password');
                    } else {
                        iconShow.classList.remove('hidden');
                        iconHide.classList.add('hidden');
                        toggleButton.setAttribute('aria-label', 'Show password');
                    }
                });
            }
        });
    </script>
    
</body>
</html>