<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login() {
        $query = "SELECT id, name, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                return true;
            }
        }
        return false;
    }
    
    // Fungsi Register bisa ditambahkan di sini
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " (name, email, password) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
    
        // Enkripsi password sebelum disimpan (Security Requirement)
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
    
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->email);
        $stmt->bindParam(3, $hashed_password);
    
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>