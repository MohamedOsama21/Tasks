<?php
$passFile = getenv('DB_PASSWORD_FILE');
$dbPassword = $passFile ? trim(file_get_contents($passFile)) : getenv('DB_PASSWORD');

$conn = new mysqli(
    getenv('DB_HOST'),
    getenv('DB_USER'),
    $dbPassword,
    getenv('DB_NAME')
);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "DB connection failed"]));
}
