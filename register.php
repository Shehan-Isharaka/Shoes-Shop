<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = cleanInput($_POST['name']);
    $email    = cleanInput($_POST['email']);
    $password = $_POST['password'];

    if (!$name || !$email || !$password) {
        $error = "All fields required";
    } elseif (!isValidEmail($email)) {
        $error = "Invalid email";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $connection->prepare(
            "INSERT INTO users (name,email,password,role) 
             VALUES (?,?,?, 'customer')"
        );
        $stmt->bind_param("sss", $name, $email, $hash);
        $stmt->execute();

        redirect("login.php");
    }
}
