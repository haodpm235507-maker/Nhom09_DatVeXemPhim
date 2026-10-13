<?php
/**
 * Script reset dữ liệu test - Chỉ chạy local
 * Truy cập: http://localhost/dat-ve-xem-phim/reset_data.php?key=test123
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$key = $_GET['key'] ?? '';
if ($key !== 'test123') {
    die('Access denied. Use ?key=test123');
}

$pdo = getConnection();

// Xóa dữ liệu giao dịch
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("TRUNCATE TABLE nhat_ky");
$pdo->exec("TRUNCATE TABLE ghe_giu");
$pdo->exec("TRUNCATE TABLE thanh_toan");
$pdo->exec("TRUNCATE TABLE chi_tiet_ve");
$pdo->exec("TRUNCATE TABLE ve");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

// Reset mã giảm giá usage
$pdo->exec("UPDATE ma_giam_gia SET da_su_dung = 0");

echo "<h3>Reset dữ liệu thành công!</h3>";
echo "<p>Đã xóa: vé, chi tiết vé, thanh toán, giữ ghế, nhật ký.</p>";
echo "<p>Đã reset số lần dùng mã giảm giá về 0.</p>";
echo '<p><a href="index.php">Về trang chủ</a></p>';
ghiNhatKy('RESET_DATA', 'Admin reset dữ liệu test');
