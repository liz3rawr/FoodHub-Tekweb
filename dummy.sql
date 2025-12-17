-- 1. HAPUS DATA LAMA (AMAN DARI ERROR FOREIGN KEY) --
DELETE FROM likes;
DELETE FROM comments;
DELETE FROM recipes;
DELETE FROM users;

-- 2. RESET URUTAN ID JADI 1 LAGI --
ALTER TABLE likes AUTO_INCREMENT = 1;
ALTER TABLE comments AUTO_INCREMENT = 1;
ALTER TABLE recipes AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

-- 3. INSERT USER BARU (admin, user1, user2) --
-- Password untuk semua akun di bawah ini adalah: password
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `bio`, `photo`, `created_at`) VALUES
(1, 'admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator Website.', '', NOW()),
(2, 'user1', 'user1@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Hobi masak makanan berat.', 'profile_user1.jpg', NOW()),
(3, 'user2', 'user2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Suka jajan dan bikin minuman.', NULL, NOW());


-- 4. INSERT RESEP DUMMY --

-- KATEGORI: ASIAN FOOD --
-- Resep 1 (Ada Foto - user1)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(2, 'Nasi Goreng Kampung Spesial', 'Nasi goreng dengan bumbu terasi khas kampung yang pedas dan gurih.', 'Asian Food', '2 piring nasi putih\n2 butir telur\n3 siung bawang merah\n2 siung bawang putih\n1 sdt terasi bakar\nKecap manis secukupnya', '1. Haluskan bawang dan terasi.\n2. Tumis bumbu hingga harum.\n3. Masukkan telur, orak-arik.\n4. Masukkan nasi dan kecap, aduk rata sampai matang.', '15 Menit', '2 Porsi', 'nasigoreng.jpg', 'approved', NOW());

-- Resep 2 (TIDAK Ada Foto - user2)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(3, 'Telur Dadar Padang Ekonomis', 'Telur dadar tebal ala rumah makan padang versi murah meriah.', 'Asian Food', '3 butir telur ayam\n1 batang daun bawang\n2 sdm tepung beras\nCabe merah giling secukupnya\nGaram & penyedap', '1. Kocok telur bersama semua bahan.\n2. Panaskan minyak agak banyak.\n3. Goreng telur dengan api sedang sampai mengembang.\n4. Balik telur hati-hati agar tidak hancur.', '10 Menit', '1 Porsi', NULL, 'approved', NOW());


-- KATEGORI: BEVERAGE --
-- Resep 3 (Ada Foto - user2)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(3, 'Es Teh Tarik Warung', 'Minuman segar pelepas dahaga, manis dan creamy.', 'Beverage', '1 kantong teh celup\n2 sdm susu kental manis\nAir panas\nEs batu secukupnya', '1. Seduh teh dengan air panas (sedikit saja biar pekat).\n2. Campur dengan susu kental manis, aduk rata.\n3. Tarik-tarik (tuang antar gelas) sampai berbusa.\n4. Tambahkan es batu.', '5 Menit', '1 Gelas', 'tehtarik.jpg', 'approved', NOW());

-- Resep 4 (TIDAK Ada Foto - user1)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(2, 'Wedang Jahe Merah', 'Minuman herbal hangat untuk meningkatkan imun tubuh.', 'Beverage', '2 ruas jahe merah (geprek)\n1 batang serai\n1 butir gula merah\n500ml air', '1. Bakar jahe sebentar lalu geprek.\n2. Rebus air, masukkan jahe, serai, dan gula merah.\n3. Masak hingga mendidih dan air menyusut sedikit.\n4. Saring dan sajikan hangat.', '20 Menit', '2 Gelas', '', 'approved', NOW());


-- KATEGORI: DESSERT --
-- Resep 5 (Ada Foto - user1)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(2, 'Pudding Coklat Lumer', 'Pudding lembut dengan saus vla vanilla yang creamy.', 'Dessert', '1 bungkus agar-agar coklat\n1 liter susu cair coklat\n100gr dark chocolate\nGula pasir secukupnya', '1. Campur agar-agar, susu, dan gula. Masak sambil diaduk.\n2. Masukkan dark chocolate, aduk sampai leleh.\n3. Tuang ke cetakan, dinginkan di kulkas.\n4. Sajikan dingin.', '3 Jam', '4 Porsi', 'pudding.jpg', 'approved', NOW());

-- Resep 6 (TIDAK Ada Foto - user2)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(3, 'Pisang Goreng Keju Susu', 'Cemilan sore hari yang manis dan gurih.', 'Dessert', '5 buah pisang kepok\nTepung pisang goreng instan\nKeju parut\nSusu kental manis', '1. Kupas pisang, balur dengan adonan tepung.\n2. Goreng hingga kecoklatan.\n3. Angkat, beri topping parutan keju dan susu kental manis.', '15 Menit', '2 Porsi', NULL, 'approved', NOW());


-- KATEGORI: SOUP --
-- Resep 7 (Ada Foto - user1)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(2, 'Soto Ayam Lamongan', 'Soto ayam dengan kuah kuning segar dan bubuk koya.', 'Soup', '1/2 ekor ayam\nBumbu soto instan (biar cepat)\nSoun, kol, tauge\nTelur rebus\nKerupuk udang (untuk koya)', '1. Rebus ayam hingga empuk, suwir dagingnya.\n2. Tumis bumbu soto, masukkan ke air rebusan ayam.\n3. Tata soun, kol, tauge di mangkok.\n4. Siram kuah panas, beri koya.', '45 Menit', '4 Porsi', 'sotoayam.jpg', 'approved', NOW());

-- Resep 8 (TIDAK Ada Foto - user2)
INSERT INTO `recipes` (`user_id`, `title`, `description`, `category`, `ingredients`, `steps`, `cooking_time`, `servings`, `image`, `status`, `created_at`) VALUES
(3, 'Sayur Sop Bening', 'Sop rumahan sederhana dengan sayuran segar.', 'Soup', '1 buah wortel\n1 buah kentang\n5 butir bakso sapi\nDaun bawang & seledri\nBawang putih geprek', '1. Didihkan air, masukkan bawang putih geprek.\n2. Masukkan wortel dan kentang sampai empuk.\n3. Masukkan bakso dan bumbu (garam, lada).\n4. Terakhir masukkan daun bawang seledri.', '20 Menit', '3 Porsi', '', 'approved', NOW());