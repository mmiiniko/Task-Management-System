<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);

    // Check if it's an admin login
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE LOWER(username) = LOWER(?) AND password = ?");
    $stmt->execute([$username, $password]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['is_admin'] = true;
        $_SESSION['login_success'] = true;
        redirect(ADMIN_URL . '/dashboard.php');
    } else {
        // Check if it's a user login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND LOWER(last_name) = LOWER(?)");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = false;
            $_SESSION['login_success'] = true;
            redirect(USER_URL . '/dashboard.php');
        } else {
            $error = "Invalid username or password";
        }
    }
}

// Check if there's a successful registration message
$registration_success = isset($_SESSION['registration_success']) && $_SESSION['registration_success'];
unset($_SESSION['registration_success']); // Clear the session variable
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .logo-container {
            width: 200px;  /* Increased from 150px */
            height: 200px; /* Increased from 150px */
            margin: 0 auto;
        }
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gray-100 font-roboto">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg"> <!-- Increased max-width -->
            <div class="text-center mb-8"> <!-- Increased bottom margin -->
                <div class="logo-container">
                    <img src="assets/images/logo.png" alt="Logo" class="mx-auto"> <!-- Changed to icon.png -->
                </div>
                <h1 class="text-3xl font-bold text-blue-900">Research and Development</h1> <!-- Increased font size and top margin -->
                <h1 class="text-2xl font-bold text-yellow-400 mt-1">Task Management System</h1> <!-- Increased font size -->
            </div>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="mb-6"> <!-- Increased bottom margin -->
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" id="username" placeholder="Enter your username" name="username" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-8"> <!-- Increased bottom margin -->
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="password" placeholder="Enter your password" name="password" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="flex items-center justify-center">
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-white font-bold w-full py-3 px-4 rounded-xl focus:outline-none focus:shadow-outline text-lg"> <!-- Increased padding and font size -->
                        Sign In
                    </button>
                </div>
            </form>
            <div class="text-center mt-6"> <!-- Increased top margin -->
                <p class="text-sm">Don't have an account? <a href="register.php" class="text-blue-500 hover:text-blue-700">Register here</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
    <script>
        <?php if (isset($_SESSION['logout_success']) && $_SESSION['logout_success']): ?>
        Swal.fire({
            icon: 'success',
            title: 'Logged Out',
            text: 'You have been successfully logged out.',
        });
        <?php
        // Clear the logout success flag
        unset($_SESSION['logout_success']);
        endif;
        ?>

        <?php if ($registration_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Registration Successful',
            text: 'You can now log in with your email and last name.',
        });
        <?php endif; ?>
    </script>
</body>
</html>
