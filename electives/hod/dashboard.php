<?php
session_start();
require_once '../config/database.php';

// Check if HOD is logged in
if (!isset($_SESSION['hod_id'])) {
    header("Location: login.php");
    exit();
}

// Get HOD details with department name
$stmt = $pdo->prepare("
    SELECT h.*, d.name as department_name 
    FROM hod h 
    JOIN departments d ON h.department_id = d.id 
    WHERE h.id = ?
");
$stmt->execute([$_SESSION['hod_id']]);
$hod = $stmt->fetch();

// Get total students in the department
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM students 
    WHERE department_id = ?
");
$stmt->execute([$hod['department_id']]);
$total_students = $stmt->fetch()['total'];

// Get total electives in the department
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM electives 
    WHERE department_id = ?
");
$stmt->execute([$hod['department_id']]);
$total_electives = $stmt->fetch()['total'];

// Get pending queries
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM queries q
    JOIN students s ON q.student_id = s.id
    WHERE s.department_id = ? AND q.status = 'pending'
");
$stmt->execute([$hod['department_id']]);
$pending_queries = $stmt->fetch()['total'];

// Get total allotments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM elective_allotments ea
    JOIN students s ON ea.student_id = s.id
    WHERE s.department_id = ? AND ea.status = 'allotted'
");
$stmt->execute([$hod['department_id']]);
$total_allotments = $stmt->fetch()['total'];

// Get recent queries
$stmt = $pdo->prepare("
    SELECT q.*, s.name as student_name, s.admission_no
    FROM queries q
    JOIN students s ON q.student_id = s.id
    WHERE s.department_id = ?
    ORDER BY q.created_at DESC
    LIMIT 5
");
$stmt->execute([$hod['department_id']]);
$recent_queries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HOD Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">JNTUA CEA - HOD Portal</a>
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
                    <a href="manage_electives.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-book"></i> Manage Electives
                    </a>
                    <a href="manage_students.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> Manage Students
                    </a>
                    <a href="queries.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-chat-dots"></i> Student Queries
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-earmark-text"></i> Reports
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Dashboard</h2>
                <p class="text-muted">Department: <?php echo htmlspecialchars($hod['department_name']); ?></p>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <p class="card-text display-6"><?php echo $total_students; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Electives</h5>
                                <p class="card-text display-6"><?php echo $total_electives; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending Queries</h5>
                                <p class="card-text display-6"><?php echo $pending_queries; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Allotments</h5>
                                <p class="card-text display-6"><?php echo $total_allotments; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Queries -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Queries</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_queries)): ?>
                            <p class="text-muted">No recent queries found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Query</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_queries as $query): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($query['student_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($query['admission_no']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($query['query']); ?></td>
                                                <td>
                                                    <?php if ($query['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Resolved</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($query['created_at'])); ?></td>
                                                <td>
                                                    <a href="queries.php?id=<?php echo $query['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 