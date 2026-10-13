<?php
/**
 * Đăng nhập
 */
$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/../includes/header.php';

// Nếu đã đăng nhập thì redirect
if (kiemTraDangNhap()) {
    if (kiemTraAdmin()) {
        header('Location: /dat-ve-xem-phim/admin/index.php');
    } else {
        header('Location: /dat-ve-xem-phim/index.php');
    }
    exit;
}

$errors = [];
$errorMsg = $_GET['error'] ?? '';

if ($errorMsg === 'khong_co_quyen') {
    $errors[] = 'Bạn không có quyền truy cập trang quản trị.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = trim($_POST['ten_dang_nhap'] ?? '');
    $matKhau = $_POST['mat_khau'] ?? '';

    if (empty($tenDangNhap) || empty($matKhau)) {
        $errors[] = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ? OR email = ?");
        $stmt->execute([$tenDangNhap, $tenDangNhap]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Tài khoản không tồn tại.';
        } elseif ($user['trang_thai'] === 'khoa') {
            $errors[] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } elseif (!password_verify($matKhau, $user['mat_khau'])) {
            $errors[] = 'Mật khẩu không đúng.';
        } else {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['ho_ten'] = $user['ho_ten'];
            $_SESSION['ten_dang_nhap'] = $user['ten_dang_nhap'];
            $_SESSION['vai_tro'] = $user['vai_tro'];
            $_SESSION['email'] = $user['email'];

            $redirect = $_GET['redirect'] ?? '';
            if ($user['vai_tro'] === 'admin') {
                header('Location: /dat-ve-xem-phim/admin/index.php');
            } elseif ($redirect) {
                header('Location: ' . $redirect);
            } else {
                header('Location: /dat-ve-xem-phim/index.php');
            }
            exit;
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</h3>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $e): ?>
                                <div><?php echo clean($e); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateDangNhap(this)">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập / Email</label>
                            <input type="text" name="ten_dang_nhap" class="form-control" required
                                   value="<?php echo clean($_POST['ten_dang_nhap'] ?? ''); ?>"
                                   autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="mat_khau" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Đăng nhập</button>
                    </form>
                    
                    <p class="text-center mt-3 mb-0">
                        Chưa có tài khoản? <a href="dang_ky.php">Đăng ký ngay</a>
                    </p>
                    
                    <hr>
                    <div class="small text-muted text-center">
                        <strong>Tài khoản test:</strong><br>
                        Admin: <code>admin</code> / <code>123456</code><br>
                        Khách: <code>nguyenvana</code> / <code>123456</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>