<?php
session_start();
require 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row && password_verify($password, $row['password'])) {
    $_SESSION['user_id'] = $row['id'];
    echo json_encode(["message" => "Logged in"]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
}
