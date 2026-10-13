<?php
/**
 * Cấu hình kết nối database
 * Hệ thống Đặt Vé Xem Phim Online
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'dat_ve_xem_phim');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP mặc định không có mật khẩu
define('DB_CHARSET', 'utf8mb4');

/**
 * Hàm kết nối PDO
 * @return PDO
 */
function getConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Định dạng tiền VND
 */
function formatTien($soTien) {
    return number_format($soTien, 0, ',', '.') . ' VNĐ';
}

/**
 * Tạo mã vé ngẫu nhiên
 */
function taoMaVe() {
    return 'VE' . date('Ymd') . strtoupper(substr(uniqid(), -5));
}

/**
 * Kiểm tra đăng nhập
 */
function kiemTraDangNhap() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Kiểm tra quyền admin
 */
function kiemTraAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
}

/**
 * Yêu cầu đăng nhập (redirect nếu chưa login)
 */
function yeuCauDangNhap() {
    if (!kiemTraDangNhap()) {
        header('Location: /dat-ve-xem-phim/auth/dang_nhap.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Yêu cầu quyền admin
 */
function yeuCauAdmin() {
    if (!kiemTraAdmin()) {
        header('Location: /dat-ve-xem-phim/auth/dang_nhap.php?error=khong_co_quyen');
        exit;
    }
}
?>