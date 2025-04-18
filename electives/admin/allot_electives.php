<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success = $error = '';

// Handle allotment process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_allotment'])) {
    try {
        $pdo->beginTransaction();

        // Get all years that have students
        $stmt = $pdo->query("SELECT DISTINCT year FROM students WHERE year BETWEEN 2 AND 4 ORDER BY year");
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($years as $year) {
            // Get all students for current year, ordered by CGPA (DESC) and backlogs (ASC)
            $stmt = $pdo->prepare("
                SELECT s.*, d.name as department_name 
                FROM students s
                JOIN departments d ON d.id = s.department_id
                WHERE s.year = ?
                ORDER BY s.cgpa DESC, s.backlogs ASC
            ");
            $stmt->execute([$year]);
            $students = $stmt->fetchAll();

            // Process each student
            foreach ($students as $student) {
                // Skip if already allotted
                $stmt = $pdo->prepare("SELECT id FROM elective_allotments WHERE student_id = ?");
                $stmt->execute([$student['id']]);
                if ($stmt->fetch()) {
                    continue;
                }

                // Get student's preferences
                $stmt = $pdo->prepare("
                    SELECT p.*, e.capacity, e.department_id,
                           (SELECT COUNT(*) FROM elective_allotments WHERE elective_id = e.id) as allotted_count
                    FROM student_preferences p
                    JOIN electives e ON e.id = p.elective_id
                    WHERE p.student_id = ?
                    ORDER BY p.preference_order
                ");
                $stmt->execute([$student['id']]);
                $preferences = $stmt->fetchAll();

                $allotted = false;

                // Try to allot based on preferences
                foreach ($preferences as $pref) {
                    // Skip if elective is from student's department
                    if ($pref['department_id'] == $student['department_id']) {
                        continue;
                    }

                    // Check if elective has capacity
                    if ($pref['allotted_count'] < $pref['capacity']) {
                        // Allot this elective
                        $stmt = $pdo->prepare("
                            INSERT INTO elective_allotments (student_id, elective_id, status, created_at)
                            VALUES (?, ?, 'allotted', NOW())
                        ");
                        $stmt->execute([$student['id'], $pref['elective_id']]);

                        // Create notification
                        $stmt = $pdo->prepare("
                            INSERT INTO notifications (user_type, user_id, message, created_at)
                            VALUES ('student', ?, ?, NOW())
                        ");
                        $message = "You have been allotted your " . 
                                 ($pref['preference_order'] == 1 ? '1st' : 
                                 ($pref['preference_order'] == 2 ? '2nd' : '3rd')) . 
                                 " choice elective.";
                        $stmt->execute([$student['id'], $message]);

                        $allotted = true;
                        break;
                    }
                }

                // If no preference could be allotted, try to find any available elective
                if (!$allotted) {
                    $stmt = $pdo->prepare("
                        SELECT e.* 
                        FROM electives e
                        LEFT JOIN elective_allotments ea ON ea.elective_id = e.id
                        WHERE e.department_id != ?
                        GROUP BY e.id
                        HAVING COUNT(ea.id) < e.capacity
                        LIMIT 1
                    ");
                    $stmt->execute([$student['department_id']]);
                    $available_elective = $stmt->fetch();

                    if ($available_elective) {
                        // Allot this elective
                        $stmt = $pdo->prepare("
                            INSERT INTO elective_allotments (student_id, elective_id, status, created_at)
                            VALUES (?, ?, 'allotted', NOW())
                        ");
                        $stmt->execute([$student['id'], $available_elective['id']]);

                        // Create notification
                        $stmt = $pdo->prepare("
                            INSERT INTO notifications (user_type, user_id, message, created_at)
                            VALUES ('student', ?, ?, NOW())
                        ");
                        $message = "You have been allotted an elective as none of your preferences were available.";
                        $stmt->execute([$student['id'], $message]);
                    }
                }
            }
        }

        $pdo->commit();
        $success = "Elective allotment process completed successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error during allotment process: " . $e->getMessage();
    }
}

// Get year-wise statistics
$stmt = $pdo->query("
    SELECT 
        s.year,
        COUNT(DISTINCT s.id) as total_students,
        COUNT(DISTINCT ea.student_id) as allotted_students
    FROM students s
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id
    WHERE s.year BETWEEN 2 AND 4
    GROUP BY s.year
    ORDER BY s.year
");
$year_stats = $stmt->fetchAll();

// Get department-wise statistics
$stmt = $pdo->query("
    SELECT 
        d.name as department_name,
        e.name as elective_name,
        e.capacity,
        COUNT(ea.id) as allotted_count
    FROM departments d
    JOIN electives e ON e.department_id = d.id
    LEFT JOIN elective_allotments ea ON ea.elective_id = e.id
    GROUP BY d.id, e.id
    ORDER BY d.name, e.name
");
$dept_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allot Electives - Admin Portal</title>
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
                    <a href="allot_electives.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-check2-square"></i> Allot Electives
                    </a>

                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-text"></i> Reports
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Allot Electives</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

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
                                        <th>Allotted Students</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($year_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo $stat['year']; ?></td>
                                            <td><?php echo $stat['total_students']; ?></td>
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
                <div class="card mb-4">
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
                                        <th>Capacity</th>
                                        <th>Allotted</th>
                                        <th>Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dept_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['department_name']); ?></td>
                                            <td><?php echo htmlspecialchars($stat['elective_name']); ?></td>
                                            <td><?php echo $stat['capacity']; ?></td>
                                            <td><?php echo $stat['allotted_count']; ?></td>
                                            <td><?php echo $stat['capacity'] - $stat['allotted_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Start Allotment Button -->
                <form method="POST" onsubmit="return confirm('Are you sure you want to start the elective allotment process?');">
                    <button type="submit" name="start_allotment" class="btn btn-primary">
                        <i class="bi bi-play-fill"></i> Start Allotment Process
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 