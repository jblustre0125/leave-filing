<?php
if (!isset($_SESSION['user_id']) || !$_SESSION['is_approver']) {
    header('Location: ../../index.php?page=login');
    exit;
}
?>

<div class="text-center">
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
</div>