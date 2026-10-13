<?php
/**
 * Quản lý rạp
 */
$pageTitle = 'Quản lý rạp';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();
$errors = [];

// Thêm rạp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them'])) {
    $tenRap = trim($_POST['ten_rap'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');
    $sdt = trim($_POST['so_dien_thoai'] ?? '');
    
    if ($tenRap === '') $errors[] = 'Tên rạp không được trống.';
    if ($diaChi === '') $errors[] = 'Địa chỉ không được trống.';
    
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO rap (ten_rap, dia_chi, so_dien_thoai) VALUES (?,?,?)")
            ->execute([$tenRap, $diaChi, $sdt]);
        redirect('rap.php', 'Thêm rạp thành công!');
    }
}

// Xóa
if (isset($_GET['xoa'])) {
    $pdo->prepare("DELETE FROM rap WHERE id = ?")->execute([(int)$_GET['xoa']]);
    redirect('rap.php', 'Đã xóa rạp.');
}

$dsRap = $pdo->query("SELECT * FROM rap ORDER BY id")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Quản lý rạp</h3>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e).'<br>'; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4 p-3">
                <h6>Thêm rạp mới</h6>
                <form method="POST" class="row g-2">
                    <div class="col-md-3"><input type="text" name="ten_rap" class="form-control form-control-sm" placeholder="Tên rạp *" required></div>
                    <div class="col-md-4"><input type="text" name="dia_chi" class="form-control form-control-sm" placeholder="Địa chỉ *" required></div>
                    <div class="col-md-2"><input type="text" name="so_dien_thoai" class="form-control form-control-sm" placeholder="SĐT"></div>
                    <div class="col-md-2"><button type="submit" name="them" class="btn btn-primary btn-sm w-100">Thêm</button></div>
                </form>
            </div>

            <table class="table table-bordered bg-white">
                <thead class="table-dark"><tr><th>ID</th><th>Tên rạp</th><th>Địa chỉ</th><th>SĐT</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($dsRap as $r): ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td><?php echo clean($r['ten_rap']); ?></td>
                        <td><?php echo clean($r['dia_chi']); ?></td>
                        <td><?php echo clean($r['so_dien_thoai']); ?></td>
                        <td><?php echo $r['trang_thai']; ?></td>
                        <td>
                            <a href="?xoa=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa?')">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>