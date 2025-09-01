<?php
// db handler
require_once 'db.php';

// Insert data into table
function insertDataLeave($table, $data)
{
    global $connLeave;

    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), '?'));
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $connLeave->prepare($sql);
    return $stmt->execute(array_values($data));
}
function insertDataHR($table, $data)
{
    global $connHR;

    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), '?'));
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $connHR->prepare($sql);
    return $stmt->execute(array_values($data));
}


// Update data in a table
function updateDataLeave($table, $data, $where, $whereParams)
{
    global $connLeave;
    $set = implode(", ", array_map(function ($col) {
        return "$col = ?";
    },  array_keys($data)));
    $sql = "UPDATE $table SET $set WHERE $where";
    $stmt = $connLeave->prepare($sql);
    return $stmt->execute(array_merge(array_values($data), $whereParams));
}

function updateDataHR($table, $data, $where, $whereParams)
{
    global $connHR;
    $set = implode(", ", array_map(function ($col) {
        return "$col = ?";
    },  array_keys($data)));
    $sql = "UPDATE $table SET $set WHERE $where";
    $stmt = $connHR->prepare($sql);
    return $stmt->execute(array_merge(array_values($data), $whereParams));
}

// Delete data from a table
function deleteDataLeave($table, $where, $whereParams)
{
    global $connLeave;
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = $connLeave->prepare($sql);
    return $stmt->execute($whereParams);
}


function deleteDataHR($table, $where, $whereParams)
{
    global $connHR;
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = $connHR->prepare($sql);
    return $stmt->execute($whereParams);
}
// Select a data from a table
function selectDataLeave($table, $columns = '*', $where = "", $whereParams = [])
{
    global $connLeave;
    $sql = "SELECT $columns FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    $stmt = $connLeave->prepare($sql);
    $stmt->execute($whereParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function selectDataHR($table, $columns = '*', $where = "", $whereParams = [])
{
    global $connHR;
    $sql = "SELECT $columns FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    $stmt = $connHR->prepare($sql);
    $stmt->execute($whereParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// success message
function printSuccess($message)
{
    echo "<div style='color:green; font-weight: bold;'>$message</div>";
}

// error message
function printError($message)
{
    echo "<div style='color:red; font-weight: bold;'>$message</div>";
}

// get last error
function getLastErrorLeave()
{
    global $connLeave;
    $errorInfo = $connLeave->errorInfo();
    return isset($errorInfo[2]) ? $errorInfo[2] : 'No error information available.';
}

function getLastErrorHR()
{
    global $connHR;
    $errorInfo = $connHR->errorInfo();
    return isset($errorInfo[2]) ? $errorInfo[2] : 'No error information available.';
}


function getLeaveCredits($employeeId, $leaveTypeId)
{
    global $connHR;

    $sql = "SELECT TOP 1 EndBalance FROM tblLeaveLedger WHERE EmployeeId = ? AND LeaveTypeId = ? ORDER BY Date DESC";
    $stmt = $connHR->prepare($sql);
    $stmt->execute([$employeeId, $leaveTypeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['EndBalance'] : 0;
}
