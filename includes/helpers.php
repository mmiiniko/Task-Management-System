<?php
function getDeadlineClass($deadline) {
    $today = new DateTime();
    $deadlineDate = new DateTime($deadline);
    
    if ($today > $deadlineDate) {
        return 'text-danger';
    }
    
    return '';
}