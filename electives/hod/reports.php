<?php
session_start();
require_once '../config/database.php';

// Check if HOD is logged in
if (!isset($_SESSION['hod_id'])) {
    header("Location: login.php");
    exit();
}

// Get HOD details
$stmt = $pdo->prepare("SELECT * FROM hod WHERE id = ?");
$stmt->execute([$_SESSION['hod_id']]);
$hod = $stmt->fetch();

// Get department statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT e.id) as total_electives,
        COUNT(DISTINCT ea.id) as total_allotments,
        COUNT(DISTINCT q.id) as total_queries
    FROM students s
    LEFT JOIN electives e ON e.department_id = ?
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id
    LEFT JOIN queries q ON q.student_id = s.id
    WHERE s.department_id = ?
");
$stmt->execute([$_SESSION['department_id'], $_SESSION['department_id']]);
$stats = $stmt->fetch();

// Get elective-wise statistics
$stmt = $pdo->prepare("
    SELECT 
        e.name as elective_name,
        e.capacity,
        COUNT(ea.id) as allotted_count,
        COUNT(CASE WHEN ea.status = 'allotted' THEN 1 END) as confirmed_count
    FROM electives e
    LEFT JOIN elective_allotments ea ON ea.elective_id = e.id
    WHERE e.department_id = ?
    GROUP BY e.id
    ORDER BY e.name
");
$stmt->execute([$_SESSION['department_id']]);
$elective_stats = $stmt->fetchAll();

// Get student-wise elective status
$stmt = $pdo->prepare("
    SELECT 
        s.name as student_name,
        s.admission_no,
        s.cgpa,
        s.backlogs,
        e.name as elective_name,
        ea.status as allotment_status
    FROM students s
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id
    LEFT JOIN electives e ON ea.elective_id = e.id
    WHERE s.department_id = ?
    ORDER BY s.name
");
$stmt->execute([$_SESSION['department_id']]);
$student_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - HOD Portal</title>
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
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
                    <a href="reports.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-file-earmark-text"></i> Reports
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Department Reports</h2>
                <p class="text-muted">Department: <?php echo htmlspecialchars($_SESSION['department_name']); ?></p>

                <!-- Department Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <h2 class="card-text"><?php echo $stats['total_students']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Electives</h5>
                                <h2 class="card-text"><?php echo $stats['total_electives']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Allotments</h5>
                                <h2 class="card-text"><?php echo $stats['total_allotments']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Queries</h5>
                                <h2 class="card-text"><?php echo $stats['total_queries']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Elective-wise Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Elective-wise Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Elective Name</th>
                                        <th>Capacity</th>
                                        <th>Allotted</th>
                                        <th>Confirmed</th>
                                        <th>Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($elective_stats as $elective): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($elective['elective_name']); ?></td>
                                            <td><?php echo $elective['capacity']; ?></td>
                                            <td><?php echo $elective['allotted_count']; ?></td>
                                            <td><?php echo $elective['confirmed_count']; ?></td>
                                            <td><?php echo $elective['capacity'] - $elective['allotted_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Student-wise Elective Status -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Student-wise Elective Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Admission No</th>
                                        <th>CGPA</th>
                                        <th>Backlogs</th>
                                        <th>Allotted Elective</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($student_stats as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                            <td><?php echo $student['cgpa']; ?></td>
                                            <td><?php echo $student['backlogs']; ?></td>
                                            <td><?php echo $student['elective_name'] ? htmlspecialchars($student['elective_name']) : 'Not Allotted'; ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'allotted' => 'success',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger'
                                                ];
                                                $status_text = $student['allotment_status'] ? ucfirst($student['allotment_status']) : 'Not Applied';
                                                $status_class = $student['allotment_status'] ? $status_class[$student['allotment_status']] : 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 