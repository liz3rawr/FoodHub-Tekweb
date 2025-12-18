<?php
class RecipeController {
    private $conn;
    private $table = "recipes";

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- Upload Gambar ---
    private function uploadImage($file) {
        $target_dir = "../assets/uploads/recipes/"; 
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if(!in_array($ext, $allowed)) return null;
        
        $new_name = "recipe_" . time() . "_" . rand(100,999) . "." . $ext;
        if(move_uploaded_file($file["tmp_name"], $target_dir . $new_name)) return $new_name;
        
        return null;
    }

    // --- CREATE ---
    public function create($user_id, $role, $data, $files) {
        // Cek Role
        $status = ($role === 'admin') ? 'approved' : 'pending';
        
        $imageName = null;
        if(isset($files['image']) && $files['image']['error'] == 0) { 
            $imageName = $this->uploadImage($files['image']); 
        }

        $ing = nl2br(htmlspecialchars($data['ingredients']));
        $stp = nl2br(htmlspecialchars($data['steps']));

        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, description, ingredients, steps, cooking_time, servings, category, status, image) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $params = [
            $user_id, 
            $data['title'], 
            $data['description'], 
            $ing, 
            $stp, 
            $data['cooking_time'], 
            $data['servings'], 
            $data['category'], 
            $status, 
            $imageName
        ];

        if($stmt->execute($params)) {
            $msg = ($status == 'approved') ? "Resep diterbitkan!" : "Resep dikirim! Menunggu Admin.";
            return ["status" => "success", "message" => $msg];
        }
        return ["status" => "error", "message" => "Gagal simpan database"];
    }

    // --- UPDATE ---
    public function update($user_id, $role, $data, $files) {
        $id = $data['id'];
        
        // Cek kepemilikan
        $check = $this->conn->prepare("SELECT user_id, image FROM " . $this->table . " WHERE id = ?");
        $check->execute([$id]);
        $curr = $check->fetch(PDO::FETCH_ASSOC);

        if(!$curr) return ["status" => "error", "message" => "Resep tidak ditemukan"];
        if($curr['user_id'] != $user_id && $role !== 'admin') {
            return ["status" => "error", "message" => "Bukan resep kamu!"];
        }

        // RESET STATUS
        $new_status = ($role === 'admin') ? 'approved' : 'pending';
        
        $sql = "UPDATE " . $this->table . " SET title=?, description=?, ingredients=?, steps=?, cooking_time=?, servings=?, category=?, status=?";
        $params = [
            $data['title'], 
            $data['description'], 
            nl2br($data['ingredients']), 
            nl2br($data['steps']), 
            $data['cooking_time'], 
            $data['servings'], 
            $data['category'], 
            $new_status
        ];

        // Cek Ganti Gambar
        if(isset($files['image']) && $files['image']['error'] == 0) {
            $img = $this->uploadImage($files['image']);
            if($img) {
                // Hapus gambar lama jika ada
                if($curr['image'] && file_exists("../assets/uploads/recipes/" . $curr['image'])) {
                    unlink("../assets/uploads/recipes/" . $curr['image']);
                }
                $sql .= ", image=?";
                $params[] = $img;
            }
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        if($this->conn->prepare($sql)->execute($params)) {
            return ["status" => "success", "message" => "Update berhasil!"];
        }
        return ["status" => "error", "message" => "Gagal update"];
    }

    // --- FETCH DATA ---
    public function getAll($role, $user_id, $filter_type, $search='', $cat='') {
        $sql = "SELECT r.id, r.title, r.description, r.category, r.image, r.status, r.created_at,
                u.name as author, u.photo as user_photo,
                (SELECT COUNT(*) FROM likes WHERE likes.recipe_id = r.id) as like_count,
                (SELECT count(*) FROM likes WHERE recipe_id = r.id AND user_id = ?) as is_liked
                FROM recipes r
                JOIN users u ON r.user_id = u.id
                WHERE 1=1";
        
        $params = [$user_id]; // buat subquery like

        if($filter_type === 'public') {
            $sql .= " AND r.status = 'approved'";
            if(!empty($search)) { $sql .= " AND (r.title LIKE ? OR r.ingredients LIKE ?)"; array_push($params, "%$search%", "%$search%"); }
            if(!empty($cat)) { $sql .= " AND r.category = ?"; array_push($params, $cat); }
            $sql .= " ORDER BY r.created_at DESC";
        } 
        elseif($filter_type === 'pending_admin') {
            if($role !== 'admin') return [];
            $sql .= " AND r.status = 'pending' ORDER BY r.created_at ASC";
        }
        elseif($filter_type === 'all_admin') {
            if($role !== 'admin') return [];
            $sql .= " ORDER BY FIELD(r.status, 'pending', 'approved', 'rejected'), r.created_at DESC";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- ADMIN APPROVAL ---

    public function reviewRecipe($role, $id, $action) {
        if($role !== 'admin') return ["status"=>"error", "message"=>"Unauthorized"];
        $st = ($action == 'approve') ? 'approved' : 'rejected';
        
        // Kalau reject, mau dihapus atau cuma ganti status? 
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET status=? WHERE id=?");
        
        if($stmt->execute([$st, $id])) return ["status"=>"success"];
        return ["status"=>"error"];
    }

    // --- DELETE & DETAIL ---
    public function delete($user_id, $role, $id) {
        $check = $this->conn->prepare("SELECT user_id, image FROM recipes WHERE id=?"); 
        $check->execute([$id]); 
        $data = $check->fetch();

        if(!$data) return ["status"=>"error"];
        if($data['user_id'] != $user_id && $role != 'admin') return ["status"=>"error", "message"=>"Unauthorized"];
        
        if($data['image'] && file_exists("../assets/uploads/recipes/" . $data['image'])) {
            unlink("../assets/uploads/recipes/" . $data['image']);
        }
        
        $this->conn->prepare("DELETE FROM recipes WHERE id=?")->execute([$id]);
        return ["status"=>"success", "message"=>"Dihapus"];
    }

    public function getDetail($id) {
        $stmt = $this->conn->prepare("SELECT r.*, u.name as author, u.photo as user_photo FROM recipes r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- CATEGORY ---
    public function addCategory($name) {
        $name = htmlspecialchars(strip_tags($name));

        // CEK DUPLIKAT 
        $check = $this->conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
        
        if($check->rowCount() > 0) {
            // Kalau ketemu, langsung stop dan kirim error
            return ["status" => "error", "message" => "Kategori '$name' sudah ada!"];
        }

        // Kalau aman, insert
        $stmt = $this->conn->prepare("INSERT INTO categories (name) VALUES (?)");
        if($stmt->execute([$name])) {
            return ["status" => "success", "message" => "Kategori berhasil ditambahkan"];
        }

        return ["status" => "error", "message" => "Gagal menyimpan kategori"];
    }


        public function deleteCategory($id) {
        // 1. Ambil Nama Kategori dulu (opsional, buat log)
        // $stmt = $this->conn->prepare("SELECT name FROM categories WHERE id=?");
        // $stmt->execute([$id]);
        
        // 2. Reset Resep Terkait
        
        $getCat = $this->conn->prepare("SELECT name FROM categories WHERE id=?");
        $getCat->execute([$id]);
        $catData = $getCat->fetch(PDO::FETCH_ASSOC);
        
        if($catData) {
            $catName = $catData['name'];
            
            // Update resep: status -> pending, category -> 'Uncategorized' atau kosong
            $updateRecipe = $this->conn->prepare("UPDATE recipes SET status='pending', category='' WHERE category=?");
            $updateRecipe->execute([$catName]);
        }

        // 3. Hapus Kategori dari Master Data
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = ?");
        if($stmt->execute([$id])) {
            return ["status" => "success", "message" => "Kategori dihapus, resep terkait dipindahkan ke pending."];
        }
        return ["status" => "error", "message" => "Gagal hapus kategori"];
    }
}
?>