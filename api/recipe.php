<?php
session_start();
header('Content-Type: application/json');

include_once '../config/Database.php';
include_once '../controllers/RecipeController.php'; 

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
        $check = $db->prepare("SELECT * FROM likes WHERE user_id=? AND recipe_id=?"); 
        $check->execute([$user_id, $_POST['recipe_id']]);
        if($check->rowCount() > 0) { 
            $db->prepare("DELETE FROM likes WHERE user_id=? AND recipe_id=?")->execute([$user_id, $_POST['recipe_id']]); 
            echo json_encode(["status"=>"unliked"]); 
        } else { 
            $db->prepare("INSERT INTO likes (user_id, recipe_id) VALUES (?,?)")->execute([$user_id, $_POST['recipe_id']]); 
            echo json_encode(["status"=>"liked"]); 
        }
        break;

    default:
        echo json_encode(["status"=>"error", "message"=>"Action invalid"]);
}
?>