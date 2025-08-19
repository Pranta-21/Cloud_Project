<?php
session_start();
include('db.php'); // Connects to database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepared statement to fetch user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'patient') {
                header("Location: /HTML_PHP/patient_dashboard.php");
            } elseif ($user['role'] === 'caretaker') {
                header("Location: /HTML_PHP/caretaker_dashboard.php");
            } else {
                header("Location: /HTML_PHP/Dashboard.html"); // Default
            }
            exit();
        } else {
            // Wrong password
            echo "<script>alert('Invalid password. Please try again.'); window.location.href='/HTML_PHP/login.php';</script>";
        }
    } else {
        // No user found
        echo "<script>alert('Username not found.'); window.location.href='/HTML_PHP/login.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: /HTML_PHP/login.php");
    exit();
}
