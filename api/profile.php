<?php
session_start();
include_once '../config/Database.php';
header('Content-Type: application/json');

// Cek login
if(!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 Unauthorized
    echo json_encode(["status" => "error", "message" => "Akses ditolak (Unauthorized)"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'get_info': // Ambil info user
        $query = "SELECT name, email, role, bio, photo FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;

    case 'update_profile': // Update profil user
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
             http_response_code(405); // Method Not Allowed
             echo json_encode(["status" => "error", "message" => "Metode POST diperlukan"]);
             exit;
        }
        
        $name = htmlspecialchars(strip_tags($_POST['name']));
        $bio = htmlspecialchars(strip_tags($_POST['bio']));
        $sql = "UPDATE users SET name = ?, bio = ? WHERE id = ?";
        $params = [$name, $bio, $user_id];

        if(isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            // Tambahkan validasi tipe file yang lebih ketat di sini jika diperlukan
            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            $target_dir = dirname(__DIR__) . "/assets/uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            if(move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $new_filename)) {
                $sql = "UPDATE users SET name = ?, bio = ?, photo = ? WHERE id = ?";
                $params = [$name, $bio, $new_filename, $user_id];
            } else {
                 // Gagal upload, kirim error tapi jangan exit agar update data lain tetap jalan
                 error_log("Gagal memindahkan file upload untuk user ID: " . $user_id);
            }
        }
        
        $stmt = $db->prepare($sql);
        if($stmt->execute($params)) {
            $_SESSION['user_name'] = $name;
            echo json_encode(["status" => "success", "message" => "Profil berhasil diperbarui!"]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(["status" => "error", "message" => "Gagal update database"]);
        }
        exit;

    case 'get_my_recipes': // Ambil resep buatan user
        $query = "SELECT r.*, u.name as author FROM recipes r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.user_id = ? ORDER BY r.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;

    case 'get_liked_recipes': // Ambil resep yang disukai user
        $query = "SELECT r.*, u.name as author FROM recipes r
                  JOIN likes l ON r.id = l.recipe_id
                  JOIN users u ON r.user_id = u.id
                  WHERE l.user_id = ? 
                  ORDER BY l.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "Aksi tidak valid atau hilang."]);
        exit;
}
?>