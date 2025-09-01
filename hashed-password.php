<?php
require_once 'config/db-handler.php';

//Fetch all employees
$employees = selectData('Employee', 'EmployeeId, Password');

foreach ($employees as $emp) {
    //Only hash if not already hashed (optional: check if hash looks like bcrypt)
    if (!empty($emp['Password']) && strlen($emp['Password']) < 60) {
        $hashed = password_hash($emp['Password'], PASSWORD_DEFAULT);
        updateData('Employee', ['Password' => $hashed], 'EmployeeId = ?', [$emp['EmployeeId']]);
        echo "EmployeeId {$emp['EmployeeId']} password hashed. <br>";
    }
}
echo "All passwords processed.";
