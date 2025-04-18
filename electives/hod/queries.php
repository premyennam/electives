<?php
require_once 'init.php';

// Check if HOD is logged in
if (!isset($_SESSION['hod_id'])) {
    header("Location: login.php");
    exit();
}

// Get HOD details
$stmt = $pdo->prepare("SELECT * FROM hod WHERE id = ?");
$stmt->execute([$_SESSION['hod_id']]);
$hod = $stmt->fetch();

// Handle query status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $query_id = $_POST['query_id'];
    $status = $_POST['status'];
    $response = $_POST['response'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE queries 
            SET status = ?, response = ?, resolved_by = ?, resolved_at = CURRENT_TIMESTAMP
            WHERE id = ? AND student_id IN (
                SELECT id FROM students WHERE department_id = ?
            )
        ");
        $stmt->execute([$status, $response, $_SESSION['hod_id'], $query_id, $_SESSION['department_id']]);
        $success = "Query status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating query: " . $e->getMessage();
    }
}

// Get all queries for the department
$stmt = $pdo->prepare("
    SELECT q.*, s.name as student_name, s.admission_no, s.year,
           d.name as department_name
    FROM queries q
    JOIN students s ON q.student_id = s.id
    JOIN departments d ON d.id = s.department_id
    WHERE s.department_id = ?
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION['department_id']]);
$queries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Queries - HOD Portal</title>
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
                    <a href="queries.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-chat-dots"></i> Student Queries
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-earmark-text"></i> Reports
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Student Queries</h2>
                <p class="text-muted">Department: <?php echo htmlspecialchars($_SESSION['department_name']); ?></p>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queries as $query): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($query['student_name']); ?><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($query['admission_no']); ?> | 
                                                    Year: <?php echo $query['year']; ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($query['subject']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($query['message'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'resolved' => 'success',
                                                    'rejected' => 'danger'
                                                ];
                                                $status_text = ucfirst($query['status']);
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$query['status']]; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($query['created_at'])); ?></td>
                                            <td>
                                                <?php if ($query['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#responseModal<?php echo $query['id']; ?>">
                                                        Respond
                                                    </button>
                                                    
                                                    <!-- Response Modal -->
                                                    <div class="modal fade" id="responseModal<?php echo $query['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Respond to Query</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="query_id" value="<?php echo $query['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Status</label>
                                                                            <select name="status" class="form-select" required>
                                                                                <option value="resolved">Resolve</option>
                                                                                <option value="rejected">Reject</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Response</label>
                                                                            <textarea name="response" class="form-control" rows="3" required></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_status" class="btn btn-primary">Submit Response</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
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