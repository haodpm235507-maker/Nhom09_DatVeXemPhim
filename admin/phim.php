<?php
/**
 * Quản lý phim - Danh sách
 */
$pageTitle = 'Quản lý phim';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();

// Xóa phim
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $pdo->prepare("DELETE FROM phim WHERE id = ?")->execute([$id]);
    redirect('phim.php', 'Đã xóa phim thành công.');
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT p.*, tl.ten_the_loai FROM phim p LEFT JOIN the_loai tl ON p.the_loai_id = tl.id WHERE 1=1";
$params = [];
if ($q) {
    $sql .= " AND p.ten_phim LIKE ?";
    $params[] = "%$q%";
}
$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsPhim = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="col-md-10 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3><i class="bi bi-film"></i> Quản lý phim</h3>
                <a href="them_phim.php" class="btn btn-primary"><i class="bi bi-plus"></i> Thêm phim</a>
            </div>

            <form class="mb-3 row g-2">
                <div class="col-auto">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm phim..." value="<?php echo clean($q); ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary">Tìm</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-bordered bg-white">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tên phim</th>
                            <th>Thể loại</th>
                            <th>Thời lượng</th>
                            <th>Khởi chiếu</th>
                            <th>Trạng thái</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dsPhim as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo clean($p['ten_phim']); ?></td>
                            <td><?php echo clean($p['ten_the_loai'] ?? '-'); ?></td>
                            <td><?php echo $p['thoi_luong']; ?> phút</td>
                            <td><?php echo date('d/m/Y', strtotime($p['ngay_khoi_chieu'])); ?></td>
                            <td>
                                <?php
                                $badge = ['dang_chieu'=>'success','sap_chieu'=>'warning','ngung_chieu'=>'secondary'];
                                $text = ['dang_chieu'=>'Đang chiếu','sap_chieu'=>'Sắp chiếu','ngung_chieu'=>'Ngừng chiếu'];
                                ?>
                                <span class="badge bg-<?php echo $badge[$p['trang_thai']]; ?>">
                                    <?php echo $text[$p['trang_thai']]; ?>
                                </span>
                            </td>
                            <td>
                                <a href="sua_phim.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <a href="?xoa=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Xóa phim này?')">Xóa</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>