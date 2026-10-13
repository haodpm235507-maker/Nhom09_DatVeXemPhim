<?php
$pageTitle = 'Nhật ký';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();
$pdo = getConnection();
$ds = $pdo->query("
    SELECT nk.*, nd.ten_dang_nhap, nd.ho_ten 
    FROM nhat_ky nk LEFT JOIN nguoi_dung nd ON nk.nguoi_dung_id = nd.id
    ORDER BY nk.ngay_tao DESC LIMIT 200
")->fetchAll();
?>
<div class="container-fluid"><div class="row">
<div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
<div class="col-md-10 py-4">
<h3 class="mb-4">Nhật ký hệ thống</h3>
<table class="table table-bordered bg-white table-sm">
<thead class="table-dark"><tr><th>Thời gian</th><th>User</th><th>Hành động</th><th>Chi tiết</th><th>IP</th></tr></thead>
<tbody>
<?php foreach($ds as $l): ?>
<tr>
<td><?php echo $l['ngay_tao'];?></td>
<td><?php echo clean($l['ten_dang_nhap'] ?? 'System');?></td>
<td><code><?php echo clean($l['hanh_dong']);?></code></td>
<td class="small"><?php echo clean($l['chi_tiet'] ?? '');?></td>
<td class="small"><?php echo clean($l['ip_address'] ?? '');?></td>
</tr>
<?php endforeach;?>
</tbody></table>
</div></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
