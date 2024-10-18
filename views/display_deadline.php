<?php
require_once 'includes/helpers.php';

// Assuming $deadline is the variable containing the deadline date
$deadlineClass = getDeadlineClass($deadline);
?>

<p>Deadline: <span class="<?php echo $deadlineClass; ?>"><?php echo $deadline; ?></span></p>