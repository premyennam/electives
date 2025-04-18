<?php
session_start();
require_once '../config/database.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Get student details with department name
$stmt = $pdo->prepare("
    SELECT s.*, d.name as department_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// Get elective allotment details
$stmt = $pdo->prepare("
    SELECT ea.*, e.name as elective_name, e.capacity
    FROM elective_allotments ea
    JOIN electives e ON ea.elective_id = e.id
    WHERE ea.student_id = ?
    ORDER BY ea.created_at DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['student_id']]);
$allotment = $stmt->fetch();

// Get pending queries
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM queries 
    WHERE student_id = ? AND status = 'pending'
");
$stmt->execute([$_SESSION['student_id']]);
$pending_queries = $stmt->fetch()['total'];

// Get recent queries
$stmt = $pdo->prepare("
    SELECT * FROM queries 
    WHERE student_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['student_id']]);
$recent_queries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">JNTUA CEA - Student Portal</a>
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
                    <a href="select_elective.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-book"></i> Select Elective
                    </a>
                    <a href="queries.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-chat-dots"></i> My Queries
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Dashboard</h2>
                <p class="text-muted">Welcome, <?php echo htmlspecialchars($student['name']); ?></p>

                <!-- Student Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Student Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></p>
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>
                                <p><strong>Year:</strong> <?php echo $student['year']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>CGPA:</strong> <?php echo $student['cgpa']; ?></p>
                                <p><strong>Backlogs:</strong> <?php echo $student['backlogs']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Elective Allotment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Elective Allotment Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($allotment): ?>
                            <div class="alert alert-success">
                                <h6>Allotted Elective: <?php echo htmlspecialchars($allotment['elective_name']); ?></h6>
                                <p class="mb-0">Status: <span class="badge bg-success">Allotted</span></p>
                                <small class="text-muted">Allotted on: <?php echo date('d M Y', strtotime($allotment['created_at'])); ?></small>
                            </div>
                            <div class="mt-3">
                                <h6>Elective Details:</h6>
                                <p><strong>Capacity:</strong> <?php echo $allotment['capacity']; ?> students</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p class="mb-0">No elective has been allotted yet. Please select your elective preferences.</p>
                            </div>
                            <a href="select_elective.php" class="btn btn-primary">Select Elective</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Queries -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Queries</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_queries)): ?>
                            <p class="text-muted">No queries found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Query</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_queries as $query): ?>
                                            <tr>
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