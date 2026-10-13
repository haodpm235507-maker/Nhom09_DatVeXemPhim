<?php
/**
 * Quản lý suất chiếu - Có kiểm tra xung đột
 */
$pageTitle = 'Suất chiếu';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();
$dsPhim = $pdo->query("SELECT id, ten_phim FROM phim WHERE trang_thai != 'ngung_chieu'")->fetchAll();
$dsPhong = $pdo->query("
    SELECT pc.id, pc.ten_phong, r.ten_rap 
    FROM phong_chieu pc JOIN rap r ON pc.rap_id = r.id
")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them'])) {
    $phimId = (int)$_POST['phim_id'];
    $phongId = (int)$_POST['phong_id'];
    $ngayChieu = $_POST['ngay_chieu'] ?? '';
    $gioBatDau = $_POST['gio_bat_dau'] ?? '';
    $gioKetThuc = $_POST['gio_ket_thuc'] ?? '';
    $giaVe = (int)($_POST['gia_ve_co_ban'] ?? 70000);
    
    if ($phimId <= 0) $errors[] = 'Chọn phim.';
    if ($phongId <= 0) $errors[] = 'Chọn phòng.';
    if (!$ngayChieu) $errors[] = 'Chọn ngày chiếu.';
    if (!$gioBatDau || !$gioKetThuc) $errors[] = 'Nhập giờ bắt đầu và kết thúc.';
    if ($gioBatDau >= $gioKetThuc) $errors[] = 'Giờ kết thúc phải sau giờ bắt đầu.';
    if ($giaVe < 0) $errors[] = 'Giá vé không hợp lệ.';
    
    // Kiểm tra xung đột suất chiếu cùng phòng
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id FROM suat_chieu 
            WHERE phong_id = ? AND ngay_chieu = ? AND trang_thai = 'hoat_dong'
              AND (
                  (gio_bat_dau < ? AND gio_ket_thuc > ?) OR
                  (gio_bat_dau < ? AND gio_ket_thuc > ?) OR
                  (gio_bat_dau >= ? AND gio_ket_thuc <= ?)
              )
        ");
        $stmt->execute([
            $phongId, $ngayChieu,
            $gioKetThuc, $gioBatDau,
            $gioKetThuc, $gioBatDau,
            $gioBatDau, $gioKetThuc
        ]);
        if ($stmt->fetch()) {
            $errors[] = 'Xung đột lịch chiếu! Phòng này đã có suất chiếu trùng thời gian.';
        }
    }
    
    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO suat_chieu (phim_id, phong_id, ngay_chieu, gio_bat_dau, gio_ket_thuc, gia_ve_co_ban)
            VALUES (?,?,?,?,?,?)
        ")->execute([$phimId, $phongId, $ngayChieu, $gioBatDau, $gioKetThuc, $giaVe]);
        redirect('suat_chieu.php', 'Thêm suất chiếu thành công!');
    }
}

if (isset($_GET['huy'])) {
    $pdo->prepare("UPDATE suat_chieu SET trang_thai = 'huy' WHERE id = ?")->execute([(int)$_GET['huy']]);
    redirect('suat_chieu.php', 'Đã hủy suất chiếu.');
}

$dsSuat = $pdo->query("
    SELECT sc.*, p.ten_phim, pc.ten_phong, r.ten_rap
    FROM suat_chieu sc
    JOIN phim p ON sc.phim_id = p.id
    JOIN phong_chieu pc ON sc.phong_id = pc.id
    JOIN rap r ON pc.rap_id = r.id
    ORDER BY sc.ngay_chieu DESC, sc.gio_bat_dau
    LIMIT 50
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Quản lý suất chiếu</h3>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e).'<br>'; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4 p-3">
                <h6>Thêm suất chiếu (có kiểm tra xung đột)</h6>
                <form method="POST" class="row g-2">
                    <div class="col-md-3">
                        <select name="phim_id" class="form-select form-select-sm" required>
                            <option value="">-- Phim --</option>
                            <?php foreach ($dsPhim as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo clean($p['ten_phim']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="phong_id" class="form-select form-select-sm" required>
                            <option value="">-- Phòng --</option>
                            <?php foreach ($dsPhong as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo clean($p['ten_rap'].' - '.$p['ten_phong']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="ngay_chieu" class="form-control form-control-sm" required></div>
                    <div class="col-md-1"><input type="time" name="gio_bat_dau" class="form-control form-control-sm" required></div>
                    <div class="col-md-1"><input type="time" name="gio_ket_thuc" class="form-control form-control-sm" required></div>
                    <div class="col-md-1"><input type="number" name="gia_ve_co_ban" class="form-control form-control-sm" value="70000" min="0"></div>
                    <div class="col-md-1"><button type="submit" name="them" class="btn btn-primary btn-sm w-100">Thêm</button></div>
                </form>
            </div>

            <table class="table table-bordered bg-white table-sm">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Phim</th><th>Rạp - Phòng</th><th>Ngày</th><th>Giờ</th><th>Giá</th><th>TT</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dsSuat as $s): ?>
                    <tr>
                        <td><?php echo $s['id']; ?></td>
                        <td><?php echo clean($s['ten_phim']); ?></td>
                        <td><?php echo clean($s['ten_rap'].' - '.$s['ten_phong']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($s['ngay_chieu'])); ?></td>
                        <td><?php echo substr($s['gio_bat_dau'],0,5).'-'.substr($s['gio_ket_thuc'],0,5); ?></td>
                        <td><?php echo number_format($s['gia_ve_co_ban']); ?></td>
                        <td><?php echo $s['trang_thai']; ?></td>
                        <td>
                            <?php if ($s['trang_thai']==='hoat_dong'): ?>
                                <a href="?huy=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hủy suất?')">Hủy</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>