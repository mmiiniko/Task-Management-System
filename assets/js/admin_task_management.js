function showAddTaskModal() {
    $.ajax({
        url: 'get_users_for_select.php',
        method: 'GET',
        success: function(users) {
            let userOptions = '';
            users.forEach(user => {
                userOptions += `<option value="${user.id}">${user.first_name} ${user.last_name}</option>`;
            });

            Swal.fire({
                title: 'Add New Task',
                html:
                    '<input id="taskName" class="swal2-input" placeholder="Task Name">' +
                    `<select id="taskUser" class="swal2-input">${userOptions}</select>` +
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
                        userId: document.getElementById('taskUser').value,
                        priority: document.getElementById('taskPriority').value,
                        deadline: document.getElementById('taskDeadline').value
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'add_task.php',
                        method: 'POST',
                        data: result.value,
                        success: function(response) {
                            if (response === 'success') {
                                Swal.fire('Success', 'Task added successfully', 'success');
                                loadTasks();
                            } else {
                                Swal.fire('Error', 'Failed to add task', 'error');
                            }
                        }
                    });
                }
            });
        }
    });
}

function editTask(taskId) {
    $.ajax({
        url: 'get_task.php',
        method: 'GET',
        data: { id: taskId },
        success: function(task) {
            $.ajax({
                url: 'get_users_for_select.php',
                method: 'GET',
                success: function(users) {
                    let userOptions = '';
                    users.forEach(user => {
                        userOptions += `<option value="${user.id}" ${user.id == task.user_id ? 'selected' : ''}>${user.first_name} ${user.last_name}</option>`;
                    });

                    Swal.fire({
                        title: 'Edit Task',
                        html:
                            `<input id="taskName" class="swal2-input" placeholder="Task Name" value="${task.task_name}">` +
                            `<select id="taskUser" class="swal2-input">${userOptions}</select>` +
                            `<select id="taskStatus" class="swal2-input">
                                <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                <option value="done" ${task.status === 'done' ? 'selected' : ''}>Done</option>
                            </select>` +
                            `<select id="taskPriority" class="swal2-input">
                                <option value="low" ${task.priority === 'low' ? 'selected' : ''}>Low</option>
                                <option value="medium" ${task.priority === 'medium' ? 'selected' : ''}>Medium</option>
                                <option value="high" ${task.priority === 'high' ? 'selected' : ''}>High</option>
                            </select>` +
                            `<input id="taskDeadline" class="swal2-input" type="date" value="${task.deadline}">`,
                        focusConfirm: false,
                        preConfirm: () => {
                            return {
                                id: taskId,
                                taskName: document.getElementById('taskName').value,
                                userId: document.getElementById('taskUser').value,
                                status: document.getElementById('taskStatus').value,
                                priority: document.getElementById('taskPriority').value,
                                deadline: document.getElementById('taskDeadline').value
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: 'update_task.php',
                                method: 'POST',
                                data: result.value,
                                success: function(response) {
                                    if (response === 'success') {
                                        Swal.fire('Success', 'Task updated successfully', 'success');
                                        loadTasks();
                                    } else {
                                        Swal.fire('Error', 'Failed to update task', 'error');
                                    }
                                }
                            });
                        }
                    });
                }
            });
        }
    });
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

function clearAllTasks() {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will delete all tasks. You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete all!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'clear_all_tasks.php',
                method: 'POST',
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire('Deleted!', 'All tasks have been deleted.', 'success');
                        loadTasks();
                    } else {
                        Swal.fire('Error', 'Failed to delete all tasks', 'error');
                    }
                }
            });
        }
    });
}

// Add event listeners and implement AJAX calls for search, filter, and pagination