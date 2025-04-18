<?php
require_once 'init.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_elective'])) {
        $elective_name = $_POST['elective_name'];
        $capacity = $_POST['capacity'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO electives (department_id, name, capacity, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['department_id'], $elective_name, $capacity, $_SESSION['hod_id']]);
            $success = "Elective added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding elective: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_elective'])) {
        $elective_id = $_POST['elective_id'];
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM electives 
                WHERE id = ? AND department_id = ?
            ");
            $stmt->execute([$elective_id, $_SESSION['department_id']]);
            $success = "Elective deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting elective: " . $e->getMessage();
        }
    }
}

// Get all electives for the HOD's department
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name,
           COUNT(ea.id) as allotted_count,
           (e.capacity - COUNT(ea.id)) as available_seats
    FROM electives e
    JOIN departments d ON d.id = e.department_id
    LEFT JOIN elective_allotments ea ON ea.elective_id = e.id AND ea.status = 'allotted'
    WHERE e.department_id = ?
    GROUP BY e.id
    ORDER BY e.name
");
$stmt->execute([$_SESSION['department_id']]);
$electives = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Electives - HOD Portal</title>
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
                    <a href="manage_electives.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Manage Electives</h2>
                <p class="text-muted">Department: <?php echo htmlspecialchars($_SESSION['department_name']); ?></p>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Add New Elective Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Elective</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label for="elective_name" class="form-label">Elective Name</label>
                                <input type="text" class="form-control" id="elective_name" name="elective_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="add_elective" class="btn btn-success w-100">Add Elective</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Electives List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Electives</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Elective Name</th>
                                        <th>Capacity</th>
                                        <th>Allotted</th>
                                        <th>Available Seats</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($electives as $elective): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($elective['name']); ?></td>
                                            <td><?php echo $elective['capacity']; ?></td>
                                            <td><?php echo $elective['allotted_count']; ?></td>
                                            <td><?php echo $elective['available_seats']; ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="elective_id" value="<?php echo $elective['id']; ?>">
                                                    <button type="submit" name="delete_elective" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Are you sure you want to delete this elective?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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