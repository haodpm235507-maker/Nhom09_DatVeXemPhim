<?php
/**
 * Chi tiết phim + Chọn suất chiếu
 */
$pageTitle = 'Chi tiết phim';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('danh_sach_phim.php', 'Phim không tồn tại.', 'danger');
}

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT p.*, tl.ten_the_loai 
    FROM phim p 
    LEFT JOIN the_loai tl ON p.the_loai_id = tl.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$phim = $stmt->fetch();

if (!$phim) {
    redirect('danh_sach_phim.php', 'Phim không tồn tại.', 'danger');
}

$pageTitle = $phim['ten_phim'];

// Lấy suất chiếu sắp tới
$stmt = $pdo->prepare("
    SELECT sc.*, pc.ten_phong, r.ten_rap, r.id AS rap_id
    FROM suat_chieu sc
    JOIN phong_chieu pc ON sc.phong_id = pc.id
    JOIN rap r ON pc.rap_id = r.id
    WHERE sc.phim_id = ? 
      AND sc.trang_thai = 'hoat_dong'
      AND CONCAT(sc.ngay_chieu, ' ', sc.gio_bat_dau) > NOW()
    ORDER BY sc.ngay_chieu, sc.gio_bat_dau
");
$stmt->execute([$id]);
$dsSuat = $stmt->fetchAll();

// Nhóm theo ngày và rạp
$suatTheoNgay = [];
foreach ($dsSuat as $s) {
    $ngay = $s['ngay_chieu'];
    $rapId = $s['rap_id'];
    $suatTheoNgay[$ngay][$rapId]['ten_rap'] = $s['ten_rap'];
    $suatTheoNgay[$ngay][$rapId]['suat'][] = $s;
}
?>

<div class="container py-4">
    <div class="row">
        <!-- Thông tin phim -->
        <div class="col-md-4 mb-4">
            <div class="movie-poster-placeholder rounded" style="height: 400px;">
                <i class="bi bi-film" style="font-size: 4rem;"></i>
            </div>
        </div>
        <div class="col-md-8">
            <h2 class="mb-2"><?php echo clean($phim['ten_phim']); ?></h2>
            <div class="mb-3">
                <?php if ($phim['trang_thai'] === 'dang_chieu'): ?>
                    <span class="badge bg-danger">Đang chiếu</span>
                <?php elseif ($phim['trang_thai'] === 'sap_chieu'): ?>
                    <span class="badge bg-warning text-dark">Sắp chiếu</span>
                <?php endif; ?>
                <span class="badge bg-secondary"><?php echo clean($phim['ten_the_loai'] ?? 'N/A'); ?></span>
                <span class="badge bg-info"><?php echo $phim['thoi_luong']; ?> phút</span>
            </div>
            
            <table class="table table-borderless table-sm">
                <tr>
                    <td width="120" class="text-muted">Đạo diễn</td>
                    <td><?php echo clean($phim['dao_dien'] ?? 'Đang cập nhật'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Diễn viên</td>
                    <td><?php echo clean($phim['dien_vien'] ?? 'Đang cập nhật'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Khởi chiếu</td>
                    <td><?php echo date('d/m/Y', strtotime($phim['ngay_khoi_chieu'])); ?></td>
                </tr>
            </table>
            
            <h5>Nội dung</h5>
            <p class="text-muted"><?php echo nl2br(clean($phim['mo_ta'] ?? 'Đang cập nhật...')); ?></p>
        </div>
    </div>

    <hr class="my-4">

    <!-- Chọn suất chiếu -->
    <h4 class="mb-3"><i class="bi bi-calendar3"></i> Lịch chiếu</h4>

    <?php if (empty($suatTheoNgay)): ?>
        <div class="alert alert-warning">
            Hiện chưa có suất chiếu nào cho phim này. Vui lòng quay lại sau.
        </div>
    <?php else: ?>
        <?php foreach ($suatTheoNgay as $ngay => $raps): ?>
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <strong><?php 
                        $thu = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
                        echo $thu[date('w', strtotime($ngay))] . ' - ' . date('d/m/Y', strtotime($ngay)); 
                    ?></strong>
                </div>
                <div class="card-body">
                    <?php foreach ($raps as $rapId => $rapData): ?>
                        <div class="mb-3">
                            <h6 class="text-primary"><i class="bi bi-geo-alt"></i> <?php echo clean($rapData['ten_rap']); ?></h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($rapData['suat'] as $suat): ?>
                                    <a href="chon_ghe.php?suat_id=<?php echo $suat['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <?php echo substr($suat['gio_bat_dau'], 0, 5); ?>
                                        <small class="d-block text-muted"><?php echo clean($suat['ten_phong']); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>