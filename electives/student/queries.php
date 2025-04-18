<?php
session_start();
require_once '../config/database.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Get student details with department name
$stmt = $pdo->prepare("
    SELECT s.*, d.name as department_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// Handle query submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    $errors = [];
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO queries (student_id, subject, message, status) 
                VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$_SESSION['student_id'], $subject, $message]);
            $success = "Query submitted successfully!";
        } catch (PDOException $e) {
            $error = "Error submitting query: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get student's queries
$stmt = $pdo->prepare("
    SELECT q.*, h.name as hod_name 
    FROM queries q 
    LEFT JOIN hod h ON h.id = q.resolved_by 
    WHERE q.student_id = ? 
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION['student_id']]);
$queries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Queries - JNTUA CEA</title>
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
                        <a class="nav-link active" href="queries.php">Queries</a>
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
                    <a href="queries.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-chat-dots"></i> Submit Query
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Submit New Query</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" 
                                       value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" 
                                       required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="4" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Query</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">My Queries</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Response</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queries as $query): ?>
                                        <tr>
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
                                            <td>
                                                <?php if ($query['response']): ?>
                                                    <?php echo nl2br(htmlspecialchars($query['response'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        By: <?php echo htmlspecialchars($query['hod_name']); ?>
                                                    </small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($query['created_at'])); ?></td>
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