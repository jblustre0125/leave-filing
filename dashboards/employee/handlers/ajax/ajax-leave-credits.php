<?php
session_start();
require_once __DIR__ . '/config/db-handler.php';

$employeeId = $_SESSION['user_id'] ?? null;
$leaveTypeId = $_GET['LeaveTypeId'] ?? null;

if ($employeeId && $leaveTypeId) {
    $credits = getLeaveCredits($employeeId, $leaveTypeId);
    echo json_encode(['credits' => $credits]);
} else {
    echo json_encode(['credits' => 0]);
}
