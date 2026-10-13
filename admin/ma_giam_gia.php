<?php
$pageTitle = 'Mã giảm giá';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();
$pdo = getConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them'])) {
    if (!kiemTraCsrf($_POST['csrf_token'] ?? '')) $errors[] = 'CSRF invalid';
    else {
        $code = strtoupper(trim($_POST['ma_code'] ?? ''));
        $loai = $_POST['loai_giam'] ?? 'phan_tram';
        $giaTri = (int)($_POST['gia_tri'] ?? 0);
        $tu = $_POST['ngay_bat_dau'] ?? '';
        $den = $_POST['ngay_ket_thuc'] ?? '';
        $soLan = (int)($_POST['so_lan_su_dung'] ?? 100);
        if ($code === '' || strlen($code) > 20) $errors[] = 'Mã code 1-20 ký tự.';
        if ($giaTri <= 0) $errors[] = 'Giá trị phải > 0.';
        if ($loai === 'phan_tram' && $giaTri > 100) $errors[] = 'Phần trăm tối đa 100.';
        if (!$tu || !$den || $tu > $den) $errors[] = 'Ngày không hợp lệ.';
        if (empty($errors)) {
            try {
                $pdo->prepare("INSERT INTO ma_giam_gia (ma_code,loai_giam,gia_tri,ngay_bat_dau,ngay_ket_thuc,so_lan_su_dung) VALUES (?,?,?,?,?,?)")
                    ->execute([$code, $loai, $giaTri, $tu, $den, $soLan]);
                ghiNhatKy('THEM_MA_GIAM_GIA', $code);
                redirect('ma_giam_gia.php', 'Thêm mã thành công!');
            } catch (Exception $e) { $errors[] = 'Mã đã tồn tại hoặc lỗi DB.'; }
        }
    }
}
if (isset($_GET['khoa'])) {
    $pdo->prepare("UPDATE ma_giam_gia SET trang_thai='khoa' WHERE id=?")->execute([(int)$_GET['khoa']]);
    redirect('ma_giam_gia.php', 'Đã khóa mã.');
}
if (isset($_GET['mo'])) {
    $pdo->prepare("UPDATE ma_giam_gia SET trang_thai='hoat_dong' WHERE id=?")->execute([(int)$_GET['mo']]);
    redirect('ma_giam_gia.php', 'Đã mở mã.');
}
$ds = $pdo->query("SELECT * FROM ma_giam_gia ORDER BY id DESC")->fetchAll();
?>
<div class="container-fluid"><div class="row">
<div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
<div class="col-md-10 py-4">
<h3 class="mb-4">Quản lý mã giảm giá</h3>
<?php if($errors):?><div class="alert alert-danger"><?php foreach($errors as $e) echo clean($e).'<br>';?></div><?php endif;?>
<div class="card border-0 shadow-sm mb-4 p-3">
<form method="POST" class="row g-2 align-items-end">
<?php echo csrfField(); ?>
<div class="col-md-2"><input name="ma_code" class="form-control form-control-sm" placeholder="Mã code *" required maxlength="20"></div>
<div class="col-md-2"><select name="loai_giam" class="form-select form-select-sm"><option value="phan_tram">%</option><option value="so_tien">Số tiền</option></select></div>
<div class="col-md-1"><input type="number" name="gia_tri" class="form-control form-control-sm" placeholder="Giá trị" required min="1"></div>
<div class="col-md-2"><input type="date" name="ngay_bat_dau" class="form-control form-control-sm" required></div>
<div class="col-md-2"><input type="date" name="ngay_ket_thuc" class="form-control form-control-sm" required></div>
<div class="col-md-1"><input type="number" name="so_lan_su_dung" class="form-control form-control-sm" value="100" min="1"></div>
<div class="col-md-1"><button name="them" class="btn btn-primary btn-sm w-100">Thêm</button></div>
</form></div>
<table class="table table-bordered bg-white table-sm">
<thead class="table-dark"><tr><th>Mã</th><th>Loại</th><th>Giá trị</th><th>Từ - Đến</th><th>Đã dùng</th><th>TT</th><th></th></tr></thead>
<tbody>
<?php foreach($ds as $m): ?>
<tr>
<td><code><?php echo clean($m['ma_code']);?></code></td>
<td><?php echo $m['loai_giam'];?></td>
<td><?php echo $m['loai_giam']==='phan_tram' ? $m['gia_tri'].'%' : number_format($m['gia_tri']);?></td>
<td><?php echo $m['ngay_bat_dau'].' → '.$m['ngay_ket_thuc'];?></td>
<td><?php echo $m['da_su_dung'].'/'.$m['so_lan_su_dung'];?></td>
<td><span class="badge bg-<?php echo $m['trang_thai']==='hoat_dong'?'success':'danger';?>"><?php echo $m['trang_thai'];?></span></td>
<td>
<?php if($m['trang_thai']==='hoat_dong'):?>
<a href="?khoa=<?php echo $m['id'];?>" class="btn btn-sm btn-outline-danger">Khóa</a>
<?php else:?>
<a href="?mo=<?php echo $m['id'];?>" class="btn btn-sm btn-outline-success">Mở</a>
<?php endif;?>
</td></tr>
<?php endforeach;?>
</tbody></table>
</div></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
