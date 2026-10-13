<?php
/**
 * Thêm phim
 */
$pageTitle = 'Thêm phim';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();
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

    // Validation
    if ($tenPhim === '') $errors[] = 'Tên phim không được để trống.';
    if (strlen($tenPhim) > 200) $errors[] = 'Tên phim tối đa 200 ký tự.';
    if ($thoiLuong <= 0) $errors[] = 'Thời lượng phải lớn hơn 0.';
    if ($thoiLuong > 500) $errors[] = 'Thời lượng không hợp lệ (tối đa 500 phút).';
    if (!$ngayKhoiChieu || !strtotime($ngayKhoiChieu)) $errors[] = 'Ngày khởi chiếu không hợp lệ.';
    if (!in_array($trangThai, ['dang_chieu', 'sap_chieu', 'ngung_chieu'])) $errors[] = 'Trạng thái không hợp lệ.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO phim (ten_phim, the_loai_id, thoi_luong, ngay_khoi_chieu, dao_dien, dien_vien, mo_ta, trang_thai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $tenPhim,
            $theLoaiId ?: null,
            $thoiLuong,
            $ngayKhoiChieu,
            $daoDien,
            $dienVien,
            $moTa,
            $trangThai
        ]);
        redirect('phim.php', 'Thêm phim thành công!');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Thêm phim mới</h3>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".clean($e)."</li>"; ?></ul></div>
            <?php endif; ?>

            <form method="POST" class="card border-0 shadow-sm p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Tên phim <span class="text-danger">*</span></label>
                        <input type="text" name="ten_phim" class="form-control" required maxlength="200"
                               value="<?php echo clean($_POST['ten_phim'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Thể loại</label>
                        <select name="the_loai_id" class="form-select">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($dsTheLoai as $tl): ?>
                                <option value="<?php echo $tl['id']; ?>"><?php echo clean($tl['ten_the_loai']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Thời lượng (phút) <span class="text-danger">*</span></label>
                        <input type="number" name="thoi_luong" class="form-control" required min="1" max="500"
                               value="<?php echo clean($_POST['thoi_luong'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ngày khởi chiếu <span class="text-danger">*</span></label>
                        <input type="date" name="ngay_khoi_chieu" class="form-control" required
                               value="<?php echo clean($_POST['ngay_khoi_chieu'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trạng thái</label>
                        <select name="trang_thai" class="form-select">
                            <option value="dang_chieu">Đang chiếu</option>
                            <option value="sap_chieu">Sắp chiếu</option>
                            <option value="ngung_chieu">Ngừng chiếu</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Đạo diễn</label>
                        <input type="text" name="dao_dien" class="form-control" value="<?php echo clean($_POST['dao_dien'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Diễn viên</label>
                        <input type="text" name="dien_vien" class="form-control" value="<?php echo clean($_POST['dien_vien'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả</label>
                        <textarea name="mo_ta" class="form-control" rows="4"><?php echo clean($_POST['mo_ta'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Thêm phim</button>
                        <a href="phim.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>