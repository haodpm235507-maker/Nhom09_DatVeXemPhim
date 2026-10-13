<?php
/**
 * Đăng ký tài khoản
 */
$pageTitle = 'Đăng ký';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['so_dien_thoai'] ?? '');
    $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';
    $xacNhan = $_POST['xac_nhan_mat_khau'] ?? '';

    // Validation backend
    if (strlen($hoTen) < 2 || strlen($hoTen) > 100) {
        $errors[] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (!preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $sdt)) {
        $errors[] = 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 03/05/07/08/09).';
    }
    if (strlen($tenDangNhap) < 4 || strlen($tenDangNhap) > 50) {
        $errors[] = 'Tên đăng nhập phải từ 4 đến 50 ký tự.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tenDangNhap)) {
        $errors[] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới.';
    }
    if (strlen($matKhau) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($matKhau !== $xacNhan) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    // Kiểm tra trùng
    if (empty($errors)) {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email đã được sử dụng.';
        }
        
        $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = ?");
        $stmt->execute([$tenDangNhap]);
        if ($stmt->fetch()) {
            $errors[] = 'Tên đăng nhập đã tồn tại.';
        }
    }

    // Lưu database
    if (empty($errors)) {
        $pdo = getConnection();
        $hash = password_hash($matKhau, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO nguoi_dung (ho_ten, email, so_dien_thoai, ten_dang_nhap, mat_khau, vai_tro)
            VALUES (?, ?, ?, ?, ?, 'khach')
        ");
        $stmt->execute([$hoTen, $email, $sdt, $tenDangNhap, $hash]);
        $success = true;
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="bi bi-person-plus"></i> Đăng ký tài khoản</h3>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Đăng ký thành công! <a href="dang_nhap.php">Đăng nhập ngay</a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?php echo clean($e); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" onsubmit="return validateDangKy(this)">
                            <div class="mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="ho_ten" class="form-control" required
                                       value="<?php echo clean($_POST['ho_ten'] ?? ''); ?>"
                                       minlength="2" maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo clean($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="so_dien_thoai" class="form-control" required
                                       placeholder="09xxxxxxxx"
                                       value="<?php echo clean($_POST['so_dien_thoai'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                <input type="text" name="ten_dang_nhap" class="form-control" required
                                       minlength="4" maxlength="50"
                                       value="<?php echo clean($_POST['ten_dang_nhap'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="mat_khau" class="form-control" required minlength="6">
                                <div class="form-text">Tối thiểu 6 ký tự</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="xac_nhan_mat_khau" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">Đăng ký</button>
                        </form>
                        
                        <p class="text-center mt-3 mb-0">
                            Đã có tài khoản? <a href="dang_nhap.php">Đăng nhập</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>