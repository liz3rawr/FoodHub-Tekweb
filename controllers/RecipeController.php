<?php
include_once '../models/Recipe.php'; // Pastikan file Model Recipe ada

class RecipeController {
    private $db;
    private $recipeModel;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->recipeModel = new Recipe($this->db); // Asumsi Class Recipe sudah ada di Models
    }

    public function createRecipe($userId, $data) {
        // Validasi sederhana
        if(empty($data['title']) || empty($data['description'])) {
            return ["status" => "error", "message" => "Data tidak lengkap"];
        }

        // Set properti model (setter manual atau langsung)
        $this->recipeModel->user_id = $userId;
        $this->recipeModel->title = $data['title'];
        $this->recipeModel->description = $data['description'];
        $this->recipeModel->category = $data['category'];

        if($this->recipeModel->create()) {
            return ["status" => "success", "message" => "Resep berhasil dibuat"];
        }
        return ["status" => "error", "message" => "Gagal menyimpan resep"];
    }

    public function getAllRecipes($keyword = "") {
        $stmt = $this->recipeModel->read($keyword);
        $recipes = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Kita format HTML di sini atau kirim JSON murni (lebih baik JSON untuk API)
            $recipes[] = $row;
        }
        return $recipes;
    }
}
?>