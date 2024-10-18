function showAddTaskModal() {
    Swal.fire({
        title: 'Add New Task',
        html:
            '<input id="taskName" class="swal2-input" placeholder="Task Name">' +
            '<select id="taskPriority" class="swal2-input">' +
            '<option value="low">Low</option>' +
            '<option value="medium">Medium</option>' +
            '<option value="high">High</option>' +
            '</select>' +
            '<input id="taskDeadline" class="swal2-input" type="date">',
        focusConfirm: false,
        preConfirm: () => {
            return {
                taskName: document.getElementById('taskName').value,
                priority: document.getElementById('taskPriority').value,
                deadline: document.getElementById('taskDeadline').value
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to add task
            // Update the table after successful addition
        }
    });
}

function editTask(taskId) {
    // Fetch task details and show edit modal
    // Similar to showAddTaskModal, but with pre-filled values
}

function deleteTask(taskId) {
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
            // Send AJAX request to delete task
            // Update the table after successful deletion
        }
    });
}

function submitTask(taskId) {
    $.ajax({
        url: 'submit_task.php',
        method: 'POST',
        data: { id: taskId },
        success: function(response) {
            if (response === 'success') {
                Swal.fire('Success', 'Task submitted successfully', 'success');
                loadTasks(currentPage);
            } else {
                Swal.fire('Error', 'Failed to submit task', 'error');
            }
        }
    });
}

function undoTask(taskId) {
    $.ajax({
        url: 'undo_task.php',
        method: 'POST',
        data: { id: taskId },
        success: function(response) {
            if (response === 'success') {
                Swal.fire('Success', 'Task undone successfully', 'success');
                loadTasks(currentPage);
            } else {
                Swal.fire('Error', 'Failed to undo task', 'error');
            }
        }
    });
}

// Add event listeners and implement AJAX calls for search, filter, and pagination
$(document).ready(function() {
    let currentPage = 1;
    const itemsPerPage = 10;

    function loadTasks(page = 1, search = '', status = '', priority = '', deadline = '') {
        $.ajax({
            url: 'get_user_tasks.php',
            method: 'GET',
            data: {
                page: page,
                search: search,
                status: status,
                priority: priority,
                deadline: deadline
            },
            success: function(response) {
                $('#tasksTableBody').html(response);
                updatePagination(page, search, status, priority, deadline);
            }
        });
    }

    function updatePagination(page, search, status, priority, deadline) {
        $.ajax({
            url: 'get_user_task_count.php',
            method: 'GET',
            data: {
                search: search,
                status: status,
                priority: priority,
                deadline: deadline
            },
            success: function(response) {
                const totalTasks = JSON.parse(response).total_tasks;
                const totalPages = Math.ceil(totalTasks / itemsPerPage);

                $('#prevPage').prop('disabled', page === 1);
                $('#nextPage').prop('disabled', page === totalPages);

                $('#pageInfo').text(`Page ${page} of ${totalPages}`);

                $('#prevPage').data('page', page - 1);
                $('#nextPage').data('page', page + 1);
            }
        });
    }

    // Initial load
    loadTasks();

    // Search form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const search = $('input[name="search"]').val();
        const status = $('select[name="status"]').val();
        const priority = $('select[name="priority"]').val();
        const deadline = $('input[name="deadline"]').val();
        currentPage = 1;
        loadTasks(currentPage, search, status, priority, deadline);
    });

    // Pagination
    $(document).on('click', '#prevPage, #nextPage', function() {
        if (!$(this).prop('disabled')) {
            currentPage = $(this).data('page');
            const search = $('input[name="search"]').val();
            const status = $('select[name="status"]').val();
            const priority = $('select[name="priority"]').val();
            const deadline = $('input[name="deadline"]').val();
            loadTasks(currentPage, search, status, priority, deadline);
        }
    });
});