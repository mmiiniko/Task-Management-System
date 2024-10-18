<?php
require_once '../includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Add this near the top of the file, after the session_start() call
if (!isset($_SESSION['user_id'])) {
    die("User not logged in or session expired. Please log in again.");
}
echo "<!-- Debug: User ID: " . $_SESSION['user_id'] . " -->\n";

// Handle task actions (submit, undo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit':
                $stmt = $pdo->prepare("UPDATE tasks SET status = 'in-progress' WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['task_id'], $_SESSION['user_id']]);
                break;
            case 'undo':
                $stmt = $pdo->prepare("UPDATE tasks SET status = 'pending' WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['task_id'], $_SESSION['user_id']]);
                break;
        }
        // Redirect to refresh the page after action
        redirect($_SERVER['PHP_SELF']);
    }
}

// Move finished tasks to finished_tasks table
$stmt = $pdo->prepare("INSERT INTO finished_tasks (user_id, task_name, description, priority, deadline, completion_date) 
                       SELECT user_id, task_name, description, priority, deadline, NOW() 
                       FROM tasks WHERE status = 'done' AND user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

// Remove finished tasks from tasks table
$stmt = $pdo->prepare("DELETE FROM tasks WHERE status = 'done' AND user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

// Fetch tasks
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tasksPerPage = 9; // Change this to 9 tasks per page
$offset = ($page - 1) * $tasksPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

$where = ["user_id = ?"];
$params = [$_SESSION['user_id']];

if ($search) {
    $where[] = "task_name LIKE ?";
    $params[] = "%$search%";
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($priority) {
    $where[] = "priority = ?";
    $params[] = $priority;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Add these lines after the $priority variable
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort and order parameters
$allowed_sorts = ['task_name', 'status', 'priority', 'deadline', 'created_at', 'completion_date'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort, $allowed_sorts)) {
    $sort = 'created_at';
}
if (!in_array(strtoupper($order), $allowed_orders)) {
    $order = 'DESC';
}

// Modify the query to include sorting
$query = "SELECT * FROM tasks $whereClause ORDER BY $sort $order LIMIT $tasksPerPage OFFSET $offset";

// Add this code after executing the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Debug output
echo "<!-- Debug: Query: " . $query . " -->\n";
echo "<!-- Debug: Params: " . print_r($params, true) . " -->\n";
echo "<!-- Debug: Number of tasks: " . count($tasks) . " -->\n";

// ... (rest of the PHP code remains unchanged)

// Add this function to generate sorting URLs
function sortUrl($field, $currentSort, $currentOrder) {
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = ($currentSort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query($params);
}

// Count total tasks for pagination
$count_query = "SELECT COUNT(*) FROM tasks $whereClause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_tasks = $stmt->fetchColumn();
$total_pages = ceil($total_tasks / $tasksPerPage);

// Function to get status background color
function getStatusColor($status) {
    switch ($status) {
        case 'pending':
            return '#DE3548';
        case 'in-progress':
            return '#0376FA';
        case 'done':
            return '#2AA845';
        default:
            return '#6B7280';
    }
}

// Function to get priority background color
function getPriorityColor($priority) {
    switch ($priority) {
        case 'low':
            return '#2AA845';
        case 'medium':
            return '#F4BC1D';
        case 'high':
            return '#DE3548';
        default:
            return '#6B7280';
    }
}

// Function to check if a date is due
function isDateDue($date) {
    return strtotime($date) < strtotime('today');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - User Dashboard</title>
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
                            <i class="fas fa-tasks w-5 h-5 mr-3 text-blue-500"></i>
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 text-transparent bg-clip-text">Manage Tasks</span>
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
                    <!-- Search and filter form -->
                    <form action="" method="GET" class="mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="relative w-1/3">
                                <input type="text" name="search" placeholder="Search tasks" value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                            <select name="status" class="px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in-progress" <?php echo $status === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                            </select>
                            <select name="priority" class="px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Priorities</option>
                                <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                            </select>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Search
                            </button>
                        </div>
                    </form>

                    <!-- Tasks Table -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo sortUrl('task_name', $sort, $order); ?>" class="hover:text-gray-700">
                                            Task Name
                                            <?php if ($sort === 'task_name'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo sortUrl('status', $sort, $order); ?>" class="hover:text-gray-700">
                                            Status
                                            <?php if ($sort === 'status'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo sortUrl('priority', $sort, $order); ?>" class="hover:text-gray-700">
                                            Priority
                                            <?php if ($sort === 'priority'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo sortUrl('deadline', $sort, $order); ?>" class="hover:text-gray-700">
                                            Deadline
                                            <?php if ($sort === 'deadline'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($tasks)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                            No tasks available.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                                <span class="capitalize"><?php echo htmlspecialchars($task['task_name']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs leading-5 font-semibold rounded-full text-white w-24 inline-block" style="background-color: <?php echo getStatusColor($task['status']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs leading-5 font-semibold rounded-full text-white w-24 inline-block" style="background-color: <?php echo getPriorityColor($task['priority']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($task['priority'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="<?php echo isDateDue($task['deadline']) ? 'text-red-600 font-semibold' : ''; ?>">
                                                    <?php echo htmlspecialchars($task['deadline']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php if ($task['status'] === 'pending'): ?>
                                                    <button onclick="submitTask(<?php echo $task['id']; ?>)" class="bg-green-500 hover:bg-green-600 text-white font-sans font-medium py-1 px-2 rounded-full flex items-center justify-center transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 mx-auto w-24">
                                                        <i class="fas fa-check mr-1"></i>
                                                        <span class="text-xs">Submit</span>
                                                    </button>
                                                <?php elseif ($task['status'] === 'in-progress'): ?>
                                                    <button onclick="undoTask(<?php echo $task['id']; ?>)" class="bg-yellow-500 hover:bg-yellow-600 text-white font-sans font-medium py-1 px-2 rounded-full flex items-center justify-center transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50 mx-auto w-24">
                                                        <i class="fas fa-undo mr-1"></i>
                                                        <span class="text-xs">Undo</span>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex justify-between items-center">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $tasksPerPage, $total_tasks); ?></span> of <span class="font-medium"><?php echo $total_tasks; ?></span> results
                        </p>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php
        if (isset($_SESSION['task_action'])) {
            $action = $_SESSION['task_action'];
            echo "Swal.fire({
                icon: '{$action['type']}',
                title: '{$action['title']}',
                text: '{$action['message']}',
            });";
            unset($_SESSION['task_action']);
        }
        ?>

        // Add confirmation for submit and undo actions
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const action = this.querySelector('input[name="action"]').value;
                const title = action === 'submit' ? 'Submit Task' : 'Undo Task';
                const text = action === 'submit' ? 'Are you sure you want to submit this task?' : 'Are you sure you want to undo this task?';
                
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, ' + action + ' it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>

    <script src="../assets/js/main.js"></script>
    <script>
        function submitTask(taskId) {
            Swal.fire({
                title: 'Submit Task',
                text: "Are you sure you want to submit this task?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="task_id" value="${taskId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function undoTask(taskId) {
            Swal.fire({
                title: 'Undo Task',
                text: "Are you sure you want to undo this task?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, undo it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="undo">
                        <input type="hidden" name="task_id" value="${taskId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>