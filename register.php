<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim($_POST['first_name']);
    $last     = trim($_POST['last_name']);
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $address  = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 1. passwords must match
    if ($password !== $confirm) {
        die('Passwords do not match. <a href="regis.html">Go back</a>');
    }

    // 2. email must not already be registered
    $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        die('That email is already registered. <a href="regis.html">Go back</a>');
    }

    // 3. hash the password and save the user
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, username, email, password, address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$first, $last, $username, $email, $hash, $address]);

    // 4. send them to the login page
    header('Location: Login.html');
    exit;
}