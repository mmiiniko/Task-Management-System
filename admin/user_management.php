<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

$alert = '';

// Handle user actions (add, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new user
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, birth_date) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['birth_date']])) {
                    $alert = [
                        'type' => 'success',
                        'message' => "User added successfully."
                    ];
                } else {
                    $alert = [
                        'type' => 'error',
                        'message' => "Error adding user."
                    ];
                }
                break;
            case 'delete':
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$_POST['user_id']])) {
                    $alert = [
                        'type' => 'success',
                        'message' => "User deleted successfully."
                    ];
                } else {
                    $alert = [
                        'type' => 'error',
                        'message' => "Error deleting user."
                    ];
                }
                break;
        }
    }
}

// Fetch users
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Change this to 9 users per page
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

$where = [];
$params = [];

if ($search) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Modify the query to include sorting
$query = "SELECT *, CONCAT(first_name, ' ', last_name) as full_name FROM users $whereClause ORDER BY $sort $order LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Count total users for pagination
$count_query = "SELECT COUNT(*) FROM users $whereClause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Function to generate sort URL
function getSortUrl($field) {
    global $sort, $order, $search;
    $newOrder = ($sort === $field && $order === 'ASC') ? 'DESC' : 'ASC';
    return "?sort=$field&order=$newOrder&search=" . urlencode($search);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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
                <div class="inline-flex items-center justify-center mb-6">
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
                            <i class="fas fa-users w-5 h-5 mr-3 text-blue-500"></i>
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 text-transparent bg-clip-text">Manage Users</span>
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

                    <button id="addUserBtn" class="mb-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        Add User
                    </button>

                    <!-- Search and filter form -->
                    <form action="" method="GET" class="mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="relative w-1/3">
                                <input type="text" name="search" placeholder="Search users" value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Search
                            </button>
                        </div>
                    </form>

                    <!-- Add User Button -->


                    <!-- Users Table -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('full_name'); ?>" class="hover:text-gray-700">
                                            Full Name
                                            <?php if ($sort === 'full_name'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('email'); ?>" class="hover:text-gray-700">
                                            Email
                                            <?php if ($sort === 'email'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="<?php echo getSortUrl('birth_date'); ?>" class="hover:text-gray-700">
                                            Birth Date
                                            <?php if ($sort === 'birth_date'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?> ml-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                            No users available.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['birth_date']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <button type="button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-full w-24 flex items-center justify-center mx-auto" onclick="openDeleteUserModal(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash-alt mr-1"></i>
                                                    <span class="text-xs">Delete</span>
                                                </button>
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
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_users); ?></span> of <span class="font-medium"><?php echo $total_users; ?></span> results
                        </p>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="userForm" method="POST">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add User</h3>
                        <div class="mt-2">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="user_id" id="userId">
                            <div class="mb-4">
                                <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name</label>
                                <input type="text" name="first_name" id="first_name" placeholder="Enter first name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="mb-4">
                                <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name</label>
                                <input type="text" name="last_name" id="last_name" placeholder="Enter last name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="mb-4">
                                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                                <input type="email" name="email" id="email" placeholder="Enter email address" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="mb-4">
                                <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                                <input type="tel" name="phone" id="phone" placeholder="Enter phone number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div class="mb-4">
                                <label for="birth_date" class="block text-gray-700 text-sm font-bold mb-2">Birth Date</label>
                                <input type="date" name="birth_date" id="birth_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="deleteUserModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete User</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to delete this user? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmDeleteUserBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeDeleteUserModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        const userModal = document.getElementById('userModal');
        const userForm = document.getElementById('userForm');
        const addUserBtn = document.getElementById('addUserBtn');

        function openModal(isEdit = false, userData = null) {
            userModal.classList.remove('hidden');
            if (isEdit) {
                document.getElementById('modal-title').textContent = 'Edit User';
                userForm.action.value = 'update';
                userForm.userId.value = userData.id;
                userForm.first_name.value = userData.first_name;
                userForm.last_name.value = userData.last_name;
                userForm.email.value = userData.email;
                userForm.phone.value = userData.phone;
                userForm.birth_date.value = userData.birth_date;
            } else {
                document.getElementById('modal-title').textContent = 'Add New User';
                userForm.action.value = 'add';
                userForm.reset();
            }
        }

        function closeModal() {
            userModal.classList.add('hidden');
        }

        addUserBtn.addEventListener('click', () => openModal());

        // Add confirmation for delete action
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('input[name="action"]').value === 'delete') {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
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

    function openDeleteUserModal(userId) {
        document.getElementById('deleteUserModal').classList.remove('hidden');
        document.getElementById('confirmDeleteUserBtn').onclick = function() {
            deleteUser(userId);
        };
    }

    function closeDeleteUserModal() {
        document.getElementById('deleteUserModal').classList.add('hidden');
    }

    function deleteUser(userId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // Update the existing delete button event listener
    document.querySelectorAll('button[onclick^="return confirm"]').forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const userId = this.previousElementSibling.value;
            openDeleteUserModal(userId);
        };
    });
    </script>
</body>
</html>
