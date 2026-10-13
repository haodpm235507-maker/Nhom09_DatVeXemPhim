<?php
/**
 * Danh sách phim - Tìm kiếm & Lọc
 */
$pageTitle = 'Danh sách phim';
require_once __DIR__ . '/includes/header.php';

$pdo = getConnection();

// Lấy tham số
$q = trim($_GET['q'] ?? '');
$theLoaiId = (int)($_GET['the_loai'] ?? 0);
$trangThai = $_GET['trang_thai'] ?? '';

// Lấy thể loại
$dsTheLoai = $pdo->query("SELECT * FROM the_loai ORDER BY ten_the_loai")->fetchAll();

// Build query
$sql = "SELECT p.*, tl.ten_the_loai FROM phim p LEFT JOIN the_loai tl ON p.the_loai_id = tl.id WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND p.ten_phim LIKE ?";
    $params[] = '%' . $q . '%';
}
if ($theLoaiId > 0) {
    $sql .= " AND p.the_loai_id = ?";
    $params[] = $theLoaiId;
}
if (in_array($trangThai, ['dang_chieu', 'sap_chieu', 'ngung_chieu'])) {
    $sql .= " AND p.trang_thai = ?";
    $params[] = $trangThai;
}

$sql .= " ORDER BY p.ngay_khoi_chieu DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsPhim = $stmt->fetchAll();
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-collection-play"></i> Danh sách phim</h2>

    <!-- Filter -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="q" class="form-control" placeholder="Tên phim..." value="<?php echo clean($q); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Thể loại</label>
                    <select name="the_loai" class="form-select">
                        <option value="">Tất cả thể loại</option>
                        <?php foreach ($dsTheLoai as $tl): ?>
                            <option value="<?php echo $tl['id']; ?>" <?php echo $theLoaiId == $tl['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($tl['ten_the_loai']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="dang_chieu" <?php echo $trangThai === 'dang_chieu' ? 'selected' : ''; ?>>Đang chiếu</option>
                        <option value="sap_chieu" <?php echo $trangThai === 'sap_chieu' ? 'selected' : ''; ?>>Sắp chiếu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kết quả -->
    <p class="text-muted">Tìm thấy <strong><?php echo count($dsPhim); ?></strong> phim</p>

    <?php if (empty($dsPhim)): ?>
        <div class="alert alert-info">Không tìm thấy phim nào phù hợp.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($dsPhim as $phim): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card movie-card">
                    <div class="position-relative">
                        <div class="movie-poster-placeholder">
                            <i class="bi bi-film"></i>
                        </div>
                        <?php if ($phim['trang_thai'] === 'dang_chieu'): ?>
                            <span class="badge bg-danger badge-trang-thai">Đang chiếu</span>
                        <?php elseif ($phim['trang_thai'] === 'sap_chieu'): ?>
                            <span class="badge bg-warning text-dark badge-trang-thai">Sắp chiếu</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title text-truncate" title="<?php echo clean($phim['ten_phim']); ?>">
                            <?php echo clean($phim['ten_phim']); ?>
                        </h6>
                        <p class="card-text small text-muted mb-2">
                            <?php echo clean($phim['ten_the_loai'] ?? 'N/A'); ?> · <?php echo $phim['thoi_luong']; ?> phút
                        </p>
                        <a href="chi_tiet_phim.php?id=<?php echo $phim['id']; ?>" class="btn btn-primary btn-sm w-100">
                            Chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>