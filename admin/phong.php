<?php
/**
 * Quản lý phòng chiếu
 */
$pageTitle = 'Phòng chiếu';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();
$dsRap = $pdo->query("SELECT * FROM rap WHERE trang_thai='hoat_dong'")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them'])) {
    $rapId = (int)$_POST['rap_id'];
    $tenPhong = trim($_POST['ten_phong'] ?? '');
    $soHang = (int)($_POST['so_hang'] ?? 0);
    $soCot = (int)($_POST['so_cot'] ?? 0);
    
    if ($rapId <= 0) $errors[] = 'Chọn rạp.';
    if ($tenPhong === '') $errors[] = 'Tên phòng không trống.';
    if ($soHang < 1 || $soHang > 20) $errors[] = 'Số hàng từ 1-20.';
    if ($soCot < 1 || $soCot > 20) $errors[] = 'Số cột từ 1-20.';
    
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO phong_chieu (rap_id, ten_phong, so_hang, so_cot) VALUES (?,?,?,?)")
            ->execute([$rapId, $tenPhong, $soHang, $soCot]);
        $phongId = $pdo->lastInsertId();
        
        // Tự tạo ghế
        $hangs = range('A', chr(ord('A') + $soHang - 1));
        $stmtGhe = $pdo->prepare("INSERT INTO ghe (phong_id, ma_ghe, loai_ghe) VALUES (?,?,?)");
        foreach ($hangs as $i => $h) {
            $loai = 'thuong';
            if ($i >= $soHang - 2) $loai = 'doi';
            elseif ($i >= floor($soHang/2) - 1 && $i <= floor($soHang/2) + 1) $loai = 'vip';
            for ($c = 1; $c <= $soCot; $c++) {
                $stmtGhe->execute([$phongId, $h.$c, $loai]);
            }
        }
        redirect('phong.php', 'Thêm phòng và tạo ghế thành công!');
    }
}

if (isset($_GET['xoa'])) {
    $pdo->prepare("DELETE FROM phong_chieu WHERE id = ?")->execute([(int)$_GET['xoa']]);
    redirect('phong.php', 'Đã xóa phòng.');
}

$dsPhong = $pdo->query("
    SELECT pc.*, r.ten_rap, (SELECT COUNT(*) FROM ghe WHERE phong_id=pc.id) AS so_ghe
    FROM phong_chieu pc JOIN rap r ON pc.rap_id = r.id ORDER BY r.id, pc.id
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Quản lý phòng chiếu</h3>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e).'<br>'; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4 p-3">
                <h6>Thêm phòng mới (tự tạo ghế)</h6>
                <form method="POST" class="row g-2">
                    <div class="col-md-3">
                        <select name="rap_id" class="form-select form-select-sm" required>
                            <option value="">-- Chọn rạp --</option>
                            <?php foreach ($dsRap as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo clean($r['ten_rap']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="text" name="ten_phong" class="form-control form-control-sm" placeholder="Tên phòng *" required></div>
                    <div class="col-md-2"><input type="number" name="so_hang" class="form-control form-control-sm" placeholder="Số hàng" min="1" max="20" value="8" required></div>
                    <div class="col-md-2"><input type="number" name="so_cot" class="form-control form-control-sm" placeholder="Số cột" min="1" max="20" value="10" required></div>
                    <div class="col-md-2"><button type="submit" name="them" class="btn btn-primary btn-sm w-100">Thêm</button></div>
                </form>
            </div>

            <table class="table table-bordered bg-white">
                <thead class="table-dark"><tr><th>ID</th><th>Rạp</th><th>Tên phòng</th><th>Hàng x Cột</th><th>Số ghế</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($dsPhong as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo clean($p['ten_rap']); ?></td>
                        <td><?php echo clean($p['ten_phong']); ?></td>
                        <td><?php echo $p['so_hang']; ?> x <?php echo $p['so_cot']; ?></td>
                        <td><?php echo $p['so_ghe']; ?></td>
                        <td><a href="?xoa=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa phòng và tất cả ghế?')">Xóa</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>