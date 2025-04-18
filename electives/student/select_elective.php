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

// Handle preference submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preferences'])) {
    try {
        $pdo->beginTransaction();

        // Validate preferences
        $preferences = array_filter($_POST['preferences']); // Remove empty values
        if (count($preferences) > 3) {
            throw new Exception("You can only select up to 3 preferences");
        }

        if (count($preferences) !== count(array_unique($preferences))) {
            throw new Exception("Each preference must be unique");
        }

        // Get departments of selected electives
        $stmt = $pdo->prepare("
            SELECT e.id, e.department_id 
            FROM electives e 
            WHERE e.id IN (" . implode(',', array_fill(0, count($preferences), '?')) . ")
        ");
        $stmt->execute(array_values($preferences));
        $elective_depts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Check if any elective is from student's department
        foreach ($preferences as $elective_id) {
            if ($elective_depts[$elective_id] == $student['department_id']) {
                throw new Exception("You cannot select electives from your own department");
            }
        }

        // Delete existing preferences
        $stmt = $pdo->prepare("DELETE FROM student_preferences WHERE student_id = ?");
        $stmt->execute([$_SESSION['student_id']]);

        // Insert new preferences
        $stmt = $pdo->prepare("
            INSERT INTO student_preferences (student_id, elective_id, preference_order)
            VALUES (?, ?, ?)
        ");

        $order = 1;
        foreach ($preferences as $elective_id) {
            $stmt->execute([$_SESSION['student_id'], $elective_id, $order]);
            $order++;
        }

        $pdo->commit();
        $success = "Your preferences have been saved successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving preferences: " . $e->getMessage();
    }
}

// Get available electives from other departments
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name,
           (SELECT COUNT(*) FROM elective_allotments WHERE elective_id = e.id AND status = 'allotted') as allotted_count,
           (SELECT preference_order FROM student_preferences WHERE student_id = ? AND elective_id = e.id) as current_preference
    FROM electives e
    JOIN departments d ON d.id = e.department_id
    WHERE e.department_id != ?
    ORDER BY d.name, e.name
");
$stmt->execute([$_SESSION['student_id'], $student['department_id']]);
$available_electives = $stmt->fetchAll();

// Group electives by department
$department_electives = [];
foreach ($available_electives as $elective) {
    if (!isset($department_electives[$elective['department_name']])) {
        $department_electives[$elective['department_name']] = [];
    }
    $department_electives[$elective['department_name']][] = $elective;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Electives - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">JNTUA CEA - Student Portal</a>
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
                    <a href="select_elective.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-book"></i> Select Elective
                    </a>
                    <a href="queries.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-chat-dots"></i> My Queries
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <h2 class="mb-4">Select Electives</h2>
                <p class="text-muted">Department: <?php echo htmlspecialchars($student['department_name']); ?></p>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Select Your Preferences</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6>Instructions:</h6>
                            <ol>
                                <li>You can select up to 3 electives from departments other than your own</li>
                                <li>Order your preferences from 1 (most preferred) to 3 (least preferred)</li>
                                <li>Each preference must be from a different department</li>
                                <li>You cannot change your preferences after the allotment process begins</li>
                            </ol>
                        </div>

                        <form method="POST" id="preferenceForm">
                            <?php foreach ($department_electives as $dept_name => $electives): ?>
                                <h6 class="mt-4"><?php echo htmlspecialchars($dept_name); ?></h6>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Elective Name</th>
                                                <th>Capacity</th>
                                                <th>Available</th>
                                                <th>Preference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($electives as $elective): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($elective['name']); ?></td>
                                                    <td><?php echo $elective['capacity']; ?></td>
                                                    <td><?php echo $elective['capacity'] - $elective['allotted_count']; ?></td>
                                                    <td>
                                                        <select name="preferences[]" class="form-select preference-select">
                                                            <option value="">Not Selected</option>
                                                            <option value="<?php echo $elective['id']; ?>" 
                                                                    <?php echo $elective['current_preference'] == 1 ? 'selected' : ''; ?>>
                                                                1st Choice
                                                            </option>
                                                            <option value="<?php echo $elective['id']; ?>"
                                                                    <?php echo $elective['current_preference'] == 2 ? 'selected' : ''; ?>>
                                                                2nd Choice
                                                            </option>
                                                            <option value="<?php echo $elective['id']; ?>"
                                                                    <?php echo $elective['current_preference'] == 3 ? 'selected' : ''; ?>>
                                                                3rd Choice
                                                            </option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>

                            <button type="submit" class="btn btn-primary mt-3">Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('preferenceForm');
        const selects = document.querySelectorAll('.preference-select');

        // Function to count selected preferences
        function countSelectedPreferences() {
            let count = 0;
            selects.forEach(select => {
                if (select.value) count++;
            });
            return count;
        }

        // Function to validate form
        form.onsubmit = function(e) {
            const count = countSelectedPreferences();
            if (count === 0) {
                alert('Please select at least one preference');
                e.preventDefault();
                return false;
            }
            if (count > 3) {
                alert('You can only select up to 3 preferences');
                e.preventDefault();
                return false;
            }

            // Check for duplicate preferences
            const selected = new Set();
            let hasDuplicates = false;
            selects.forEach(select => {
                if (select.value && selected.has(select.value)) {
                    hasDuplicates = true;
                }
                selected.add(select.value);
            });

            if (hasDuplicates) {
                alert('Each preference must be unique');
                e.preventDefault();
                return false;
            }

            return true;
        };
    });
    </script>
</body>
</html> 