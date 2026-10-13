<?php
/**
 * Trang quản trị - Dashboard
 */
$pageTitle = 'Quản trị';
require_once __DIR__ . '/../includes/header.php';

yeuCauAdmin();

$pdo = getConnection();

// Thống kê nhanh
$stats = [
    'phim' => $pdo->query("SELECT COUNT(*) FROM phim")->fetchColumn(),
    'rap' => $pdo->query("SELECT COUNT(*) FROM rap")->fetchColumn(),
    'suat' => $pdo->query("SELECT COUNT(*) FROM suat_chieu WHERE trang_thai = 'hoat_dong'")->fetchColumn(),
    've' => $pdo->query("SELECT COUNT(*) FROM ve WHERE trang_thai = 'da_thanh_toan'")->fetchColumn(),
    'doanh_thu' => $pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM ve WHERE trang_thai = 'da_thanh_toan'")->fetchColumn(),
    'nguoi_dung' => $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'khach'")->fetchColumn(),
];
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 admin-sidebar d-none d-md-block">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="col-md-10 py-4">
            <h3 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard Quản trị</h3>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-lg-2">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo $stats['phim']; ?></h3>
                            <small class="text-muted">Phim</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo $stats['rap']; ?></h3>
                            <small class="text-muted">Rạp</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-info"><?php echo $stats['suat']; ?></h3>
                            <small class="text-muted">Suất chiếu</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-warning"><?php echo $stats['ve']; ?></h3>
                            <small class="text-muted">Vé đã bán</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-danger"><?php echo number_format($stats['doanh_thu']/1000); ?>k</h3>
                            <small class="text-muted">Doanh thu</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="text-secondary"><?php echo $stats['nguoi_dung']; ?></h3>
                            <small class="text-muted">Khách hàng</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Chào mừng Admin!</strong> Sử dụng menu bên trái để quản lý hệ thống.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>