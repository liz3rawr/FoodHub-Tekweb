<?php
    session_start();
    include_once '../config/Database.php';

    // Header JSON & Koneksi
    header('Content-Type: application/json');
    $database = new Database();
    $db = $database->getConnection();

    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    // --- HELPER UPLOAD GAMBAR ---
    function uploadImage($file) {
        $target_dir = dirname(__DIR__) . "/assets/uploads/recipes/";
        if (!is_dir($target_dir)) { if (!mkdir($target_dir, 0777, true)) return null; }
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if(!in_array($ext, $allowed)) return null;
        $new_name = "recipe_" . time() . "_" . rand(100,999) . "." . $ext;
        if(move_uploaded_file($file["tmp_name"], $target_dir . $new_name)) return $new_name;
        return null;
    }

    // 1. CREATE (BUAT RESEP)
    if($action == 'create' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $status = ($user_role == 'admin') ? 'approved' : 'pending';
        $imageName = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) { $imageName = uploadImage($_FILES['image']); }

        $ing = nl2br(htmlspecialchars($_POST['ingredients']));
        $stp = nl2br(htmlspecialchars($_POST['steps']));

        $sql = "INSERT INTO recipes (user_id, title, description, ingredients, steps, cooking_time, servings, category, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        if($stmt->execute([$user_id, $_POST['title'], $_POST['description'], $ing, $stp, $_POST['cooking_time'], $_POST['servings'], $_POST['category'], $status, $imageName])) {
            echo json_encode(["status" => "success", "message" => ($status == 'approved' ? "Resep berhasil diterbitkan!" : "Resep dikirim! Menunggu persetujuan Admin.")]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan data."]);
        }
    }

    // 2. READ (SEARCH & FILTER - PUBLIC)
    if($action == 'read') {
        $search = isset($_GET['search']) ? "%".$_GET['search']."%" : "%%";
        $category = (isset($_GET['category']) && $_GET['category'] != '') ? $_GET['category'] : "%";

        $query = "SELECT r.id, r.title, r.description, r.category, r.image, r.status, 
                u.name as author, u.photo as user_photo,
                (SELECT COUNT(*) FROM likes WHERE likes.recipe_id = r.id) as like_count,
                (SELECT count(*) FROM likes WHERE recipe_id = r.id AND user_id = ?) as is_liked
                FROM recipes r
                JOIN users u ON r.user_id = u.id
                WHERE (r.title LIKE ? OR r.ingredients LIKE ?) 
                AND r.category LIKE ? 
                AND r.status = 'approved' 
                ORDER BY r.created_at DESC";
                
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $search, $search, $category]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 3. READ PENDING (ADMIN) 
    if($action == 'read_pending' && $user_role == 'admin') {
        $sql = "SELECT r.*, u.name as author, u.photo as user_photo 
                FROM recipes r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.status = 'pending' 
                ORDER BY r.created_at ASC";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 4. READ ALL 
    if($action == 'read_all_admin' && $user_role == 'admin') {
        $sql = "SELECT r.*, u.name as author, u.photo as user_photo 
                FROM recipes r 
                JOIN users u ON r.user_id = u.id 
                ORDER BY FIELD(r.status, 'pending', 'approved', 'rejected'), r.created_at DESC";
        $stmt = $db->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 5. GET DETAIL
    if($action == 'get_detail') {
        $stmt = $db->prepare("SELECT r.*, u.name as author, u.photo as user_photo FROM recipes r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    // 6. UPDATE (EDIT RESEP)
    if($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $check = $db->prepare("SELECT user_id FROM recipes WHERE id = ?"); $check->execute([$id]);
        $data = $check->fetch();

        if($data['user_id'] != $user_id && $user_role != 'admin') { echo json_encode(["status" => "error", "message" => "Unauthorized"]); exit; }

        $new_status = ($user_role == 'admin') ? 'approved' : 'pending';
        $sql = "UPDATE recipes SET title=?, description=?, ingredients=?, steps=?, cooking_time=?, servings=?, category=?, status=? WHERE id=?";
        $params = [$_POST['title'], $_POST['description'], nl2br($_POST['ingredients']), nl2br($_POST['steps']), $_POST['cooking_time'], $_POST['servings'], $_POST['category'], $new_status, $id];

        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $img = uploadImage($_FILES['image']);
            if($img) {
                $sql = str_replace("WHERE id=?", ", image=? WHERE id=?", $sql);
                $params = [$_POST['title'], $_POST['description'], nl2br($_POST['ingredients']), nl2br($_POST['steps']), $_POST['cooking_time'], $_POST['servings'], $_POST['category'], $new_status, $img, $id];
            }
        }
        
        if($db->prepare($sql)->execute($params)) echo json_encode(["status" => "success", "message" => "Resep diperbarui."]);
        else echo json_encode(["status" => "error", "message" => "Gagal update."]);
    }

    // 7. DELETE RESEP
    if($action == 'delete' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $check = $db->prepare("SELECT user_id, image FROM recipes WHERE id=?"); $check->execute([$id]); $data = $check->fetch();
        if($data['user_id'] != $user_id && $user_role != 'admin') { echo json_encode(["status"=>"error"]); exit; }
        if($data['image'] && file_exists(dirname(__DIR__) . "/assets/uploads/recipes/" . $data['image'])) unlink(dirname(__DIR__) . "/assets/uploads/recipes/" . $data['image']);
        $db->prepare("DELETE FROM recipes WHERE id=?")->execute([$id]);
        echo json_encode(["status"=>"success", "message"=>"Dihapus"]);
    }

    // 8. ADMIN ACTIONS
    if(($action == 'approve' || $action == 'reject') && $user_role == 'admin') {
        $st = ($action == 'approve') ? 'approved' : 'rejected';
        $db->prepare("UPDATE recipes SET status=? WHERE id=?")->execute([$st, $_POST['id']]); 
        echo json_encode(["status"=>"success"]);
    }
    if($action == 'get_categories') echo json_encode($db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC));
    if($action == 'add_category' && $user_role == 'admin') { $db->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$_POST['name']]); echo json_encode(["status"=>"success"]); }
    if($action == 'delete_category' && $user_role == 'admin') { $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$_POST['id']]); echo json_encode(["status"=>"success"]); }

    // 9. LIKE
    if($action == 'toggle_like') {
        $check = $db->prepare("SELECT * FROM likes WHERE user_id=? AND recipe_id=?"); $check->execute([$user_id, $_POST['recipe_id']]);
        if($check->rowCount() > 0) { $db->prepare("DELETE FROM likes WHERE user_id=? AND recipe_id=?")->execute([$user_id, $_POST['recipe_id']]); echo json_encode(["status"=>"unliked"]); } 
        else { $db->prepare("INSERT INTO likes (user_id, recipe_id) VALUES (?,?)")->execute([$user_id, $_POST['recipe_id']]); echo json_encode(["status"=>"liked"]); }
    }
?>