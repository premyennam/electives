<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get admin details
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate current password
    if (!password_verify($current_password, $admin['password'])) {
        $errors[] = "Current password is incorrect";
    }

    // Validate new password
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }

    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with new password
                $stmt = $pdo->prepare("UPDATE admin SET email = ?, password = ? WHERE id = ?");
                $stmt->execute([$email, password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            } else {
                // Update only email
                $stmt = $pdo->prepare("UPDATE admin SET email = ? WHERE id = ?");
                $stmt->execute([$email, $_SESSION['admin_id']]);
            }
            $success = "Profile updated successfully!";
            
            // Refresh admin details
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - JNTUA CEA</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Profile</a>
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
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Admin Profile</h2>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
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