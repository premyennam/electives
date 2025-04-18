<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_hods FROM hod");
$total_hods = $stmt->fetch()['total_hods'];

$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];

$stmt = $pdo->query("SELECT COUNT(*) as total_electives FROM electives");
$total_electives = $stmt->fetch()['total_electives'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_queries FROM queries WHERE status = 'pending'");
$pending_queries = $stmt->fetch()['pending_queries'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JNTUA CEA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">JNTUA CEA - Admin Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>

                    <a href="manage_hods.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Manage HODs
                    </a>
                    <a href="manage_students.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-mortarboard"></i> Manage Students
                    </a>
                    <a href="allot_electives.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-check2-square"></i> Allot Electives
                    </a>

                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-text"></i> Reports
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Dashboard Overview</h2>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total HODs</h5>
                                <p class="card-text display-4"><?php echo $total_hods; ?></p>
                                <a href="manage_hods.php" class="btn btn-light">Manage HODs</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <p class="card-text display-4"><?php echo $total_students; ?></p>
                                <a href="reports.php" class="btn btn-light">View Details</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Electives</h5>
                                <p class="card-text display-4"><?php echo $total_electives; ?></p>
                                <a href="allot_electives.php" class="btn btn-light">Manage Electives</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Queries</h5>
                                <p class="card-text display-4"><?php echo $pending_queries; ?></p>
                                <a href="queries.php" class="btn btn-light">View Queries</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 