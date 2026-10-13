-- =============================================
-- HỆ THỐNG ĐẶT VÉ XEM PHIM ONLINE - Phiên bản 2.0
-- Cập nhật: Hold ghế, Timeout thanh toán, Nhật ký
-- =============================================

CREATE DATABASE IF NOT EXISTS dat_ve_xem_phim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dat_ve_xem_phim;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS nhat_ky;
DROP TABLE IF EXISTS ghe_giu;
DROP TABLE IF EXISTS thanh_toan;
DROP TABLE IF EXISTS chi_tiet_ve;
DROP TABLE IF EXISTS ve;
DROP TABLE IF EXISTS ma_giam_gia;
DROP TABLE IF EXISTS suat_chieu;
DROP TABLE IF EXISTS ghe;
DROP TABLE IF EXISTS phong_chieu;
DROP TABLE IF EXISTS rap;
DROP TABLE IF EXISTS phim;
DROP TABLE IF EXISTS the_loai;
DROP TABLE IF EXISTS nguoi_dung;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE nguoi_dung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    so_dien_thoai VARCHAR(15) NOT NULL,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    vai_tro ENUM('khach', 'admin') DEFAULT 'khach',
    trang_thai ENUM('hoat_dong', 'khoa') DEFAULT 'hoat_dong',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE the_loai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_the_loai VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE phim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phim VARCHAR(200) NOT NULL,
    the_loai_id INT,
    thoi_luong INT NOT NULL,
    ngay_khoi_chieu DATE NOT NULL,
    dao_dien VARCHAR(100),
    dien_vien TEXT,
    mo_ta TEXT,
    poster VARCHAR(255) DEFAULT 'default.jpg',
    trailer VARCHAR(255),
    trang_thai ENUM('dang_chieu', 'sap_chieu', 'ngung_chieu') DEFAULT 'dang_chieu',
    FOREIGN KEY (the_loai_id) REFERENCES the_loai(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE rap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_rap VARCHAR(100) NOT NULL,
    dia_chi VARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(15),
    trang_thai ENUM('hoat_dong', 'ngung') DEFAULT 'hoat_dong'
) ENGINE=InnoDB;

CREATE TABLE phong_chieu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rap_id INT NOT NULL,
    ten_phong VARCHAR(50) NOT NULL,
    so_hang INT NOT NULL DEFAULT 8,
    so_cot INT NOT NULL DEFAULT 10,
    FOREIGN KEY (rap_id) REFERENCES rap(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ghe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phong_id INT NOT NULL,
    ma_ghe VARCHAR(10) NOT NULL,
    loai_ghe ENUM('thuong', 'vip', 'doi') DEFAULT 'thuong',
    trang_thai ENUM('hoat_dong', 'bao_tri') DEFAULT 'hoat_dong',
    UNIQUE KEY unique_ghe_phong (phong_id, ma_ghe),
    FOREIGN KEY (phong_id) REFERENCES phong_chieu(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE suat_chieu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phim_id INT NOT NULL,
    phong_id INT NOT NULL,
    ngay_chieu DATE NOT NULL,
    gio_bat_dau TIME NOT NULL,
    gio_ket_thuc TIME NOT NULL,
    gia_ve_co_ban DECIMAL(10,0) NOT NULL DEFAULT 70000,
    trang_thai ENUM('hoat_dong', 'huy', 'ket_thuc') DEFAULT 'hoat_dong',
    FOREIGN KEY (phim_id) REFERENCES phim(id) ON DELETE CASCADE,
    FOREIGN KEY (phong_id) REFERENCES phong_chieu(id) ON DELETE CASCADE,
    INDEX idx_ngay_phong (ngay_chieu, phong_id)
) ENGINE=InnoDB;

CREATE TABLE ma_giam_gia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_code VARCHAR(20) NOT NULL UNIQUE,
    loai_giam ENUM('phan_tram', 'so_tien') DEFAULT 'phan_tram',
    gia_tri DECIMAL(10,0) NOT NULL,
    ngay_bat_dau DATE NOT NULL,
    ngay_ket_thuc DATE NOT NULL,
    so_lan_su_dung INT DEFAULT 100,
    da_su_dung INT DEFAULT 0,
    trang_thai ENUM('hoat_dong', 'het_han', 'khoa') DEFAULT 'hoat_dong'
) ENGINE=InnoDB;

CREATE TABLE ve (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_ve VARCHAR(20) NOT NULL UNIQUE,
    nguoi_dung_id INT NOT NULL,
    suat_chieu_id INT NOT NULL,
    tong_tien DECIMAL(12,0) NOT NULL,
    ma_giam_gia_id INT NULL,
    so_tien_giam DECIMAL(10,0) DEFAULT 0,
    trang_thai ENUM('cho_thanh_toan', 'da_thanh_toan', 'da_su_dung', 'da_huy') DEFAULT 'cho_thanh_toan',
    ngay_dat DATETIME DEFAULT CURRENT_TIMESTAMP,
    het_han_thanh_toan DATETIME NULL,
    ngay_thanh_toan DATETIME NULL,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id),
    FOREIGN KEY (suat_chieu_id) REFERENCES suat_chieu(id),
    FOREIGN KEY (ma_giam_gia_id) REFERENCES ma_giam_gia(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE chi_tiet_ve (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ve_id INT NOT NULL,
    ghe_id INT NOT NULL,
    gia_ghe DECIMAL(10,0) NOT NULL,
    FOREIGN KEY (ve_id) REFERENCES ve(id) ON DELETE CASCADE,
    FOREIGN KEY (ghe_id) REFERENCES ghe(id),
    UNIQUE KEY unique_ghe_ve (ve_id, ghe_id)
) ENGINE=InnoDB;

CREATE TABLE thanh_toan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ve_id INT NOT NULL,
    phuong_thuc ENUM('tien_mat', 'chuyen_khoan', 'the', 'mo_phong') DEFAULT 'mo_phong',
    so_tien DECIMAL(12,0) NOT NULL,
    trang_thai ENUM('thanh_cong', 'that_bai', 'huy', 'cho') DEFAULT 'cho',
    ma_giao_dich VARCHAR(50),
    ngay_thanh_toan DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ve_id) REFERENCES ve(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ghe_giu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suat_chieu_id INT NOT NULL,
    ghe_id INT NOT NULL,
    nguoi_dung_id INT NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    thoi_gian_giu DATETIME DEFAULT CURRENT_TIMESTAMP,
    het_han DATETIME NOT NULL,
    UNIQUE KEY unique_hold (suat_chieu_id, ghe_id),
    FOREIGN KEY (suat_chieu_id) REFERENCES suat_chieu(id) ON DELETE CASCADE,
    FOREIGN KEY (ghe_id) REFERENCES ghe(id) ON DELETE CASCADE,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE nhat_ky (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT NULL,
    hanh_dong VARCHAR(100) NOT NULL,
    chi_tiet TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hanh_dong (hanh_dong),
    INDEX idx_ngay (ngay_tao)
) ENGINE=InnoDB;

-- Dữ liệu mẫu
INSERT INTO nguoi_dung (ho_ten, email, so_dien_thoai, ten_dang_nhap, mat_khau, vai_tro) VALUES
('Admin Hệ Thống', 'admin@cinema.vn', '0901234567', 'admin', '$2y$10$YgnJ21iFgVJ5fjc2aB9n5eY8dYiyQcyto7QzP1g8BthNP3bxGezv2', 'admin'),
('Nguyễn Văn A', 'nguyenvana@gmail.com', '0912345678', 'nguyenvana', '$2y$10$YgnJ21iFgVJ5fjc2aB9n5eY8dYiyQcyto7QzP1g8BthNP3bxGezv2', 'khach'),
('Trần Thị B', 'tranthib@gmail.com', '0923456789', 'tranthib', '$2y$10$YgnJ21iFgVJ5fjc2aB9n5eY8dYiyQcyto7QzP1g8BthNP3bxGezv2', 'khach'),
('Lê Văn C', 'levanc@gmail.com', '0934567890', 'levanc', '$2y$10$YgnJ21iFgVJ5fjc2aB9n5eY8dYiyQcyto7QzP1g8BthNP3bxGezv2', 'khach'),
('Phạm Thị D', 'phamthid@gmail.com', '0945678901', 'phamthid', '$2y$10$YgnJ21iFgVJ5fjc2aB9n5eY8dYiyQcyto7QzP1g8BthNP3bxGezv2', 'khach');

INSERT INTO the_loai (ten_the_loai) VALUES
('Hành động'), ('Tình cảm'), ('Hài'), ('Kinh dị'), ('Khoa học viễn tưởng'), ('Hoạt hình'), ('Phiêu lưu');

INSERT INTO phim (ten_phim, the_loai_id, thoi_luong, ngay_khoi_chieu, dao_dien, dien_vien, mo_ta, poster, trang_thai) VALUES
('Avengers: Endgame', 1, 181, '2025-04-26', 'Anthony Russo, Joe Russo', 'Robert Downey Jr., Chris Evans, Scarlett Johansson', 'Các siêu anh hùng tập hợp để cứu vũ trụ khỏi Thanos.', 'avengers.jpg', 'dang_chieu'),
('Spider-Man: No Way Home', 1, 148, '2025-12-17', 'Jon Watts', 'Tom Holland, Zendaya, Benedict Cumberbatch', 'Peter Parker đối mặt với đa vũ trụ.', 'spiderman.jpg', 'dang_chieu'),
('Dune: Part Two', 5, 166, '2025-03-01', 'Denis Villeneuve', 'Timothée Chalamet, Zendaya, Rebecca Ferguson', 'Paul Atreides tiếp tục hành trình trên hành tinh Arrakis.', 'dune.jpg', 'dang_chieu'),
('Inside Out 2', 6, 96, '2025-06-14', 'Kelsey Mann', 'Amy Poehler, Maya Hawke', 'Những cảm xúc mới xuất hiện trong tâm trí Riley.', 'insideout2.jpg', 'dang_chieu'),
('The Batman', 1, 176, '2025-03-04', 'Matt Reeves', 'Robert Pattinson, Zoë Kravitz', 'Batman đối đầu với Riddler ở Gotham.', 'batman.jpg', 'dang_chieu'),
('Oppenheimer', 1, 180, '2025-07-21', 'Christopher Nolan', 'Cillian Murphy, Emily Blunt', 'Câu chuyện về cha đẻ của bom nguyên tử.', 'oppenheimer.jpg', 'sap_chieu'),
('Barbie', 3, 114, '2025-07-21', 'Greta Gerwig', 'Margot Robbie, Ryan Gosling', 'Barbie bước vào thế giới thực.', 'barbie.jpg', 'sap_chieu'),
('Jujutsu Kaisen 0', 6, 105, '2025-12-24', 'Sunghoo Park', 'Megumi Ogata, Yuichi Nakamura', 'Câu chuyện về Yuta Okkotsu.', 'jjk.jpg', 'dang_chieu');

INSERT INTO rap (ten_rap, dia_chi, so_dien_thoai) VALUES
('CGV Vincom Center', '72 Lê Thánh Tôn, Quận 1, TP.HCM', '19006017'),
('CGV Aeon Mall Tân Phú', '30 Bờ Bao Tân Thắng, Tân Phú, TP.HCM', '19006017'),
('Lotte Cinema Nam Sài Gòn', 'Tầng 5, Lotte Mart, Quận 7, TP.HCM', '19006789'),
('Galaxy Nguyễn Du', '116 Nguyễn Du, Quận 1, TP.HCM', '19002224');

INSERT INTO phong_chieu (rap_id, ten_phong, so_hang, so_cot) VALUES
(1, 'Phòng 1', 8, 10),(1, 'Phòng 2', 6, 8),(1, 'Phòng VIP', 5, 6),
(2, 'Phòng 1', 8, 10),(2, 'Phòng 2', 7, 9),
(3, 'Phòng 1', 8, 10),(3, 'Phòng 2', 6, 8),(4, 'Phòng 1', 8, 10);

INSERT INTO ghe (phong_id, ma_ghe, loai_ghe) VALUES
(1,'A1','thuong'),(1,'A2','thuong'),(1,'A3','thuong'),(1,'A4','thuong'),(1,'A5','thuong'),(1,'A6','thuong'),(1,'A7','thuong'),(1,'A8','thuong'),(1,'A9','thuong'),(1,'A10','thuong'),
(1,'B1','thuong'),(1,'B2','thuong'),(1,'B3','thuong'),(1,'B4','thuong'),(1,'B5','thuong'),(1,'B6','thuong'),(1,'B7','thuong'),(1,'B8','thuong'),(1,'B9','thuong'),(1,'B10','thuong'),
(1,'C1','thuong'),(1,'C2','thuong'),(1,'C3','thuong'),(1,'C4','thuong'),(1,'C5','thuong'),(1,'C6','thuong'),(1,'C7','thuong'),(1,'C8','thuong'),(1,'C9','thuong'),(1,'C10','thuong'),
(1,'D1','vip'),(1,'D2','vip'),(1,'D3','vip'),(1,'D4','vip'),(1,'D5','vip'),(1,'D6','vip'),(1,'D7','vip'),(1,'D8','vip'),(1,'D9','vip'),(1,'D10','vip'),
(1,'E1','vip'),(1,'E2','vip'),(1,'E3','vip'),(1,'E4','vip'),(1,'E5','vip'),(1,'E6','vip'),(1,'E7','vip'),(1,'E8','vip'),(1,'E9','vip'),(1,'E10','vip'),
(1,'F1','thuong'),(1,'F2','thuong'),(1,'F3','thuong'),(1,'F4','thuong'),(1,'F5','thuong'),(1,'F6','thuong'),(1,'F7','thuong'),(1,'F8','thuong'),(1,'F9','thuong'),(1,'F10','thuong'),
(1,'G1','thuong'),(1,'G2','thuong'),(1,'G3','thuong'),(1,'G4','thuong'),(1,'G5','thuong'),(1,'G6','thuong'),(1,'G7','thuong'),(1,'G8','thuong'),(1,'G9','thuong'),(1,'G10','thuong'),
(1,'H1','doi'),(1,'H2','doi'),(1,'H3','doi'),(1,'H4','doi'),(1,'H5','doi'),(1,'H6','doi'),(1,'H7','doi'),(1,'H8','doi'),(1,'H9','doi'),(1,'H10','doi');

INSERT INTO ghe (phong_id, ma_ghe, loai_ghe) VALUES
(2,'A1','thuong'),(2,'A2','thuong'),(2,'A3','thuong'),(2,'A4','thuong'),(2,'A5','thuong'),(2,'A6','thuong'),(2,'A7','thuong'),(2,'A8','thuong'),
(2,'B1','thuong'),(2,'B2','thuong'),(2,'B3','thuong'),(2,'B4','thuong'),(2,'B5','thuong'),(2,'B6','thuong'),(2,'B7','thuong'),(2,'B8','thuong'),
(2,'C1','vip'),(2,'C2','vip'),(2,'C3','vip'),(2,'C4','vip'),(2,'C5','vip'),(2,'C6','vip'),(2,'C7','vip'),(2,'C8','vip'),
(2,'D1','vip'),(2,'D2','vip'),(2,'D3','vip'),(2,'D4','vip'),(2,'D5','vip'),(2,'D6','vip'),(2,'D7','vip'),(2,'D8','vip'),
(2,'E1','thuong'),(2,'E2','thuong'),(2,'E3','thuong'),(2,'E4','thuong'),(2,'E5','thuong'),(2,'E6','thuong'),(2,'E7','thuong'),(2,'E8','thuong'),
(2,'F1','doi'),(2,'F2','doi'),(2,'F3','doi'),(2,'F4','doi'),(2,'F5','doi'),(2,'F6','doi'),(2,'F7','doi'),(2,'F8','doi');

INSERT INTO ghe (phong_id, ma_ghe, loai_ghe) VALUES
(3,'A1','vip'),(3,'A2','vip'),(3,'A3','vip'),(3,'A4','vip'),(3,'A5','vip'),(3,'A6','vip'),
(3,'B1','vip'),(3,'B2','vip'),(3,'B3','vip'),(3,'B4','vip'),(3,'B5','vip'),(3,'B6','vip'),
(3,'C1','vip'),(3,'C2','vip'),(3,'C3','vip'),(3,'C4','vip'),(3,'C5','vip'),(3,'C6','vip'),
(3,'D1','doi'),(3,'D2','doi'),(3,'D3','doi'),(3,'D4','doi'),(3,'D5','doi'),(3,'D6','doi'),
(3,'E1','doi'),(3,'E2','doi'),(3,'E3','doi'),(3,'E4','doi'),(3,'E5','doi'),(3,'E6','doi');

INSERT INTO ghe (phong_id, ma_ghe, loai_ghe) VALUES
(4,'A1','thuong'),(4,'A2','thuong'),(4,'A3','thuong'),(4,'A4','thuong'),(4,'A5','thuong'),(4,'A6','thuong'),(4,'A7','thuong'),(4,'A8','thuong'),(4,'A9','thuong'),(4,'A10','thuong'),
(4,'B1','thuong'),(4,'B2','thuong'),(4,'B3','thuong'),(4,'B4','thuong'),(4,'B5','thuong'),(4,'B6','thuong'),(4,'B7','thuong'),(4,'B8','thuong'),(4,'B9','thuong'),(4,'B10','thuong'),
(4,'C1','vip'),(4,'C2','vip'),(4,'C3','vip'),(4,'C4','vip'),(4,'C5','vip'),(4,'C6','vip'),(4,'C7','vip'),(4,'C8','vip'),(4,'C9','vip'),(4,'C10','vip'),
(4,'D1','vip'),(4,'D2','vip'),(4,'D3','vip'),(4,'D4','vip'),(4,'D5','vip'),(4,'D6','vip'),(4,'D7','vip'),(4,'D8','vip'),(4,'D9','vip'),(4,'D10','vip'),
(4,'E1','thuong'),(4,'E2','thuong'),(4,'E3','thuong'),(4,'E4','thuong'),(4,'E5','thuong'),(4,'E6','thuong'),(4,'E7','thuong'),(4,'E8','thuong'),(4,'E9','thuong'),(4,'E10','thuong'),
(4,'F1','thuong'),(4,'F2','thuong'),(4,'F3','thuong'),(4,'F4','thuong'),(4,'F5','thuong'),(4,'F6','thuong'),(4,'F7','thuong'),(4,'F8','thuong'),(4,'F9','thuong'),(4,'F10','thuong'),
(4,'G1','doi'),(4,'G2','doi'),(4,'G3','doi'),(4,'G4','doi'),(4,'G5','doi'),(4,'G6','doi'),(4,'G7','doi'),(4,'G8','doi'),(4,'G9','doi'),(4,'G10','doi'),
(4,'H1','doi'),(4,'H2','doi'),(4,'H3','doi'),(4,'H4','doi'),(4,'H5','doi'),(4,'H6','doi'),(4,'H7','doi'),(4,'H8','doi'),(4,'H9','doi'),(4,'H10','doi');

INSERT INTO suat_chieu (phim_id, phong_id, ngay_chieu, gio_bat_dau, gio_ket_thuc, gia_ve_co_ban) VALUES
(1,1,CURDATE(),'09:00:00','12:01:00',70000),(1,1,CURDATE(),'13:00:00','16:01:00',75000),(1,1,CURDATE(),'18:00:00','21:01:00',90000),
(1,2,CURDATE(),'10:00:00','13:01:00',70000),(1,2,CURDATE(),'15:00:00','18:01:00',80000),
(2,1,CURDATE(),'10:30:00','13:00:00',70000),(2,3,CURDATE(),'14:00:00','16:30:00',120000),(2,3,CURDATE(),'19:00:00','21:30:00',130000),
(3,4,CURDATE(),'11:00:00','13:46:00',75000),(3,4,CURDATE(),'16:00:00','18:46:00',85000),
(4,1,CURDATE()+INTERVAL 1 DAY,'09:30:00','11:06:00',70000),(4,1,CURDATE()+INTERVAL 1 DAY,'14:00:00','15:36:00',75000),
(5,2,CURDATE()+INTERVAL 1 DAY,'10:00:00','12:56:00',80000),(5,2,CURDATE()+INTERVAL 1 DAY,'18:30:00','21:26:00',95000),
(1,1,CURDATE()+INTERVAL 2 DAY,'09:00:00','12:01:00',70000),(1,1,CURDATE()+INTERVAL 2 DAY,'18:00:00','21:01:00',90000),
(2,3,CURDATE()+INTERVAL 2 DAY,'15:00:00','17:30:00',120000),
(6,1,CURDATE()+INTERVAL 5 DAY,'10:00:00','13:00:00',80000),(7,2,CURDATE()+INTERVAL 5 DAY,'14:00:00','15:54:00',75000);

INSERT INTO ma_giam_gia (ma_code, loai_giam, gia_tri, ngay_bat_dau, ngay_ket_thuc, so_lan_su_dung) VALUES
('GIAM10','phan_tram',10,CURDATE()-INTERVAL 10 DAY,CURDATE()+INTERVAL 30 DAY,100),
('GIAM20K','so_tien',20000,CURDATE()-INTERVAL 5 DAY,CURDATE()+INTERVAL 15 DAY,50),
('GIAM50','phan_tram',50,CURDATE()-INTERVAL 1 DAY,CURDATE()+INTERVAL 7 DAY,20),
('HETHAN','phan_tram',15,CURDATE()-INTERVAL 30 DAY,CURDATE()-INTERVAL 1 DAY,100),
('VIP30','phan_tram',30,CURDATE(),CURDATE()+INTERVAL 60 DAY,30),
('TESTMAX','phan_tram',100,CURDATE(),CURDATE()+INTERVAL 30 DAY,5);

INSERT INTO ve (ma_ve, nguoi_dung_id, suat_chieu_id, tong_tien, trang_thai, ngay_thanh_toan) VALUES
('VE20251011001',2,1,140000,'da_thanh_toan',NOW()-INTERVAL 2 HOUR),
('VE20251011002',3,3,180000,'da_thanh_toan',NOW()-INTERVAL 1 HOUR);

INSERT INTO chi_tiet_ve (ve_id, ghe_id, gia_ghe) VALUES (1,1,70000),(1,2,70000),(2,31,90000),(2,32,90000);
INSERT INTO thanh_toan (ve_id, phuong_thuc, so_tien, trang_thai, ma_giao_dich) VALUES
(1,'mo_phong',140000,'thanh_cong','GD20251011001'),(2,'mo_phong',180000,'thanh_cong','GD20251011002');

SELECT 'Database v2.0 OK' AS thong_bao;
