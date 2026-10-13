<?php
/**
 * Vé của tôi - Lịch sử đặt vé
 */
$pageTitle = 'Vé của tôi';
require_once __DIR__ . '/includes/header.php';

yeuCauDangNhap();

$pdo = getConnection();

$stmt = $pdo->prepare("
    SELECT v.*, sc.ngay_chieu, sc.gio_bat_dau, p.ten_phim, pc.ten_phong, r.ten_rap
    FROM ve v
    JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
    JOIN phim p ON sc.phim_id = p.id
    JOIN phong_chieu pc ON sc.phong_id = pc.id
    JOIN rap r ON pc.rap_id = r.id
    WHERE v.nguoi_dung_id = ?
    ORDER BY v.ngay_dat DESC
");
$stmt->execute([$_SESSION['user_id']]);
$dsVe = $stmt->fetchAll();

// Lấy ghế cho từng vé
function layGheCuaVe($veId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT g.ma_ghe FROM chi_tiet_ve ctv 
        JOIN ghe g ON ctv.ghe_id = g.id 
        WHERE ctv.ve_id = ?
    ");
    $stmt->execute([$veId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="container py-4">
    <h3 class="mb-4"><i class="bi bi-ticket-perforated"></i> Vé của tôi</h3>

    <?php if (empty($dsVe)): ?>
        <div class="alert alert-info">
            Bạn chưa có vé nào. <a href="danh_sach_phim.php">Đặt vé ngay</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($dsVe as $ve): 
                $ghes = layGheCuaVe($ve['id']);
                $trangThaiClass = [
                    'cho_thanh_toan' => 'warning',
                    'da_thanh_toan' => 'success',
                    'da_su_dung' => 'secondary',
                    'da_huy' => 'danger'
                ];
                $trangThaiText = [
                    'cho_thanh_toan' => 'Chờ thanh toán',
                    'da_thanh_toan' => 'Đã thanh toán',
                    'da_su_dung' => 'Đã sử dụng',
                    'da_huy' => 'Đã hủy'
                ];
            ?>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?php echo clean($ve['ma_ve']); ?></strong>
                        <span class="badge bg-<?php echo $trangThaiClass[$ve['trang_thai']]; ?>">
                            <?php echo $trangThaiText[$ve['trang_thai']]; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h6><?php echo clean($ve['ten_phim']); ?></h6>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-geo-alt"></i> <?php echo clean($ve['ten_rap']); ?> - <?php echo clean($ve['ten_phong']); ?><br>
                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($ve['ngay_chieu'])); ?>
                            · <?php echo substr($ve['gio_bat_dau'], 0, 5); ?><br>
                            <i class="bi bi-grid-3x3"></i> Ghế: 
                            <?php echo !empty($ghes) ? implode(', ', $ghes) : '<em class="text-muted">Đã hủy</em>'; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-danger"><?php echo formatTien($ve['tong_tien']); ?></strong>
                            <div>
                                <?php if ($ve['trang_thai'] === 'cho_thanh_toan'): ?>
                                    <a href="thanh_toan.php?ve_id=<?php echo $ve['id']; ?>" class="btn btn-sm btn-primary">
                                        Thanh toán
                                    </a>
                                <?php elseif ($ve['trang_thai'] === 'da_thanh_toan'): ?>
                                    <a href="huy_ve.php?ve_id=<?php echo $ve['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Bạn chắc chắn muốn hủy vé này?')">
                                        Hủy vé
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer small text-muted">
                        Đặt lúc: <?php echo date('d/m/Y H:i', strtotime($ve['ngay_dat'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>