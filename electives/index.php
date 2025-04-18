<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JNTUA CEA - Open Elective Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">JNTUA CEA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h1 class="display-4 mb-4">Open Elective Management System</h1>
                <p class="lead mb-5">Welcome to JNTUA College of Engineering Anantapur's Open Elective Management System. Please select your login portal below.</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-person-circle display-1 text-primary mb-3"></i>
                        <h3 class="card-title">Student Portal</h3>
                        <p class="card-text">Access your elective selection and allotment status.</p>
                        <a href="student/login.php" class="btn btn-primary">Student Login</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-person-badge display-1 text-success mb-3"></i>
                        <h3 class="card-title">HOD Portal</h3>
                        <p class="card-text">Manage department electives and student queries.</p>
                        <a href="hod/login.php" class="btn btn-success">HOD Login</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-lock display-1 text-danger mb-3"></i>
                        <h3 class="card-title">Admin Portal</h3>
                        <p class="card-text">System administration and overall management.</p>
                        <a href="admin/login.php" class="btn btn-danger">Admin Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>About JNTUA CEA</h5>
                    <p>JNTUA College of Engineering Anantapur is a premier technical institution committed to excellence in education and research.</p>
                </div>
                <div class="col-md-6">
                    <h5>Contact Us</h5>
                    <p>
                        Address: JNTUA College of Engineering Anantapur<br>
                        Ananthapuramu - 515002, Andhra Pradesh<br>
                        Phone: +91-8555-XXXXXX<br>
                        Email: info@jntua.ac.in
                    </p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> JNTUA CEA. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 