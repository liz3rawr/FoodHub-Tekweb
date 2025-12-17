<?php
// 1. Tangkap buffer (Mencegah spasi/error text merusak JSON)
ob_start();

session_start();
include_once '../config/Database.php';

// Pastikan error PHP tidak muncul di output JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();

    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['role'] ?? 'user';

    $action = $_REQUEST['action'] ?? '';

    // ==========================================================
    // A. TAMBAH KOMENTAR
    // ==========================================================
    if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!$user_id) {
            throw new Exception("Silakan login terlebih dahulu");
        }

        $comment_text = htmlspecialchars(strip_tags($_POST['comment'] ?? ''));
        $recipe_id = $_POST['recipe_id'] ?? 0;

        if (empty($comment_text)) {
            throw new Exception("Komentar tidak boleh kosong");
        }

        $stmt = $db->prepare("INSERT INTO comments (user_id, recipe_id, comment) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $recipe_id, $comment_text])) {
            sendResponse(["status" => "success", "message" => "Komentar terkirim"]);
        } else {
            throw new Exception("Gagal kirim komentar ke database");
        }
    }

    // ==========================================================
    // B. LIST KOMENTAR (PERBAIKAN UTAMA DISINI)
    // ==========================================================
    elseif ($action == 'list') {
        $recipe_id = $_GET['recipe_id'] ?? 0;

        $stmt = $db->prepare("SELECT c.*, u.name, u.photo 
                              FROM comments c 
                              JOIN users u ON c.user_id = u.id 
                              WHERE c.recipe_id = ? 
                              ORDER BY c.created_at DESC");
        $stmt->execute([$recipe_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Jika kosong, pastikan tetap array
        if (!$comments) {
            $comments = [];
        }

        // Loop dengan referensi (&)
        foreach ($comments as &$row) {
            // 1. Pastikan kedua ID diubah jadi (int) agar perbandingan akurat
            $current_user_id = (int)$user_id; 
            $owner_id        = (int)$row['user_id']; 

            // 2. Logic Pengecekan
            $is_owner = ($current_user_id !== 0 && $current_user_id === $owner_id);
            $is_admin = ($user_role === 'admin');

            // 3. Set flag can_delete
            $row['can_delete'] = ($is_owner || $is_admin);

            // 4. Debugging (Nanti bisa dihapus) -> Ini biar kita tau data apa yang dibaca
            $row['debug_info'] = "Login: $current_user_id vs Owner: $owner_id | Role: $user_role";

            // Format tanggal
            if (!empty($row['created_at'])) {
                $row['created_at'] = date('d M Y, H:i', strtotime($row['created_at']));
            } else {
                $row['created_at'] = '-';
            }
        }
        unset($row);

        // Kirim data bersih
        sendResponse($comments);
    }

    // ==========================================================
    // C. HAPUS KOMENTAR
    // ==========================================================
    elseif ($action == 'delete' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!$user_id) throw new Exception("Akses ditolak");

        $comment_id = $_POST['comment_id'] ?? 0;

        // Cek pemilik
        $stmtCheck = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmtCheck->execute([$comment_id]);
        $data = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$data) throw new Exception("Komentar tidak ditemukan");

        // Validasi Server Side
        if ($data['user_id'] == $user_id || $user_role == 'admin') {
            $stmtDel = $db->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmtDel->execute([$comment_id])) {
                sendResponse(["status" => "success"]);
            } else {
                throw new Exception("Gagal menghapus");
            }
        } else {
            throw new Exception("Anda tidak berhak menghapus ini");
        }
    } else {
        throw new Exception("Action tidak valid");
    }

} catch (Exception $e) {
    sendResponse(["status" => "error", "message" => $e->getMessage()]);
}

// Fungsi Helper untuk Output Bersih
function sendResponse($data) {
    ob_clean(); // Hapus semua output sampah sebelumnya (spasi/warning)
    echo json_encode($data);
    exit;
}
?>