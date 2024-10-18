<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Fetch dashboard data
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_tasks
FROM tasks");
$dashboard_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch finished tasks count from finished_tasks table
$stmt = $pdo->query("SELECT COUNT(*) as archived_tasks FROM finished_tasks");
$archived_tasks_count = $stmt->fetchColumn();

// Fetch finished tasks count from tasks table (status = 'done')
$stmt = $pdo->query("SELECT COUNT(*) as finished_tasks FROM tasks WHERE status = 'done'");
$finished_tasks_count = $stmt->fetchColumn();

// Calculate total finished tasks
$total_finished_tasks = $archived_tasks_count + $finished_tasks_count;

// Update dashboard data
$dashboard_data['finished_tasks'] = $total_finished_tasks;

// Set all task counts to 0 if there are no tasks
$dashboard_data['finished_tasks'] = $dashboard_data['finished_tasks'] ?? 0;
$dashboard_data['pending_tasks'] = $dashboard_data['pending_tasks'] ?? 0;
$dashboard_data['in_progress_tasks'] = $dashboard_data['in_progress_tasks'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$user_count = $stmt->fetchColumn();

// Fetch data for donut chart
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done
FROM tasks
UNION ALL
SELECT 0 as pending, 0 as in_progress, COUNT(*) as done
FROM finished_tasks");
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine the results
$combined_chart_data = [
    'pending' => $chart_data[0]['pending'],
    'in-progress' => $chart_data[0]['in_progress'],
    'finished' => $chart_data[0]['done'] + $chart_data[1]['done']
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-roboto">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="bg-gradient-to-b from-gray-900 to-gray-800 text-white w-64 flex flex-col">
            <div class="p-5">
                <div class="flex items-center justify-center mb-6">
                    <img src="../assets/images/icon.png" alt="Icon" class="w-12 h-12 mr-3">
                    <h2 class="text-2xl font-bold">Admin Panel</h2>
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
                <a href="user_management.php" class="flex items-center py-3 px-6 hover:bg-gray-700 transition duration-200 ease-in-out <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'bg-gray-700' : ''; ?>">
                    <i class="fas fa-users w-5 h-5 mr-3"></i>
                    <span>Manage Users</span>
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
                            <i class="fas fa-tachometer-alt mr-2 text-blue-500"></i>
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 text-transparent bg-clip-text">Dashboard</span>
                        </h1>
                        <p class="text-sm text-gray-600">
                            <i class="far fa-calendar-alt mr-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </p>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-6 py-8">
                <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
                        <h2 class="text-2xl font-semibold text-gray-800">
                            Hi, Admin!
                        </h2>
                        <p class="text-gray-600 mt-2">Welcome to your dashboard. Here's an overview of your tasks.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-red-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 bg-opacity-75">
                                    <i class="fas fa-clock text-2xl text-red-500"></i>
                                </div>
                                <div class="mx-4">
                                    <h3 class="text-lg font-semibold text-gray-700">Pending Tasks</h3>
                                    <p class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['pending_tasks']; ?></p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Tasks waiting to be started</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-blue-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 bg-opacity-75">
                                    <i class="fas fa-spinner text-2xl text-blue-500"></i>
                                </div>
                                <div class="mx-4">
                                    <h3 class="text-lg font-semibold text-gray-700">In-Progress Tasks</h3>
                                    <p class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['in_progress_tasks']; ?></p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Tasks currently being worked on</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-green-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 bg-opacity-75">
                                    <i class="fas fa-check-circle text-2xl text-green-500"></i>
                                </div>
                                <div class="mx-4">
                                    <h3 class="text-lg font-semibold text-gray-700">Finished Tasks</h3>
                                    <p class="text-3xl font-bold text-gray-800"><?php echo $total_finished_tasks; ?></p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Tasks completed successfully</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-purple-500">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 bg-opacity-75">
                                    <i class="fas fa-users text-2xl text-purple-500"></i>
                                </div>
                                <div class="mx-4">
                                    <h3 class="text-lg font-semibold text-gray-700">Total Users</h3>
                                    <p class="text-3xl font-bold text-gray-800"><?php echo $user_count; ?></p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Registered users in the system</p>
                        </div>
                    </div>
                    <div class="mt-8">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold mb-4 text-gray-700">Task Status Overview</h3>
                            <div style="height: 300px;">
                                <canvas id="taskStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Chart.js code for the donut chart
        const ctx = document.getElementById('taskStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Finished'],
                datasets: [{
                    data: [
                        <?php echo $combined_chart_data['pending']; ?>,
                        <?php echo $combined_chart_data['in-progress']; ?>,
                        <?php echo $combined_chart_data['finished']; ?>
                    ],
                    backgroundColor: [
                        '#DE3548',
                        '#0376FA',
                        '#2AA845',
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                },
            }
        });

        // Add this code for the login success alert
        <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success']): ?>
        Swal.fire({
            icon: 'success',
            title: 'Logged In',
            text: 'You have successfully logged in as an admin.',
        });
        <?php
        // Clear the login success flag
        unset($_SESSION['login_success']);
        endif;
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
