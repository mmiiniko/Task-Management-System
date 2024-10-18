$(document).ready(function() {
    function loadUsers(page = 1, search = '') {
        $.ajax({
            url: 'get_users.php',
            method: 'GET',
            data: { page: page, search: search },
            success: function(response) {
                $('#usersTableBody').html(response);
            }
        });
    }

    loadUsers();

    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const search = $('input[name="search"]').val();
        loadUsers(1, search);
    });

    $(document).on('click', '#prevPage, #nextPage', function() {
        const page = $(this).data('page');
        const search = $('input[name="search"]').val();
        loadUsers(page, search);
    });
});

function showAddUserModal() {
    Swal.fire({
        title: 'Add New User',
        html:
            '<input id="firstName" class="swal2-input" placeholder="First Name">' +
            '<input id="lastName" class="swal2-input" placeholder="Last Name">' +
            '<input id="email" class="swal2-input" placeholder="Email">' +
            '<input id="phoneNumber" class="swal2-input" placeholder="Phone Number">' +
            '<input id="birthDate" class="swal2-input" type="date">',
        focusConfirm: false,
        preConfirm: () => {
            return {
                firstName: document.getElementById('firstName').value,
                lastName: document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                phoneNumber: document.getElementById('phoneNumber').value,
                birthDate: document.getElementById('birthDate').value
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'add_user.php',
                method: 'POST',
                data: result.value,
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire('Success', 'User added successfully', 'success');
                        loadUsers();
                    } else {
                        Swal.fire('Error', 'Failed to add user', 'error');
                    }
                }
            });
        }
    });
}

function editUser(userId) {
    $.ajax({
        url: 'get_user.php',
        method: 'GET',
        data: { id: userId },
        success: function(user) {
            Swal.fire({
                title: 'Edit User',
                html:
                    `<input id="firstName" class="swal2-input" placeholder="First Name" value="${user.first_name}">` +
                    `<input id="lastName" class="swal2-input" placeholder="Last Name" value="${user.last_name}">` +
                    `<input id="email" class="swal2-input" placeholder="Email" value="${user.email}">` +
                    `<input id="phoneNumber" class="swal2-input" placeholder="Phone Number" value="${user.phone_number}">` +
                    `<input id="birthDate" class="swal2-input" type="date" value="${user.birth_date}">`,
                focusConfirm: false,
                preConfirm: () => {
                    return {
                        id: userId,
                        firstName: document.getElementById('firstName').value,
                        lastName: document.getElementById('lastName').value,
                        email: document.getElementById('email').value,
                        phoneNumber: document.getElementById('phoneNumber').value,
                        birthDate: document.getElementById('birthDate').value
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'update_user.php',
                        method: 'POST',
                        data: result.value,
                        success: function(response) {
                            if (response === 'success') {
                                Swal.fire('Success', 'User updated successfully', 'success');
                                loadUsers();
                            } else {
                                Swal.fire('Error', 'Failed to update user', 'error');
                            }
                        }
                    });
                }
            });
        }
    });
}

function deleteUser(userId) {
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
            $.ajax({
                url: 'delete_user.php',
                method: 'POST',
                data: { id: userId },
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire('Deleted!', 'User has been deleted.', 'success');
                        loadUsers();
                    } else {
                        Swal.fire('Error', 'Failed to delete user', 'error');
                    }
                }
            });
        }
    });
}

function clearAllUsers() {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will delete all users except the admin. You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete all!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'clear_all_users.php',
                method: 'POST',
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire('Deleted!', 'All users have been deleted.', 'success');
                        loadUsers();
                    } else {
                        Swal.fire('Error', 'Failed to delete all users', 'error');
                    }
                }
            });
        }
    });
}