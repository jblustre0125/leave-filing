<?php
// database connection
$host1 = 'NBCP-LT-144\SQLEXPRESS';
$host2 = '192.168.20.230';
$db1 = 'LeaveFiling';
$db2 = 'NBCTECHDB';
$user = 'sa';
$password = 'Nbc12#';

try {
    $connLeave = new PDO("sqlsrv:Server=$host1;Database=$db1", $user, $password);
    $connLeave->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connHR = new PDO("sqlsrv:Server=$host2;Database=$db2", $user, $password);
    $connHR->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
