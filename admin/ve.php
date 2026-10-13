<?php
/**
 * Quản lý vé
 */
$pageTitle = 'Quản lý vé';
require_once __DIR__ . '/../includes/header.php';
yeuCauAdmin();

$pdo = getConnection();

$dsVe = $pdo->query("
    SELECT v.*, nd.ho_ten, nd.email, p.ten_phim, sc.ngay_chieu, sc.gio_bat_dau, r.ten_rap
    FROM ve v
    JOIN nguoi_dung nd ON v.nguoi_dung_id = nd.id
    JOIN suat_chieu sc ON v.suat_chieu_id = sc.id
    JOIN phim p ON sc.phim_id = p.id
    JOIN phong_chieu pc ON sc.phong_id = pc.id
    JOIN rap r ON pc.rap_id = r.id
    ORDER BY v.ngay_dat DESC
    LIMIT 100
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 admin-sidebar d-none d-md-block"><?php include 'sidebar.php'; ?></div>
        <div class="col-md-10 py-4">
            <h3 class="mb-4">Quản lý vé / Đơn hàng</h3>
            
            <div class="table-responsive">
                <table class="table table-bordered bg-white table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Mã vé</th>
                            <th>Khách hàng</th>
                            <th>Phim</th>
                            <th>Rạp</th>
                            <th>Suất</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dsVe as $v): ?>
                        <tr>
                            <td><code><?php echo clean($v['ma_ve']); ?></code></td>
                            <td><?php echo clean($v['ho_ten']); ?><br><small><?php echo clean($v['email']); ?></small></td>
                            <td><?php echo clean($v['ten_phim']); ?></td>
                            <td><?php echo clean($v['ten_rap']); ?></td>
                            <td><?php echo date('d/m', strtotime($v['ngay_chieu'])).' '.substr($v['gio_bat_dau'],0,5); ?></td>
                            <td><?php echo number_format($v['tong_tien']); ?></td>
                            <td>
                                <?php
                                $c = ['cho_thanh_toan'=>'warning','da_thanh_toan'=>'success','da_huy'=>'danger','da_su_dung'=>'secondary'];
                                ?>
                                <span class="badge bg-<?php echo $c[$v['trang_thai']] ?? 'secondary'; ?>">
                                    <?php echo $v['trang_thai']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['ngay_dat'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>