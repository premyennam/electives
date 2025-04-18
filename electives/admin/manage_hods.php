<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success = $error = '';

// Handle HOD creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_hod'])) {
            $stmt = $pdo->prepare("
                INSERT INTO hod (name, email, password, department_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['department_id']
            ]);
            $success = "HOD added successfully!";
        }
        else if (isset($_POST['update_hod'])) {
            $sql = "UPDATE hod SET name = ?, email = ?, department_id = ?";
            $params = [$_POST['name'], $_POST['email'], $_POST['department_id']];
            
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $_POST['hod_id'];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success = "HOD updated successfully!";
        }
        else if (isset($_POST['delete_hod'])) {
            $stmt = $pdo->prepare("DELETE FROM hod WHERE id = ?");
            $stmt->execute([$_POST['hod_id']]);
            $success = "HOD deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all HODs with department names
$stmt = $pdo->query("
    SELECT h.*, d.name as department_name 
    FROM hod h
    JOIN departments d ON d.id = h.department_id
    ORDER BY d.name, h.name
");
$hods = $stmt->fetchAll();

// Get all departments for the dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage HODs - Admin Portal</title>
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

                    <a href="manage_hods.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Manage HODs</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Add HOD Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New HOD</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label">Department</label>
                                    <select class="form-select" id="department_id" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>">
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="add_hod" class="btn btn-primary">Add HOD</button>
                        </form>
                    </div>
                </div>

                <!-- HODs List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current HODs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hods as $hod): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($hod['name']); ?></td>
                                            <td><?php echo htmlspecialchars($hod['email']); ?></td>
                                            <td><?php echo htmlspecialchars($hod['department_name']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $hod['id']; ?>">
                                                    Edit
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="hod_id" value="<?php echo $hod['id']; ?>">
                                                    <button type="submit" name="delete_hod" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this HOD?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $hod['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit HOD</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST">
                                                            <input type="hidden" name="hod_id" value="<?php echo $hod['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Name</label>
                                                                <input type="text" class="form-control" name="name" 
                                                                       value="<?php echo htmlspecialchars($hod['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" class="form-control" name="email" 
                                                                       value="<?php echo htmlspecialchars($hod['email']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">New Password (leave blank to keep current)</label>
                                                                <input type="password" class="form-control" name="password">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Department</label>
                                                                <select class="form-select" name="department_id" required>
                                                                    <?php foreach ($departments as $dept): ?>
                                                                        <option value="<?php echo $dept['id']; ?>" 
                                                                                <?php echo $dept['id'] == $hod['department_id'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($dept['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <button type="submit" name="update_hod" class="btn btn-primary">Update HOD</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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