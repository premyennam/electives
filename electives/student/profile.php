<?php
session_start();
require_once '../config/database.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, d.name as department_name 
    FROM students s 
    JOIN departments d ON d.id = s.department_id 
    WHERE s.id = ?
");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: login.php");
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    try {
        // Verify current password
        if (!empty($current_password)) {
            $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
            $stmt->execute([$_SESSION['student_id']]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $current_hash)) {
                $error = "Current password is incorrect!";
            } else {
                // Update profile
                $sql = "UPDATE students SET name = ?, email = ?, phone = ?";
                $params = [$name, $email, $phone];
                
                // Update password if provided
                if (!empty($new_password)) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $_SESSION['student_id'];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $success = "Profile updated successfully!";
                
                // Refresh student data
                $stmt = $pdo->prepare("
                    SELECT s.*, d.name as department_name 
                    FROM students s 
                    JOIN departments d ON d.id = s.department_id 
                    WHERE s.id = ?
                ");
                $stmt->execute([$_SESSION['student_id']]);
                $student = $stmt->fetch();
            }
        } else {
            $error = "Please enter your current password to update profile!";
        }
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - JNTUA CEA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">JNTUA CEA - Student Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="queries.php">Queries</a>
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
                    <a href="select_elective.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-book"></i> Select Elective
                    </a>
                    <a href="queries.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-chat-dots"></i> Submit Query
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Student Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($student['department_name']); ?>" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Admission Number</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($student['admission_no']); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($student['year']); ?>" readonly>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3">Change Password</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 