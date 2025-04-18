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
                if (count($data) < 7) {
                    throw new Exception("Invalid CSV format. Each row must have 7 columns.");
                }

                $admission_no = trim($data[1]);
                $name = trim($data[2]);
                $branch = trim($data[3]);
                $year = (int)trim($data[4]);
                $cgpa = (float)trim($data[5]);
                $backlogs = (int)trim($data[6]);
                $email = $admission_no . "@jntua.ac.in";

                // Validate data
                if (empty($admission_no) || empty($name) || empty($branch)) {
                    throw new Exception("Required fields cannot be empty.");
                }

                if ($year < 2 || $year > 4) {
                    throw new Exception("Year must be between 2 and 4.");
                }

                if ($cgpa < 0 || $cgpa > 10) {
                    throw new Exception("CGPA must be between 0 and 10.");
                }

                if ($backlogs < 0) {
                    throw new Exception("Backlogs cannot be negative.");
                }
                
                // Get department_id from branch name
                $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
                $stmt->execute([$branch]);
                $department = $stmt->fetch();
                
                if (!$department) {
                    throw new Exception("Invalid department name: " . $branch);
                }

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

// Get all students for the department and selected year
$stmt = $pdo->prepare("
    SELECT s.*, 
           e.name as elective_name,
           ea.status as allotment_status
    FROM students s
    LEFT JOIN elective_allotments ea ON ea.student_id = s.id AND ea.status = 'allotted'
    LEFT JOIN electives e ON ea.elective_id = e.id
    WHERE s.department_id = ? AND s.year = ?
    ORDER BY s.cgpa DESC, s.backlogs ASC
");
$stmt->execute([$hod['department_id'], $selected_year]);
$students = $stmt->fetchAll();

// Debug information
if (empty($students)) {
    $debug_info = "No students found for department: " . $hod['department_name'] . " and year: " . $selected_year;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - HOD Portal</title>
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
                    <a href="manage_students.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Manage Students</h2>
                <p class="text-muted">Department: <?php echo htmlspecialchars($hod['department_name']); ?></p>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($debug_info)): ?>
                    <div class="alert alert-info"><?php echo $debug_info; ?></div>
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