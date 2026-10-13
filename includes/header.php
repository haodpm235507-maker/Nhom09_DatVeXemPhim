<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? clean($pageTitle) . ' - ' : ''; ?>CineBook - Đặt Vé Xem Phim Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/dat-ve-xem-phim/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dat-ve-xem-phim/index.php">
                <i class="bi bi-film"></i> CineBook
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dat-ve-xem-phim/index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dat-ve-xem-phim/danh_sach_phim.php">Phim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dat-ve-xem-phim/danh_sach_phim.php?trang_thai=dang_chieu">Đang chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dat-ve-xem-phim/danh_sach_phim.php?trang_thai=sap_chieu">Sắp chiếu</a>
                    </li>
                </ul>
                
                <!-- Form tìm kiếm -->
                <form class="d-flex me-3" action="/dat-ve-xem-phim/danh_sach_phim.php" method="GET">
                    <input class="form-control form-control-sm me-2" type="search" name="q" 
                           placeholder="Tìm phim..." value="<?php echo isset($_GET['q']) ? clean($_GET['q']) : ''; ?>">
                    <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-search"></i></button>
                </form>
                
                <ul class="navbar-nav">
                    <?php if (kiemTraDangNhap()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> 
                                <?php echo clean($_SESSION['ho_ten'] ?? 'Tài khoản'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/dat-ve-xem-phim/ve_cua_toi.php">
                                    <i class="bi bi-ticket-perforated"></i> Vé của tôi
                                </a></li>
                                <?php if (kiemTraAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-primary" href="/dat-ve-xem-phim/admin/index.php">
                                    <i class="bi bi-gear"></i> Trang quản trị
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/dat-ve-xem-phim/auth/dang_xuat.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/dat-ve-xem-phim/auth/dang_nhap.php">Đăng nhập</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary btn-sm text-white ms-2 px-3" href="/dat-ve-xem-phim/auth/dang_ky.php">Đăng ký</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash message -->
    <div class="container mt-3">
        <?php hienThiFlash(); ?>
    </div>
    
    <main class="pb-5">