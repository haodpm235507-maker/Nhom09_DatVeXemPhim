<?php
/**
 * Hủy vé
 */
require_once __DIR__ . '/includes/header.php';

yeuCauDangNhap();

$veId = (int)($_GET['ve_id'] ?? 0);
if ($veId <= 0) {
    redirect('ve_cua_toi.php', 'Vé không hợp lệ.', 'danger');
}

$pdo = getConnection();

// Kiểm tra vé thuộc user
$stmt = $pdo->prepare("SELECT * FROM ve WHERE id = ? AND nguoi_dung_id = ?");
$stmt->execute([$veId, $_SESSION['user_id']]);
$ve = $stmt->fetch();

if (!$ve) {
    redirect('ve_cua_toi.php', 'Không tìm thấy vé.', 'danger');
}

// Kiểm tra điều kiện hủy
$ketQua = coTheHuyVe($veId);

if (!$ketQua['ok']) {
    redirect('ve_cua_toi.php', $ketQua['message'], 'danger');
}

// Thực hiện hủy
$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE ve SET trang_thai = 'da_huy' WHERE id = ?")->execute([$veId]);
    
    // Giải phóng ghế
    $pdo->prepare("DELETE FROM chi_tiet_ve WHERE ve_id = ?")->execute([$veId]);
    
    $pdo->commit();
    redirect('ve_cua_toi.php', 'Hủy vé thành công! Ghế đã được giải phóng.');
} catch (Exception $e) {
    $pdo->rollBack();
    redirect('ve_cua_toi.php', 'Lỗi khi hủy vé: ' . $e->getMessage(), 'danger');
}
?>