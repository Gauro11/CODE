<?php
include 'connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];
    $age = $_POST['age'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $position = $_POST['position'];

    // Hash default password
    $password = password_hash('employee123', PASSWORD_DEFAULT);
    $archived = 0; // default active

    $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, password, phone_number, age, birthdate, gender, position, archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssisssi", $fname, $lname, $email, $password, $phone, $age, $birthdate, $gender, $position, $archived);

    if ($stmt->execute()) {
        header("Location: UM_employees.php");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Employee</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<style>
.content-container {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    margin: 20px;
    max-width: 600px;
}
form input, form select, form button {
    width: 100%;
    padding: 10px 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
form button {
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
}
form button:hover { background-color: #0056b3; }
h2 { margin-bottom: 20px; }
.error { color: red; margin-bottom: 10px; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header" id="header">
<nav class="nav container">
    <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" 
             onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
</nav>
</header>

<!-- DASHBOARD WRAPPER -->
<div class="dashboard__wrapper">
    <!-- SIDEBAR -->
    <aside class="dashboard__sidebar">
        <ul class="sidebar__menu">
            <li class="menu__item"><a href="UM_employees.php" class="menu__link"><i class='bx bx-user-circle'></i> Employees</a></li>
            <li class="menu__item"><a href="admin_dashboard.php" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h2>Add Employee</h2>

                <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

                <form method="POST">
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="phone_number" placeholder="Contact Number">
                    <input type="number" name="age" placeholder="Age">
                    <input type="date" name="birthdate" placeholder="Birthday">
                    <select name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Prefer Not to Say">Prefer Not to Say</option>
                    </select>
                    <select name="position">
                        <option value="">Select Position</option>
                        <option value="Driver">Driver</option>
                        <option value="Cleaner">Cleaner</option>
                    </select>
                    <button type="submit">Add Employee</button>
                </form>
            </div>
        </section>
    </main>
</div>

</body>
</html>
