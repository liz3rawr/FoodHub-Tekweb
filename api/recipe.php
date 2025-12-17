<?php
session_start();
header('Content-Type: application/json');

include_once '../config/Database.php';
include_once '../controllers/RecipeController.php'; 

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    // Jika bukan dari AJAX, tendang user kembali ke halaman tampilan
    header("Location: ../index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$controller = new RecipeController($db); 

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch($action) {
    case 'create':
        if($_SERVER['REQUEST_METHOD'] !== 'POST') break;
        echo json_encode($controller->create($user_id, $user_role, $_POST, $_FILES));
        break;

    case 'update':
        if($_SERVER['REQUEST_METHOD'] !== 'POST') break;
        echo json_encode($controller->update($user_id, $user_role, $_POST, $_FILES));
        break;

    case 'read':
        $search = $_GET['search'] ?? '';
        $cat = (isset($_GET['category']) && $_GET['category'] != '') ? $_GET['category'] : '';
        echo json_encode($controller->getAll($user_role, $user_id, 'public', $search, $cat));
        break;

    case 'read_pending':
        echo json_encode($controller->getAll($user_role, $user_id, 'pending_admin'));
        break;

    case 'read_all_admin':
        echo json_encode($controller->getAll($user_role, $user_id, 'all_admin'));
        break;

    case 'get_detail':
        echo json_encode($controller->getDetail($_GET['id']));
        break;

    case 'delete':
        echo json_encode($controller->delete($user_id, $user_role, $_POST['id']));
        break;

    case 'approve':
    case 'reject':
        echo json_encode($controller->reviewRecipe($user_role, $_POST['id'], $action));
        break;

    case 'get_categories': 
        echo json_encode($db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'add_category': 
        if($user_role == 'admin') { 
            echo json_encode($controller->addCategory($_POST['name']));
        } else {
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        }
        break;
        
    case 'delete_category':
        if($user_role == 'admin') {
            echo json_encode($controller->deleteCategory($_POST['id']));
        }
        break;

    case 'toggle_like':
        $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
        if($user_id == 0 || $recipe_id == 0) {
            echo json_encode(["status"=>"error", "message"=>"Unauthorized or invalid recipe id"]);
            break;
        }

        try {
            $db->beginTransaction();
            $check = $db->prepare("SELECT 1 FROM likes WHERE user_id=? AND recipe_id=? LIMIT 1");
            $check->execute([$user_id, $recipe_id]);
            if($check->rowCount() > 0) {
                $del = $db->prepare("DELETE FROM likes WHERE user_id=? AND recipe_id=?");
                $del->execute([$user_id, $recipe_id]);
                $action = 'unliked';
            } else {
                $ins = $db->prepare("INSERT INTO likes (user_id, recipe_id) VALUES (?,?)");
                $ins->execute([$user_id, $recipe_id]);
                $action = 'liked';
            }
            // get updated count
            $cnt = $db->prepare("SELECT COUNT(*) as c FROM likes WHERE recipe_id=?");
            $cnt->execute([$recipe_id]);
            $row = $cnt->fetch(PDO::FETCH_ASSOC);
            $like_count = isset($row['c']) ? intval($row['c']) : 0;

            $db->commit();
            echo json_encode(["status"=>"success", "action"=>$action, "like_count"=>$like_count, "recipe_id"=>$recipe_id]);
        } catch(Exception $e) {
            if($db->inTransaction()) $db->rollBack();
            echo json_encode(["status"=>"error", "message"=>"Database error"]);
        }
        break;

    default:
        echo json_encode(["status"=>"error", "message"=>"Action invalid"]);
}
?>