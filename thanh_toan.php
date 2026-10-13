<?php
/**
 * Thanh toán mô phỏng v2 - Kiểm tra timeout
 */
$pageTitle = 'Thanh toán';
require_once __DIR__ . '/includes/header.php';
yeuCauDangNhap();

$veId = (int)($_GET['ve_id'] ?? 0);
if ($veId <= 0) redirect('ve_cua_toi.php', 'Vé không hợp lệ.', 'danger');

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT v.*, sc.ngay_chieu, sc.gio_bat_dau, sc.gio_ket_thuc,
           p.ten_phim, pc.ten_phong, r.ten_rap, nd.ho_ten
    FROM ve v
    JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
    JOIN phim p ON sc.phim_id = p.id
    JOIN phong_chieu pc ON sc.phong_id = pc.id
    JOIN rap r ON pc.rap_id = r.id
    JOIN nguoi_dung nd ON v.nguoi_dung_id = nd.id
    WHERE v.id = ? AND v.nguoi_dung_id = ?
");
$stmt->execute([$veId, $_SESSION['user_id']]);
$ve = $stmt->fetch();

if (!$ve) redirect('ve_cua_toi.php', 'Không tìm thấy vé.', 'danger');

// Kiểm tra timeout
if ($ve['trang_thai'] === 'cho_thanh_toan' && $ve['het_han_thanh_toan'] && strtotime($ve['het_han_thanh_toan']) < time()) {
    // Auto hủy
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE ve SET trang_thai='da_huy' WHERE id=?")->execute([$veId]);
    $pdo->prepare("DELETE FROM chi_tiet_ve WHERE ve_id=?")->execute([$veId]);
    $pdo->commit();
    ghiNhatKy('HUY_VE_TIMEOUT', "Vé {$ve['ma_ve']} hết hạn thanh toán");
    redirect('ve_cua_toi.php', 'Vé đã hết hạn thanh toán (quá 10 phút). Ghế đã được giải phóng.', 'danger');
}

if ($ve['trang_thai'] !== 'cho_thanh_toan') {
    redirect('ve_cua_toi.php', 'Vé này đã được xử lý.', 'danger');
}

$stmt = $pdo->prepare("SELECT ctv.*, g.ma_ghe, g.loai_ghe FROM chi_tiet_ve ctv JOIN ghe g ON ctv.ghe_id=g.id WHERE ctv.ve_id=?");
$stmt->execute([$veId]);
$dsGhe = $stmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kiemTraCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ.';
    } else {
        $hanhDong = $_POST['hanh_dong'] ?? '';
        if ($hanhDong === 'thanh_toan_thanh_cong') {
            $pdo->beginTransaction();
            try {
                $maGD = 'GD' . date('YmdHis') . rand(100,999);
                $pdo->prepare("UPDATE ve SET trang_thai='da_thanh_toan', ngay_thanh_toan=NOW() WHERE id=?")->execute([$veId]);
                $pdo->prepare("INSERT INTO thanh_toan (ve_id,phuong_thuc,so_tien,trang_thai,ma_giao_dich) VALUES (?,'mo_phong',?,'thanh_cong',?)")
                    ->execute([$veId, $ve['tong_tien'], $maGD]);
                $pdo->commit();
                ghiNhatKy('THANH_TOAN_THANH_CONG', "Vé {$ve['ma_ve']} - " . formatTien($ve['tong_tien']));
                redirect('ve_cua_toi.php', 'Thanh toán thành công! Mã vé: ' . $ve['ma_ve']);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        } elseif ($hanhDong === 'thanh_toan_that_bai' || $hanhDong === 'huy_thanh_toan') {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE ve SET trang_thai='da_huy' WHERE id=?")->execute([$veId]);
                $pdo->prepare("DELETE FROM chi_tiet_ve WHERE ve_id=?")->execute([$veId]);
                $status = $hanhDong === 'thanh_toan_that_bai' ? 'that_bai' : 'huy';
                $pdo->prepare("INSERT INTO thanh_toan (ve_id,phuong_thuc,so_tien,trang_thai,ma_giao_dich) VALUES (?,'mo_phong',?,?,?)")
                    ->execute([$veId, $ve['tong_tien'], $status, strtoupper($status).time()]);
                $pdo->commit();
                ghiNhatKy('THANH_TOAN_' . strtoupper($status), "Vé {$ve['ma_ve']}");
                $msg = $hanhDong === 'thanh_toan_that_bai' ? 'Thanh toán thất bại. Ghế đã giải phóng.' : 'Đã hủy thanh toán. Ghế đã giải phóng.';
                redirect('ve_cua_toi.php', $msg, 'danger');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
}

$conLai = $ve['het_han_thanh_toan'] ? max(0, strtotime($ve['het_han_thanh_toan']) - time()) : 600;
?>
<div class="container py-4">
    <h3 class="mb-4"><i class="bi bi-credit-card"></i> Thanh toán</h3>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo clean($e).'<br>'; ?></div><?php endif; ?>

    <div class="alert alert-warning d-flex justify-content-between">
        <span>Thời gian còn lại để thanh toán:</span>
        <strong id="timerTT"><?php echo sprintf('%02d:%02d', floor($conLai/60), $conLai%60); ?></strong>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white">Thông tin vé</div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td width="140" class="text-muted">Mã vé</td><td><strong><?php echo clean($ve['ma_ve']); ?></strong></td></tr>
                        <tr><td class="text-muted">Phim</td><td><?php echo clean($ve['ten_phim']); ?></td></tr>
                        <tr><td class="text-muted">Rạp</td><td><?php echo clean($ve['ten_rap'].' - '.$ve['ten_phong']); ?></td></tr>
                        <tr><td class="text-muted">Suất</td><td><?php echo date('d/m/Y',strtotime($ve['ngay_chieu'])).' · '.substr($ve['gio_bat_dau'],0,5); ?></td></tr>
                        <tr><td class="text-muted">Ghế</td><td><?php foreach($dsGhe as $g) echo '<span class="badge bg-secondary me-1">'.$g['ma_ghe'].'</span>'; ?></td></tr>
                        <tr><td class="text-muted">Số lượng</td><td><?php echo count($dsGhe); ?> ghế</td></tr>
                        <?php if($ve['so_tien_giam']>0): ?><tr><td class="text-muted">Giảm giá</td><td class="text-success">-<?php echo formatTien($ve['so_tien_giam']); ?></td></tr><?php endif; ?>
                        <tr><td class="text-muted">Tổng TT</td><td><strong class="text-danger fs-5"><?php echo formatTien($ve['tong_tien']); ?></strong></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">Thanh toán mô phỏng</div>
                <div class="card-body">
                    <p class="small text-muted">Chọn kết quả để test các trường hợp.</p>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <div class="d-grid gap-2">
                            <button type="submit" name="hanh_dong" value="thanh_toan_thanh_cong" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Thanh toán thành công
                            </button>
                            <button type="submit" name="hanh_dong" value="thanh_toan_that_bai" class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> Thanh toán thất bại
                            </button>
                            <button type="submit" name="hanh_dong" value="huy_thanh_toan" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Hủy thanh toán
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
let remain = <?php echo $conLai; ?>;
setInterval(() => {
    remain--;
    if (remain <= 0) { alert('Hết thời gian thanh toán!'); location.href='ve_cua_toi.php'; return; }
    document.getElementById('timerTT').textContent = String(Math.floor(remain/60)).padStart(2,'0') + ':' + String(remain%60).padStart(2,'0');
}, 1000);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
