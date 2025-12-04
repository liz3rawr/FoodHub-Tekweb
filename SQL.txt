USE db_foodhub;


-- 1. Tabel Users (pengguna & admin di tabel yang sama, dibedakan dari rolenya)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    bio TEXT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- 2. Tabel Categories (kategori masakan)

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Secara default ada kategori2 ini
INSERT IGNORE INTO categories (name) VALUES 
('Asian Food'), 
('Western Food'), 
('Dessert'), 
('Spicy'), 
('Soup'), 
('Beverage'), 
('Healthy'),
('Seafood');


-- 3. Tabel Recipes (info lengkap resep)

CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    ingredients TEXT,          -- Bahan-bahan
    steps TEXT,                -- Langkah pembuatan
    cooking_time VARCHAR(50),  -- Estimasi waktu (misal: 30 Menit)
    servings VARCHAR(50),      -- Porsi untuk berapa orang
    category VARCHAR(50),      -- Nama kategori
    image VARCHAR(255) DEFAULT NULL, -- Nama file foto
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- 4. Tabel Likes (menyimpan like, dari user mana ke resep mana)

CREATE TABLE IF NOT EXISTS likes (
    user_id INT,
    recipe_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id), -- Mencegah 1 user like 2x di resep sama
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
);


-- 5. Table Comments (mencatat dari user mana di resep mana)

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    recipe_id INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
);