<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get selected year from query parameter or default to current year
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : 2;

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($file['tmp_name'], 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        try {
            $pdo->beginTransaction();
            
            while (($data = fgetcsv($handle)) !== false) {
                // Expected CSV format: S.No, Admission No, Name, Branch, Year, CGPA, Backlogs
                $admission_no = $data[1];
                $name = $data[2];
                $branch = $data[3];
                $year = $data[4];
                $cgpa = $data[5];
                $backlogs = $data[6];
                $email = $admission_no . "@jntua.ac.in";
                
                // Get department_id from branch name
                $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
                $stmt->execute([$branch]);
                $department = $stmt->fetch();
                
                if ($department) {
                    // Check if student already exists
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE admission_no = ?");
                    $stmt->execute([$admission_no]);
                    
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("
                            INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$admission_no, $name, $email, $department['id'], $year, $cgpa, $backlogs]);
                    }
                }
            }
            
            $pdo->commit();
            $success = "Students uploaded successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error uploading students: " . $e->getMessage();
        }
        
        fclose($handle);
    } else {
        $error = "Error uploading file: " . $file['error'];
    }
}

// Get all students for the selected year
$stmt = $pdo->prepare("
    SELECT s.*, 
           d.name as department_name,
           e.name as elective_name,
           ea.status as allotment_status
    FROM students s
    JOIN departments d ON s.department_id = d.id
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id AND ea.status = 'allotted'
    LEFT JOIN electives e ON ea.elective_id = e.id
    WHERE s.year = ?
    ORDER BY d.name, s.cgpa DESC, s.backlogs ASC
");
$stmt->execute([$selected_year]);
$students = $stmt->fetchAll();

// Get department statistics
$stmt = $pdo->prepare("
    SELECT d.name, 
           COUNT(s.id) as total_students,
           COUNT(CASE WHEN ea.status = 'allotted' THEN 1 END) as allotted_count
    FROM departments d
    LEFT JOIN students s ON s.department_id = d.id AND s.year = ?
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id AND ea.status = 'allotted'
    GROUP BY d.id, d.name
    ORDER BY d.name
");
$stmt->execute([$selected_year]);
$department_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin Portal</title>
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

                    <a href="manage_hods.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Manage HODs
                    </a>
                    <a href="manage_students.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Manage Students</h2>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Year Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Select Year</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="?year=2" class="btn btn-outline-primary <?php echo $selected_year === 2 ? 'active' : ''; ?>">2nd Year</a>
                            <a href="?year=3" class="btn btn-outline-primary <?php echo $selected_year === 3 ? 'active' : ''; ?>">3rd Year</a>
                            <a href="?year=4" class="btn btn-outline-primary <?php echo $selected_year === 4 ? 'active' : ''; ?>">4th Year</a>
                        </div>
                    </div>
                </div>

                <!-- Department Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Department-wise Statistics - <?php echo $selected_year; ?>nd Year</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Total Students</th>
                                        <th>Allotted</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($department_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['name']); ?></td>
                                            <td><?php echo $stat['total_students']; ?></td>
                                            <td><?php echo $stat['allotted_count']; ?></td>
                                            <td><?php echo $stat['total_students'] - $stat['allotted_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Upload CSV Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Upload Student Data</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="csv_file" class="form-label">CSV File</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                    <div class="form-text">
                                        CSV format: S.No, Admission No, Name, Branch, Year, CGPA, Backlogs<br>
                                        Example: 1, 20B01A0501, John Doe, COMPUTER SCIENCE AND ENGINEERING, 2, 8.5, 0
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success w-100">Upload Students</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Students List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Student List - <?php echo $selected_year; ?>nd Year</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>CGPA</th>
                                        <th>Backlogs</th>
                                        <th>Allotted Elective</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                                            <td><?php echo $student['cgpa']; ?></td>
                                            <td><?php echo $student['backlogs']; ?></td>
                                            <td><?php echo $student['elective_name'] ? htmlspecialchars($student['elective_name']) : '-'; ?></td>
                                            <td>
                                                <?php if ($student['allotment_status'] === 'allotted'): ?>
                                                    <span class="badge bg-success">Allotted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not Allotted</span>
                                                <?php endif; ?>
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