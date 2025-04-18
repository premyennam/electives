<?php
session_start();
require_once '../config/database.php';

// Check if HOD is logged in
if (!isset($_SESSION['hod_id']) || !isset($_SESSION['department_id'])) {
    header("Location: login.php");
    exit();
}

// Get HOD details
$stmt = $pdo->prepare("
    SELECT h.*, d.name as department_name 
    FROM hod h
    JOIN departments d ON d.id = h.department_id
    WHERE h.id = ?
");
$stmt->execute([$_SESSION['hod_id']]);
$hod = $stmt->fetch();

if (!$hod) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Update session with department info if not set
if (!isset($_SESSION['department_name'])) {
    $_SESSION['department_name'] = $hod['department_name'];
}
?> 