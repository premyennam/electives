<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get department-wise allotment statistics
$stmt = $pdo->query("
    SELECT 
        d.name as department_name,
        e.name as elective_name,
        e.capacity,
        COUNT(ea.id) as allotted_count,
        s.year,
        COUNT(DISTINCT CASE WHEN sp.preference_order = 1 AND ea.elective_id = e.id THEN sp.student_id END) as first_choice,
        COUNT(DISTINCT CASE WHEN sp.preference_order = 2 AND ea.elective_id = e.id THEN sp.student_id END) as second_choice,
        COUNT(DISTINCT CASE WHEN sp.preference_order = 3 AND ea.elective_id = e.id THEN sp.student_id END) as third_choice,
        COUNT(DISTINCT CASE WHEN sp.preference_order IS NULL AND ea.elective_id = e.id THEN sp.student_id END) as no_preference
    FROM departments d
    JOIN electives e ON e.department_id = d.id
    LEFT JOIN elective_allotments ea ON ea.elective_id = e.id
    LEFT JOIN students s ON s.id = ea.student_id
    LEFT JOIN student_preferences sp ON sp.student_id = s.id AND sp.elective_id = e.id
    GROUP BY d.id, e.id, s.year
    ORDER BY d.name, e.name, s.year
");
$dept_stats = $stmt->fetchAll();

// Get year-wise statistics
$stmt = $pdo->query("
    SELECT 
        s.year,
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT ea.student_id) as allotted_students,
        COUNT(DISTINCT CASE WHEN sp.id IS NOT NULL THEN s.id END) as students_with_preferences
    FROM students s
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id
    LEFT JOIN student_preferences sp ON sp.student_id = s.id
    WHERE s.year BETWEEN 2 AND 4
    GROUP BY s.year
    ORDER BY s.year
");
$year_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">JNTUA CEA - Admin Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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

                    <a href="manage_hods.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Manage HODs
                    </a>
                    <a href="manage_students.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-mortarboard"></i> Manage Students
                    </a>
                    <a href="allot_electives.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-check2-square"></i> Allot Electives
                    </a>

                    <a href="reports.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-file-text"></i> Reports
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Reports</h2>

                <!-- Year-wise Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Year-wise Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th>Total Students</th>
                                        <th>Students with Preferences</th>
                                        <th>Allotted Students</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($year_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo $stat['year']; ?></td>
                                            <td><?php echo $stat['total_students']; ?></td>
                                            <td><?php echo $stat['students_with_preferences']; ?></td>
                                            <td><?php echo $stat['allotted_students']; ?></td>
                                            <td><?php echo $stat['total_students'] - $stat['allotted_students']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Department-wise Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Department-wise Elective Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Elective</th>
                                        <th>Year</th>
                                        <th>Capacity</th>
                                        <th>Allotted</th>
                                        <th>1st Choice</th>
                                        <th>2nd Choice</th>
                                        <th>3rd Choice</th>
                                        <th>No Preference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dept_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['department_name']); ?></td>
                                            <td><?php echo htmlspecialchars($stat['elective_name']); ?></td>
                                            <td><?php echo $stat['year']; ?></td>
                                            <td><?php echo $stat['capacity']; ?></td>
                                            <td><?php echo $stat['allotted_count']; ?></td>
                                            <td><?php echo $stat['first_choice']; ?></td>
                                            <td><?php echo $stat['second_choice']; ?></td>
                                            <td><?php echo $stat['third_choice']; ?></td>
                                            <td><?php echo $stat['no_preference']; ?></td>
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