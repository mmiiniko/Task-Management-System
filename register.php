<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $birth_date = sanitizeInput($_POST['birth_date']);

    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($birth_date)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $error = "Email already in use.";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, birth_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$first_name, $last_name, $email, $phone, $birth_date])) {
                // Set a session variable to indicate successful registration
                $_SESSION['registration_success'] = true;
                // Redirect to login page
                redirect('login.php');
            } else {
                $error = "An error occurred during registration.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .logo-container {
            width: 200px;
            height: 200px;
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
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl">
            <div class="text-center mb-8">
                <div class="logo-container">
                    <img src="assets/images/logo.png" alt="Logo" class="mx-auto">
                </div>
                <h1 class="text-3xl font-bold text-blue-900">Research and Development</h1>
                <h1 class="text-2xl font-bold text-yellow-400 mt-1">Task Management System</h1>
            </div>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="flex space-x-4 mb-4">
                    <div class="w-1/2">
                        <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="w-1/2">
                        <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name</label>
                        <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-6">
                    <label for="birth_date" class="block text-gray-700 text-sm font-bold mb-2">Birth Date</label>
                    <input type="date" id="birth_date" name="birth_date" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="flex items-center justify-center">
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-white font-bold w-full py-3 px-4 rounded-xl focus:outline-none focus:shadow-outline text-lg">
                        Register
                    </button>
                </div>
            </form>
            <div class="text-center mt-6">
                <p class="text-sm">Already have an account? <a href="login.php" class="text-blue-500 hover:text-blue-700">Login here</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
