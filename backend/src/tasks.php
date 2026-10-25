<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(["error" => "Not logged in"]));
}

$uid = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

$redis = new Redis();
$redis->connect(getenv('REDIS_HOST'), 6379);
$key = "tasks:" . $uid;

if ($method === 'GET') {
    $cached = $redis->get($key);
    if ($cached !== false) {
        header('X-Cache: HIT');
        echo $cached;
    } else {
        $stmt = $conn->prepare("SELECT id, title, done FROM tasks WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $json = json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        $redis->setex($key, 30, $json);
        header('X-Cache: MISS');
        echo $json;
    }
}

if ($method === 'POST') {
    $title = $_POST['title'] ?? '';
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)");
    $stmt->bind_param("is", $uid, $title);
    $stmt->execute();
    $redis->del($key);
    echo json_encode(["message" => "Added"]);
}

if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = (int)($data['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    $redis->del($key);
    echo json_encode(["message" => "Deleted"]);
}
