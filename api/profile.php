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

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    // Jika bukan dari AJAX, tendang user kembali ke halaman tampilan
    header("Location: ../index.php");
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

    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                http_response_code(405);
                echo json_encode(["status" => "error", "message" => "Metode POST diperlukan"]);
                exit;
        }
        
        $name = htmlspecialchars(strip_tags($_POST['name']));
        $bio = htmlspecialchars(strip_tags($_POST['bio']));
        
        // Debugging array untuk melihat apa yang terjadi
        $debug = [];
        $error_msg = "";

        $sql = "UPDATE users SET name = ?, bio = ? WHERE id = ?";
        $params = [$name, $bio, $user_id];

        // Cek apakah ada file yang dikirim
        if(isset($_FILES['photo'])) {
            $debug['file_info'] = $_FILES['photo']; // Info file

            if($_FILES['photo']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
                
                // DEFINISI FOLDER TUJUAN (Pastikan struktur folder sesuai)
                // dirname(__DIR__) naik satu level dari folder 'api' ke root project
                $base_path = dirname(__DIR__); 
                $target_dir_relative = "/assets/uploads/users/";
                $target_dir = $base_path . $target_dir_relative;
                
                $debug['target_dir'] = $target_dir; // Cek path ini benar atau salah

                // 1. Cek/Buat Folder
                if (!is_dir($target_dir)) {
                    // Coba buat folder
                    if(!mkdir($target_dir, 0777, true)) {
                        $error_msg = "Gagal membuat folder users. Cek izin (permission) folder assets/uploads.";
                        $debug['mkdir_fail'] = true;
                    }
                }

                // 2. Pindahkan File
                if(empty($error_msg)) {
                    $target_file = $target_dir . $new_filename;
                    
                    if(move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                        // SUKSES UPLOAD
                        $sql = "UPDATE users SET name = ?, bio = ?, photo = ? WHERE id = ?";
                        $params = [$name, $bio, $new_filename, $user_id];
                        $debug['upload_status'] = "Sukses pindah file ke: " . $target_file;
                    } else {
                        // GAGAL PINDAH FILE
                        $error_msg = "Gagal memindahkan file (move_uploaded_file error). Cek Permission Write.";
                    }
                }
            } else {
                // Error dari PHP (misal file terlalu besar)
                $error_msg = "Error Upload PHP Code: " . $_FILES['photo']['error'];
            }
        }
        
        // Eksekusi SQL jika tidak ada error upload fatal
        if(empty($error_msg)) {
            $stmt = $db->prepare($sql);
            if($stmt->execute($params)) {
                $_SESSION['user_name'] = $name;
                echo json_encode([
                    "status" => "success", 
                    "message" => "Profil update!",
                    "debug" => $debug // Lihat ini di Console Browser
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Gagal update database", "debug" => $debug]);
            }
        } else {
            // Kirim pesan error spesifik ke frontend
            echo json_encode([
                "status" => "error", 
                "message" => "Upload Gagal: " . $error_msg,
                "debug" => $debug
            ]);
        }
        exit;

    case 'get_my_recipes': 
        $query = "SELECT r.*, u.name as author FROM recipes r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.user_id = ? ORDER BY r.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;

    case 'get_liked_recipes': 
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
        http_response_code(400); 
        echo json_encode(["status" => "error", "message" => "Aksi tidak valid atau hilang."]);
        exit;
}
?>