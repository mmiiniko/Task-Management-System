<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

$alert = '';

// Handle task actions (update, delete, add)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                // Update task
                $stmt = $pdo->prepare("UPDATE tasks SET user_id = ?, task_name = ?, status = ?, priority = ?, deadline = ? WHERE id = ?");
                if ($stmt->execute([$_POST['user_id'], $_POST['task_name'], $_POST['status'], $_POST['priority'], $_POST['deadline'], $_POST['task_id']])) {
                    echo json_encode([
                        'type' => 'success',
                        'message' => "Task updated successfully."
                    ]);
                } else {
                    echo json_encode([
                        'type' => 'error',
                        'message' => "Error updating task."
                    ]);
                }
                exit;
            case 'delete':
                // Delete task
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                if ($stmt->execute([$_POST['task_id']])) {
                    echo json_encode([
                        'type' => 'success',
                        'message' => "Task deleted successfully."
                    ]);
                } else {
                    echo json_encode([
                        'type' => 'error',
                        'message' => "Error deleting task."
                    ]);
                }
                exit;
            case 'add':
                // Add new task
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, task_name, status, priority, deadline) VALUES (?, ?, 'pending', ?, ?)");
                if ($stmt->execute([$_POST['user_id'], $_POST['task_name'], $_POST['priority'], $_POST['deadline']])) {
                    echo json_encode([
                        'type' => 'success',
                        'message' => "Task added successfully."
                    ]);
                } else {
                    echo json_encode([
                        'type' => 'error',
                        'message' => "Error adding task."
                    ]);
                }
                exit;
            case 'delete_all':
                $stmt = $pdo->prepare("DELETE FROM tasks");
                if ($stmt->execute()) {
                    echo json_encode([
                        'type' => 'success',
                        'message' => "All tasks have been deleted successfully."
                    ]);
                } else {
                    echo json_encode([
                        'type' => 'error',
                        'message' => "Error deleting all tasks."
                    ]);
                }
                exit;
        }
    }
}

// Move finished tasks to finished_tasks table
$stmt = $pdo->prepare("INSERT INTO finished_tasks (user_id, task_name, description, priority, deadline, completion_date) 
                       SELECT user_id, task_name, description, priority, deadline, NOW() 
                       FROM tasks WHERE status = 'done'");
$stmt->execute();

// Remove finished tasks from tasks table
$stmt = $pdo->prepare("DELETE FROM tasks WHERE status = 'done'");
$stmt->execute();

// Fetch tasks
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tasksPerPage = 9; // Change this line from 10 to 9
$offset = ($page - 1) * $tasksPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort and order parameters
$allowed_sorts = ['user', 'task_name', 'status', 'priority', 'deadline', 'created_at'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort, $allowed_sorts)) {
    $sort = 'created_at';
}
if (!in_array(strtoupper($order), $allowed_orders)) {
    $order = 'DESC';
}

$where = [];
$params = [];

if ($search) {
    $where[] = "(tasks.task_name LIKE ? OR users.first_name LIKE ? OR users.last_name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($status) {
    $where[] = "tasks.status = ?";
    $params[] = $status;
}
if ($priority) {
    $where[] = "tasks.priority = ?";
    $params[] = $priority;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Modify the query to include sorting
$orderBy = $sort === 'user' ? 'users.last_name ' . $order . ', users.first_name ' . $order : 'tasks.' . $sort . ' ' . $order;
$query = "SELECT tasks.*, users.first_name, users.last_name 
          FROM tasks 
          JOIN users ON tasks.user_id = users.id 
          $whereClause 
          ORDER BY $orderBy, tasks.created_at DESC 
          LIMIT $tasksPerPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Count total tasks for pagination
$totalTasksQuery = "SELECT COUNT(*) FROM tasks JOIN users ON tasks.user_id = users.id $whereClause";
$stmt = $pdo->prepare($totalTasksQuery);
$stmt->execute($params);
$totalTasks = $stmt->fetchColumn();
$totalPages = ceil($totalTasks / $tasksPerPage);

// Fetch users for dropdown
$stmt = $pdo->query("SELECT id, first_name, last_name FROM users");
$users = $stmt->fetchAll();

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

// Add this function to generate sorting URLs
function sortUrl($field, $currentSort, $currentOrder) {
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = ($currentSort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Admin Dashboard</title>
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
                            <i class="fas fa-tasks w-5 h-5 mr-2 text-blue-500"></i>
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 text-transparent bg-clip-text">Manage Tasks</span>
                        </h1>
                        <p class="text-sm text-gray-600">
                            <i class="far fa-calendar-alt mr-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </p>
                    </div>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden bg-gray-200">
                <div id="page-content" class="container mx-auto px-6 py-8">
                    <?php if ($alert): ?>
                        <div class="bg-<?php echo $alert['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $alert['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $alert['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo $alert['message']; ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- After the search form -->
                    <div class="mb-6 flex justify-between items-center">
                        <button onclick="openAddTaskModal()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-200 ease-in-out flex items-center">
                            <i class="fas fa-plus-circle mr-2"></i>Add Task
                        </button>
                    </div>

                    <!-- Filter form -->
                    <form action="" method="GET" class="mb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="relative w-64">
                                    <input type="text" name="search" placeholder="Search tasks or users" value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                                <select name="status" class="px-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Status</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in-progress" <?php echo $status === 'in-progress' ? 'selected' : ''; ?>>In-progress</option>
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
                            <button type="button" onclick="confirmDeleteAllTasks()" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-200 ease-in-out flex items-center">
                                <i class="fas fa-trash-alt mr-2"></i>Clear Data
                            </button>
                        </div>
                    </form>

                    <!-- Tasks Table -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo sortUrl('user', $sort, $order); ?>" class="hover:text-gray-700">
                                            User
                                            <?php if ($sort === 'user'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($tasks)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                            No tasks available.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td class="px-6 py-3 whitespace-nowrap text-left"><?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?></td>
                                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                                <span class="capitalize"><?php echo htmlspecialchars($task['task_name']); ?></span>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs leading-5 font-semibold rounded-full text-white w-24 inline-block" style="background-color: <?php echo getStatusColor($task['status']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($task['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs leading-5 font-semibold rounded-full text-white w-24 inline-block" style="background-color: <?php echo getPriorityColor($task['priority']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($task['priority'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                                <span class="<?php echo isDateDue($task['deadline']) ? 'text-red-600 font-semibold' : ''; ?>">
                                                    <?php echo htmlspecialchars($task['deadline']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-center">
                                                <div class="flex justify-center space-x-2">
                                                    <button class="bg-blue-500 hover:bg-blue-600 text-white font-sans font-medium py-2 px-2 rounded-full flex items-center justify-center transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 w-24" onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <span class="text-xs">Edit</span>
                                                    </button>
                                                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white font-sans font-medium py-2 px-2 rounded-full flex items-center justify-center transition duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 w-24" onclick="openDeleteModal(<?php echo $task['id']; ?>)">
                                                        <i class="fas fa-trash-alt mr-1"></i>
                                                        <span class="text-xs">Delete</span>
                                                    </button>
                                                </div>
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
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $tasksPerPage, $totalTasks); ?></span> of <span class="font-medium"><?php echo $totalTasks; ?></span> results
                        </p>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&priority=<?php echo urlencode($priority); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
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

    <!-- Edit Task Modal -->
    <div id="editTaskModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="editTaskForm" method="POST">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-2">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="task_id" id="editTaskId">
                            <div class="mb-4">
                                <label for="editUserId" class="block text-gray-700 text-sm font-bold mb-2">User</label>
                                <select name="user_id" id="editUserId" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4 ">
                                <label for="editTaskName" class="block text-gray-900 text-sm font-bold mb-2">Task Name</label>
                                <input type="text" name="task_name" id="editTaskName" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="mb-4">
                                <label for="editStatus" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                                <select name="status" id="editStatus" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="in-progress">In-progress</option>
                                    <option value="pending">Pending</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="editPriority" class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                                <select name="priority" id="editPriority" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="editDeadline" class="block text-gray-700 text-sm font-bold mb-2">Deadline</label>
                                <input type="date" name="deadline" id="editDeadline" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Update Task
                        </button>
                        <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div id="addTaskModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="addTaskForm" method="POST">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <label for="addUserId" class="block text-gray-700 text-sm font-bold mb-2">User</label>
                            <select name="user_id" id="addUserId" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select user</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="addTaskName" class="block text-gray-700 text-sm font-bold mb-2">Task Name</label>
                            <input type="text" name="task_name" id="addTaskName" placeholder="Enter task name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div class="mb-4">
                            <label for="addPriority" class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                            <select name="priority" id="addPriority" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="addDeadline" class="block text-gray-700 text-sm font-bold mb-2">Deadline</label>
                            <input type="date" name="deadline" id="addDeadline" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Add Task
                        </button>
                        <button type="button" onclick="closeAddTaskModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add this just before the closing </body> tag -->
    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Task</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to delete this task? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function editTask(task) {
            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editUserId').value = task.user_id;
            document.getElementById('editTaskName').value = task.task_name;
            document.getElementById('editStatus').value = task.status;
            document.getElementById('editPriority').value = task.priority;
            document.getElementById('editDeadline').value = task.deadline;
            document.getElementById('editTaskModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editTaskModal').classList.add('hidden');
        }

        document.getElementById('editTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to update this task?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(this);
                    fetch('task_management.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json())
                    .then(data => {
                        closeEditModal();
                        Swal.fire({
                            icon: data.type,
                            title: data.type === 'success' ? 'Success' : 'Error',
                            text: data.message,
                        }).then(() => {
                            if (data.type === 'success') {
                                location.reload();
                            }
                        });
                    });
                }
            });
        });

        <?php if ($alert): ?>
        Swal.fire({
            icon: '<?php echo $alert['type']; ?>',
            title: '<?php echo ucfirst($alert['type']); ?>',
            text: '<?php echo $alert['message']; ?>',
        });
        <?php endif; ?>

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

    // Add these functions to your existing <script> tag
    function openAddTaskModal() {
        document.getElementById('addTaskModal').classList.remove('hidden');
    }

    function closeAddTaskModal() {
        document.getElementById('addTaskModal').classList.add('hidden');
        clearAddTaskForm();
    }

    document.getElementById('addTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');
        fetch('task_management.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
        .then(data => {
            closeAddTaskModal();
            Swal.fire({
                icon: data.type,
                title: data.type === 'success' ? 'Success' : 'Error',
                text: data.message,
            }).then(() => {
                if (data.type === 'success') {
                    location.reload(); // Reload the page to show the new task
                }
            });
        });
    });

    // Add this function to your JavaScript
    function clearAddTaskForm() {
        document.getElementById('addUserId').value = '';
        document.getElementById('addTaskName').value = '';
        document.getElementById('addPriority').value = 'low';
        document.getElementById('addDeadline').value = '';
    }

    // Add these functions to your existing <script> tag
    function openDeleteModal(taskId) {
        document.getElementById('deleteConfirmModal').classList.remove('hidden');
        document.getElementById('confirmDeleteBtn').onclick = function() {
            deleteTask(taskId);
        };
    }

    function closeDeleteModal() {
        document.getElementById('deleteConfirmModal').classList.add('hidden');
    }

    function deleteTask(taskId) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('task_id', taskId);

        fetch('task_management.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
        .then(data => {
            closeDeleteModal();
            if (data.type === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                }).then(() => {
                    location.reload(); // Reload the page to update the task list
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                });
            }
        });
    }

    // Update the existing delete button event listener
    document.querySelectorAll('button[onclick^="return confirm"]').forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const taskId = this.previousElementSibling.value;
            openDeleteModal(taskId);
        };
    });

    // Add this function to your existing <script> tag
    function confirmDeleteAllTasks() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete all tasks. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete all!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteAllTasks();
            }
        });
    }

    function deleteAllTasks() {
        fetch('task_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete_all'
        })
        .then(response => response.json())
        .then(data => {
            if (data.type === 'success') {
                Swal.fire(
                    'Deleted!',
                    data.message,
                    'success'
                ).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire(
                    'Error!',
                    data.message,
                    'error'
                );
            }
        });
    }
    </script>
</body>
</html>