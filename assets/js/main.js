// Add hover effects to sidebar links
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('mouseenter', () => {
        link.classList.add('bg-gray-700');
    });
    link.addEventListener('mouseleave', () => {
        if (!link.classList.contains('bg-gray-700')) {
            link.classList.remove('bg-gray-700');
        }
    });
});

// Function to show SweetAlert notifications
function showNotification(type, message) {
    Swal.fire({
        icon: type,
        title: type.charAt(0).toUpperCase() + type.slice(1),
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
}

// Example usage of SweetAlert (you can call this function when needed)
// showNotification('success', 'Task updated successfully!');
// showNotification('error', 'An error occurred. Please try again.');