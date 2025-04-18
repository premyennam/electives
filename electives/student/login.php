<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admission_no = $_POST['admission_no'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM students WHERE admission_no = ?");
    $stmt->execute([$admission_no]);
    $student = $stmt->fetch();

    if ($student && $admission_no === $password) { // Using admission number as password
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['name'];
        $_SESSION['student_admission_no'] = $student['admission_no'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid admission number or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - JNTUA CEA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Student Login</h3>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="admission_no" class="form-label">Admission Number</label>
                                <input type="text" class="form-control" id="admission_no" name="admission_no" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Use your admission number as password</div>
                            </div>
                            <button type="submit" class="btn btn-info w-100">Login</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="../index.php" class="text-decoration-none">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 