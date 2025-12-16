<?php
session_start();
include_once '../config/Database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'] ?? 0;
$action = $_REQUEST['action'] ?? '';

if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!$user_id) { echo json_encode(["status"=>"error", "message"=>"Login dulu"]); exit; }
    $stmt = $db->prepare("INSERT INTO comments (user_id, recipe_id, comment) VALUES (?, ?, ?)");
    if($stmt->execute([$user_id, $_POST['recipe_id'], htmlspecialchars($_POST['comment'])])) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error", "message"=>"Gagal kirim komentar"]);
    }
}

if($action == 'list') {
    // PENTING: Select u.photo juga
    $stmt = $db->prepare("SELECT c.*, u.name, u.photo FROM comments c JOIN users u ON c.user_id = u.id WHERE c.recipe_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$_GET['recipe_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>