<?php
/**
 * Quản lý người dùng
 */
$pageTitle = 'Người dùng';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();

// Khóa / Mở khóa
if (isset($_GET['khoa'])) {
    $id = (int)$_GET['khoa'];
    if ($id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE nguoi_dung SET trang_thai = 'khoa' WHERE id = ?")->execute([$id]);
        redirect('nguoi_dung.php', 'Đã khóa tài khoản.');
    }
}
if (isset($_GET['mo'])) {
    $id = (int)$_GET['mo'];
    $pdo->prepare("UPDATE nguoi_dung SET trang_thai = 'hoat_dong' WHERE id = ?")->execute([$id]);
    redirect('nguoi_dung.php', 'Đã mở khóa tài khoản.');
}

$dsUser = $pdo->query("SELECT * FROM nguoi_dung ORDER BY id")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Quản lý người dùng</h3>
            
            <table class="table table-bordered bg-white">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Username</th><th>SĐT</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($dsUser as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo clean($u['ho_ten']); ?></td>
                        <td><?php echo clean($u['email']); ?></td>
                        <td><?php echo clean($u['ten_dang_nhap']); ?></td>
                        <td><?php echo clean($u['so_dien_thoai']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $u['vai_tro']==='admin'?'primary':'secondary'; ?>">
                                <?php echo $u['vai_tro']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $u['trang_thai']==='hoat_dong'?'success':'danger'; ?>">
                                <?php echo $u['trang_thai']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id'] && $u['vai_tro'] !== 'admin'): ?>
                                <?php if ($u['trang_thai'] === 'hoat_dong'): ?>
                                    <a href="?khoa=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Khóa?')">Khóa</a>
                                <?php else: ?>
                                    <a href="?mo=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-success">Mở khóa</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>