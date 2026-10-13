<?php
/**
 * Sửa phim
 */
$pageTitle = 'Sửa phim';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM phim WHERE id = ?");
$stmt->execute([$id]);
$phim = $stmt->fetch();

if (!$phim) {
    redirect('phim.php', 'Phim không tồn tại.', 'danger');
}

$dsTheLoai = $pdo->query("SELECT * FROM the_loai ORDER BY ten_the_loai")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenPhim = trim($_POST['ten_phim'] ?? '');
    $theLoaiId = (int)($_POST['the_loai_id'] ?? 0);
    $thoiLuong = (int)($_POST['thoi_luong'] ?? 0);
    $ngayKhoiChieu = $_POST['ngay_khoi_chieu'] ?? '';
    $daoDien = trim($_POST['dao_dien'] ?? '');
    $dienVien = trim($_POST['dien_vien'] ?? '');
    $moTa = trim($_POST['mo_ta'] ?? '');
    $trangThai = $_POST['trang_thai'] ?? 'dang_chieu';

    if ($tenPhim === '') $errors[] = 'Tên phim không được để trống.';
    if ($thoiLuong <= 0) $errors[] = 'Thời lượng phải lớn hơn 0.';
    if (!$ngayKhoiChieu) $errors[] = 'Ngày khởi chiếu không hợp lệ.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE phim SET ten_phim=?, the_loai_id=?, thoi_luong=?, ngay_khoi_chieu=?, 
            dao_dien=?, dien_vien=?, mo_ta=?, trang_thai=? WHERE id=?
        ");
        $stmt->execute([
            $tenPhim, $theLoaiId ?: null, $thoiLuong, $ngayKhoiChieu,
            $daoDien, $dienVien, $moTa, $trangThai, $id
        ]);
        redirect('phim.php', 'Cập nhật phim thành công!');
    }
} else {
    $_POST = $phim;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Sửa phim #<?php echo $id; ?></h3>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".clean($e)."</li>"; ?></ul></div>
            <?php endif; ?>

            <form method="POST" class="card border-0 shadow-sm p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Tên phim <span class="text-danger">*</span></label>
                        <input type="text" name="ten_phim" class="form-control" required value="<?php echo clean($_POST['ten_phim']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Thể loại</label>
                        <select name="the_loai_id" class="form-select">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($dsTheLoai as $tl): ?>
                                <option value="<?php echo $tl['id']; ?>" <?php echo ($_POST['the_loai_id']??'') == $tl['id'] ? 'selected' : ''; ?>>
                                    <?php echo clean($tl['ten_the_loai']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Thời lượng (phút) *</label>
                        <input type="number" name="thoi_luong" class="form-control" required min="1" value="<?php echo (int)$_POST['thoi_luong']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ngày khởi chiếu *</label>
                        <input type="date" name="ngay_khoi_chieu" class="form-control" required value="<?php echo $_POST['ngay_khoi_chieu']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trạng thái</label>
                        <select name="trang_thai" class="form-select">
                            <?php foreach (['dang_chieu'=>'Đang chiếu','sap_chieu'=>'Sắp chiếu','ngung_chieu'=>'Ngừng chiếu'] as $k=>$v): ?>
                                <option value="<?php echo $k; ?>" <?php echo $_POST['trang_thai']===$k?'selected':''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Đạo diễn</label>
                        <input type="text" name="dao_dien" class="form-control" value="<?php echo clean($_POST['dao_dien']??''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Diễn viên</label>
                        <input type="text" name="dien_vien" class="form-control" value="<?php echo clean($_POST['dien_vien']??''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả</label>
                        <textarea name="mo_ta" class="form-control" rows="4"><?php echo clean($_POST['mo_ta']??''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                        <a href="phim.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>