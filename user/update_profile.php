<?php
require_once '../includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

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
        // Check if email is already in use by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn()) {
            $error = "Email is already in use by another user.";
        } else {
            // Update user profile
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, birth_date = ? WHERE id = ?");
            if ($stmt->execute([$first_name, $last_name, $email, $phone, $birth_date, $user_id])) {
                $success = "Profile updated successfully.";
                // Update session variables if email or last name changed
                if ($email !== $user['email'] || $last_name !== $user['last_name']) {
                    $_SESSION['username'] = $email;
                    $_SESSION['password'] = $last_name;
                }
                // Set a flag to show the success message after refresh
                $_SESSION['profile_updated'] = true;
                // Redirect to refresh the page
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "An error occurred while updating your profile.";
            }
        }
    }
}

// Check if the profile was just updated
$profile_updated = isset($_SESSION['profile_updated']) && $_SESSION['profile_updated'];
unset($_SESSION['profile_updated']); // Clear the flag
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="bg-gray-100 font-roboto">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="bg-gradient-to-b from-gray-900 to-gray-800 text-white w-64 flex flex-col">
            <div class="p-5">
                <div class="flex items-center justify-center mb-6">
                    <img src="../assets/images/icon.png" alt="Icon" class="w-12 h-12 mr-3">
                    <h2 class="text-2xl font-bold">User Panel</h2>
                </div>
            </div>
            <nav class="flex-1">
                <a href="dashboard.php" class="flex items-center py-3 px-6 hover:bg-gray-700 transition duration-200 ease-in-out <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-700' : ''; ?>">
                    <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                    <span>Dashboard</span>
                </a>
                <a href="task_management.php" class="flex items-center py-3 px-6 hover:bg-gray-700 transition duration-200 ease-in-out <?php echo basename($_SERVER['PHP_SELF']) == 'task_management.php' ? 'bg-gray-700' : ''; ?>">
                    <i class="fas fa-tasks w-5 h-5 mr-3"></i>
                    <span>Manage Tasks</span>
                </a>
                <a href="finished_tasks.php" class="flex items-center py-3 px-6 hover:bg-gray-700 transition duration-200 ease-in-out <?php echo basename($_SERVER['PHP_SELF']) == 'finished_tasks.php' ? 'bg-gray-700' : ''; ?>">
                    <i class="fas fa-check-circle w-5 h-5 mr-3"></i>
                    <span>Finished Tasks</span>
                </a>
                <a href="update_profile.php" class="flex items-center py-3 px-6 hover:bg-gray-700 transition duration-200 ease-in-out <?php echo basename($_SERVER['PHP_SELF']) == 'update_profile.php' ? 'bg-gray-700' : ''; ?>">
                    <i class="fa-solid fa-user-pen w-5 h-5 mr-3"></i>
                    <span>Update Profile</span>
                </a>
            </nav>
            <div class="p-5">
                <a href="#" onclick="confirmLogout()" class="flex items-center justify-center py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 transition duration-200 ease-in-out">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-md">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center">
                        <h1 class="text-3xl font-bold text-gray-800">
                            <i class="fa-solid fa-user-pen w-5 h-5 mr-3 text-blue-500"></i>
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 text-transparent bg-clip-text">Update Profile</span>
                        </h1>
                        <p class="text-sm text-gray-600">
                            <i class="far fa-calendar-alt mr-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </p>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200">
                <div class="container mx-auto px-6 py-8">
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $success; ?></span>
                        </div>
                    <?php endif; ?>
                    <form action="" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 max-w-md mx-auto">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                                First Name
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="first_name" type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                                Last Name
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="last_name" type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                                Phone
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="phone" type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="birth_date">
                                Birth Date
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="birth_date" type="date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date']); ?>" required>
                        </div>
                        <div class="flex items-center justify-between">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex items-center" type="submit">
                                <i class="fas fa-save mr-2"></i>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to update your profile?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        <?php if ($profile_updated): ?>
        Swal.fire({
            icon: 'success',
            title: 'Profile Updated',
            text: 'Your profile has been successfully updated.',
        });
        <?php endif; ?>

        <?php
        if (isset($_SESSION['profile_update'])) {
            $update = $_SESSION['profile_update'];
            echo "Swal.fire({
                icon: '{$update['type']}',
                title: '{$update['title']}',
                text: '{$update['message']}',
            });";
            unset($_SESSION['profile_update']);
        }
        ?>

        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }
    </script>
</body>
</html>
