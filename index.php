<?php
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management System</title>
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
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg">
            <div class="text-center mb-8">
                <div class="logo-container">
                    <img src="assets/images/logo.png" alt="Logo" class="mx-auto">
                </div>
                <h1 class="text-3xl font-bold text-blue-900">Research and Development</h1>
                <h1 class="text-2xl font-bold text-yellow-400 mt-1">Task Management System</h1>
            </div>
            <div class="space-y-4">
                <a href="login.php" class="block w-full bg-yellow-400 hover:bg-yellow-300 text-white font-bold py-3 px-4 rounded-xl focus:outline-none focus:shadow-outline text-lg text-center">
                    Login
                </a>
                <a href="register.php" class="block w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-xl focus:outline-none focus:shadow-outline text-lg text-center">
                    Register
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
