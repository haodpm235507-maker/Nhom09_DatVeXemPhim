<?php
/**
 * Trang chủ
 */
$pageTitle = 'Trang chủ';
require_once __DIR__ . '/includes/header.php';

$pdo = getConnection();

// Phim đang chiếu
$stmt = $pdo->query("
    SELECT p.*, tl.ten_the_loai 
    FROM phim p 
    LEFT JOIN the_loai tl ON p.the_loai_id = tl.id 
    WHERE p.trang_thai = 'dang_chieu' 
    ORDER BY p.ngay_khoi_chieu DESC 
    LIMIT 8
");
$phimDangChieu = $stmt->fetchAll();

// Phim sắp chiếu
$stmt = $pdo->query("
    SELECT p.*, tl.ten_the_loai 
    FROM phim p 
    LEFT JOIN the_loai tl ON p.the_loai_id = tl.id 
    WHERE p.trang_thai = 'sap_chieu' 
    ORDER BY p.ngay_khoi_chieu ASC 
    LIMIT 4
");
$phimSapChieu = $stmt->fetchAll();
?>

<!-- Hero -->
<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">Đặt Vé Xem Phim Online</h1>
        <p class="lead mb-4">Trải nghiệm điện ảnh tuyệt vời với hệ thống đặt vé nhanh chóng, tiện lợi</p>
        <a href="danh_sach_phim.php" class="btn btn-primary btn-lg px-4">
            <i class="bi bi-ticket-perforated"></i> Đặt vé ngay
        </a>
    </div>
</section>

<div class="container">
    <!-- Phim đang chiếu -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0"><i class="bi bi-play-circle text-danger"></i> Phim đang chiếu</h2>
            <a href="danh_sach_phim.php?trang_thai=dang_chieu" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
        </div>
        <div class="row g-4">
            <?php foreach ($phimDangChieu as $phim): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card movie-card">
                    <div class="position-relative">
                        <div class="movie-poster-placeholder">
                            <i class="bi bi-film"></i>
                        </div>
                        <span class="badge bg-danger badge-trang-thai">Đang chiếu</span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title text-truncate"><?php echo clean($phim['ten_phim']); ?></h6>
                        <p class="card-text small text-muted mb-2">
                            <?php echo clean($phim['ten_the_loai'] ?? 'N/A'); ?> · <?php echo $phim['thoi_luong']; ?> phút
                        </p>
                        <a href="chi_tiet_phim.php?id=<?php echo $phim['id']; ?>" class="btn btn-primary btn-sm w-100">
                            Chi tiết / Đặt vé
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Phim sắp chiếu -->
    <?php if (!empty($phimSapChieu)): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0"><i class="bi bi-calendar-event text-warning"></i> Phim sắp chiếu</h2>
            <a href="danh_sach_phim.php?trang_thai=sap_chieu" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
        </div>
        <div class="row g-4">
            <?php foreach ($phimSapChieu as $phim): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card movie-card">
                    <div class="position-relative">
                        <div class="movie-poster-placeholder" style="background: linear-gradient(135deg, #2c3e50, #4a69bd);">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <span class="badge bg-warning text-dark badge-trang-thai">Sắp chiếu</span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title text-truncate"><?php echo clean($phim['ten_phim']); ?></h6>
                        <p class="card-text small text-muted mb-2">
                            Khởi chiếu: <?php echo date('d/m/Y', strtotime($phim['ngay_khoi_chieu'])); ?>
                        </p>
                        <a href="chi_tiet_phim.php?id=<?php echo $phim['id']; ?>" class="btn btn-outline-secondary btn-sm w-100">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>