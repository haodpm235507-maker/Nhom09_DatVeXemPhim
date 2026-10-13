<?php
/**
 * Chọn ghế v2 - Hold ghế + AJAX refresh + Countdown + CSRF + Max 8 ghế
 */
$pageTitle = 'Chọn ghế';
require_once __DIR__ . '/includes/header.php';
yeuCauDangNhap();

$suatId = (int)($_GET['suat_id'] ?? 0);
if ($suatId <= 0) redirect('danh_sach_phim.php', 'Suất chiếu không hợp lệ.', 'danger');

$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT sc.*, p.ten_phim, p.thoi_luong, pc.ten_phong, pc.id AS phong_id,
           r.ten_rap, r.dia_chi
    FROM suat_chieu sc
    JOIN phim p ON sc.phim_id = p.id
    JOIN phong_chieu pc ON sc.phong_id = pc.id
    JOIN rap r ON pc.rap_id = r.id
    WHERE sc.id = ?
");
$stmt->execute([$suatId]);
$suat = $stmt->fetch();
if (!$suat) redirect('danh_sach_phim.php', 'Suất chiếu không tồn tại.', 'danger');

$thoiGianBatDau = strtotime($suat['ngay_chieu'] . ' ' . $suat['gio_bat_dau']);
if ($suat['trang_thai'] !== 'hoat_dong' || $thoiGianBatDau - time() < 15 * 60) {
    redirect('chi_tiet_phim.php?id=' . $suat['phim_id'], 'Suất chiếu không còn hợp lệ để đặt vé.', 'danger');
}

$stmt = $pdo->prepare("SELECT * FROM ghe WHERE phong_id = ? ORDER BY ma_ghe");
$stmt->execute([$suat['phong_id']]);
$dsGhe = $stmt->fetchAll();

$gheDaBan = layGheDaBan($suatId);
$sessionId = session_id();
$gheCuaToi = layGheDangGiuCuaToi($suatId, $_SESSION['user_id'], $sessionId);

$gheTheoHang = [];
foreach ($dsGhe as $ghe) {
    $hang = substr($ghe['ma_ghe'], 0, 1);
    $gheTheoHang[$hang][] = $ghe;
}
ksort($gheTheoHang);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kiemTraCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ. Vui lòng thử lại.';
    } else {
        $gheChon = $_POST['ghe_chon'] ?? [];
        $maGiamGia = trim($_POST['ma_giam_gia'] ?? '');

        if (empty($gheChon) || !is_array($gheChon)) {
            $errors[] = 'Vui lòng chọn ít nhất 1 ghế.';
        } elseif (count($gheChon) > 8) {
            $errors[] = 'Bạn chỉ được chọn tối đa 8 ghế trong một lần đặt.';
        } else {
            $pdo->beginTransaction();
            try {
                $tongTien = 0;
                $chiTietGhe = [];
                foreach ($gheChon as $gheId) {
                    $gheId = (int)$gheId;
                    $stmt = $pdo->prepare("
                        SELECT g.*,
                               (SELECT COUNT(*) FROM chi_tiet_ve ctv JOIN ve v ON ctv.ve_id = v.id
                                WHERE ctv.ghe_id = g.id AND v.suat_chieu_id = ?
                                  AND v.trang_thai IN ('cho_thanh_toan','da_thanh_toan','da_su_dung')) AS da_ban
                        FROM ghe g WHERE g.id = ? AND g.phong_id = ? AND g.trang_thai = 'hoat_dong'
                        FOR UPDATE
                    ");
                    $stmt->execute([$suatId, $gheId, $suat['phong_id']]);
                    $ghe = $stmt->fetch();
                    if (!$ghe) throw new Exception('Ghế không hợp lệ hoặc đang bảo trì.');
                    if ($ghe['da_ban'] > 0) throw new Exception('Ghế ' . $ghe['ma_ghe'] . ' đã được người khác đặt.');

                    // Kiểm tra hold của người khác
                    $chk = $pdo->prepare("SELECT 1 FROM ghe_giu WHERE suat_chieu_id=? AND ghe_id=? AND het_han>NOW() AND nguoi_dung_id!=?");
                    $chk->execute([$suatId, $gheId, $_SESSION['user_id']]);
                    if ($chk->fetch()) throw new Exception('Ghế ' . $ghe['ma_ghe'] . ' đang được người khác giữ.');

                    $giaGhe = layGiaGhe($ghe['loai_ghe'], $suat['gia_ve_co_ban']);
                    $tongTien += $giaGhe;
                    $chiTietGhe[] = ['ghe_id' => $gheId, 'ma_ghe' => $ghe['ma_ghe'], 'gia' => $giaGhe];
                }

                $soTienGiam = 0; $maGiamGiaId = null;
                if ($maGiamGia !== '') {
                    $kq = apDungMaGiamGia($maGiamGia, $tongTien);
                    if (!$kq['success']) throw new Exception($kq['message']);
                    $soTienGiam = $kq['so_tien_giam'];
                    $maGiamGiaId = $kq['ma_id'];
                }
                $tongTienSauGiam = $tongTien - $soTienGiam;

                $maVe = taoMaVe();
                $hetHanTT = date('Y-m-d H:i:s', time() + 10 * 60); // 10 phút
                $stmt = $pdo->prepare("
                    INSERT INTO ve (ma_ve, nguoi_dung_id, suat_chieu_id, tong_tien, ma_giam_gia_id, so_tien_giam, trang_thai, het_han_thanh_toan)
                    VALUES (?,?,?,?,?,?,'cho_thanh_toan',?)
                ");
                $stmt->execute([$maVe, $_SESSION['user_id'], $suatId, $tongTienSauGiam, $maGiamGiaId, $soTienGiam, $hetHanTT]);
                $veId = $pdo->lastInsertId();

                $stmtCT = $pdo->prepare("INSERT INTO chi_tiet_ve (ve_id, ghe_id, gia_ghe) VALUES (?,?,?)");
                foreach ($chiTietGhe as $ct) {
                    $stmtCT->execute([$veId, $ct['ghe_id'], $ct['gia']]);
                }
                if ($maGiamGiaId) {
                    $pdo->prepare("UPDATE ma_giam_gia SET da_su_dung = da_su_dung + 1 WHERE id = ?")->execute([$maGiamGiaId]);
                }

                // Xóa hold ghế của user
                xoaGiuGhe($suatId, $_SESSION['user_id'], $sessionId);

                $pdo->commit();
                ghiNhatKy('DAT_VE', "Đặt vé $maVe - " . count($chiTietGhe) . " ghế - " . formatTien($tongTienSauGiam));
                redirect('thanh_toan.php?ve_id=' . $veId, 'Đã giữ ghế thành công! Vui lòng thanh toán trong 10 phút.');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <div>
            <strong><?php echo clean($suat['ten_phim']); ?></strong><br>
            <small>
                <i class="bi bi-geo-alt"></i> <?php echo clean($suat['ten_rap']); ?> - <?php echo clean($suat['ten_phong']); ?>
                · <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($suat['ngay_chieu'])); ?>
                · <i class="bi bi-clock"></i> <?php echo substr($suat['gio_bat_dau'],0,5); ?>
            </small>
        </div>
        <div class="text-end">
            <div id="countdownHold" class="badge bg-warning text-dark fs-6 d-none">
                Giữ ghế: <span id="timerHold">05:00</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btnRefreshGhe" title="Làm mới ghế">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>'.clean($e).'</div>'; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="seat-map-container">
                <div class="screen"></div>
                <?php foreach ($gheTheoHang as $hang => $ghes): ?>
                <div class="seat-row">
                    <span class="seat-row-label"><?php echo $hang; ?></span>
                    <?php foreach ($ghes as $ghe):
                        $isSold = in_array($ghe['id'], $gheDaBan) && !in_array($ghe['id'], $gheCuaToi);
                        $isMine = in_array($ghe['id'], $gheCuaToi);
                        $class = 'seat ';
                        if ($ghe['trang_thai'] === 'bao_tri') $class .= 'baotri';
                        elseif ($isSold) $class .= 'sold';
                        elseif ($isMine) $class .= 'selected ' . $ghe['loai_ghe'];
                        else $class .= 'available ' . $ghe['loai_ghe'];
                    ?>
                        <div class="<?php echo $class; ?>"
                             data-id="<?php echo $ghe['id']; ?>"
                             data-ma="<?php echo $ghe['ma_ghe']; ?>"
                             data-loai="<?php echo $ghe['loai_ghe']; ?>"
                             data-gia="<?php echo layGiaGhe($ghe['loai_ghe'], $suat['gia_ve_co_ban']); ?>"
                             title="<?php echo $ghe['ma_ghe'].' - '.ucfirst($ghe['loai_ghe']); ?>">
                            <?php echo substr($ghe['ma_ghe'], 1); ?>
                        </div>
                    <?php endforeach; ?>
                    <span class="seat-row-label"><?php echo $hang; ?></span>
                </div>
                <?php endforeach; ?>
                <div class="seat-legend">
                    <div class="seat-legend-item"><div class="seat-legend-box" style="background:var(--seat-available)"></div> Trống</div>
                    <div class="seat-legend-item"><div class="seat-legend-box" style="background:var(--seat-selected)"></div> Đang chọn</div>
                    <div class="seat-legend-item"><div class="seat-legend-box" style="background:var(--seat-sold)"></div> Đã bán / Đang giữ</div>
                    <div class="seat-legend-item"><div class="seat-legend-box" style="background:var(--seat-vip)"></div> VIP</div>
                    <div class="seat-legend-item"><div class="seat-legend-box" style="background:var(--seat-doi)"></div> Đôi</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="booking-summary">
                <h5 class="mb-3">Thông tin đặt vé</h5>
                <form method="POST" id="formDatGhe">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Ghế đã chọn <small class="text-muted">(tối đa 8)</small></label>
                        <div id="danhSachGheChon" class="border rounded p-2" style="min-height:60px;">
                            <span class="text-muted small">Chưa chọn ghế nào</span>
                        </div>
                        <div id="hiddenGheInputs"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mã giảm giá</label>
                        <input type="text" name="ma_giam_gia" class="form-control form-control-sm" placeholder="GIAM10, VIP30...">
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Số ghế:</span><strong id="soGhe">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tạm tính:</span><strong id="tamTinh" class="text-danger">0 VNĐ</strong>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="btnDatVe" disabled>
                        <i class="bi bi-ticket-perforated"></i> Tiếp tục thanh toán
                    </button>
                    <a href="chi_tiet_phim.php?id=<?php echo $suat['phim_id']; ?>" class="btn btn-outline-secondary w-100 mt-2">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const SUAT_ID = <?php echo $suatId; ?>;
const MAX_GHE = 8;
let selectedSeats = [];
let holdExpire = null;
let timerInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    const seats = document.querySelectorAll('.seat.available, .seat.selected');
    seats.forEach(seat => {
        if (seat.classList.contains('selected')) {
            selectedSeats.push({
                id: seat.dataset.id, ma: seat.dataset.ma,
                gia: parseInt(seat.dataset.gia), loai: seat.dataset.loai
            });
        }
        seat.addEventListener('click', function() {
            if (this.classList.contains('sold') || this.classList.contains('baotri')) return;
            const id = this.dataset.id;
            const idx = selectedSeats.findIndex(s => s.id === id);
            if (idx > -1) {
                selectedSeats.splice(idx, 1);
                this.classList.remove('selected');
                this.classList.add('available');
            } else {
                if (selectedSeats.length >= MAX_GHE) {
                    alert('Chỉ được chọn tối đa ' + MAX_GHE + ' ghế.');
                    return;
                }
                selectedSeats.push({id, ma: this.dataset.ma, gia: parseInt(this.dataset.gia), loai: this.dataset.loai});
                this.classList.remove('available');
                this.classList.add('selected');
            }
            updateSummary();
            holdSeatsAjax();
        });
    });
    updateSummary();

    // Auto refresh ghế mỗi 8 giây
    setInterval(refreshGheStatus, 8000);
    document.getElementById('btnRefreshGhe').addEventListener('click', refreshGheStatus);
});

function updateSummary() {
    const el = document.getElementById('danhSachGheChon');
    const hidden = document.getElementById('hiddenGheInputs');
    const btn = document.getElementById('btnDatVe');
    if (selectedSeats.length === 0) {
        el.innerHTML = '<span class="text-muted small">Chưa chọn ghế nào</span>';
        btn.disabled = true;
        document.getElementById('countdownHold').classList.add('d-none');
    } else {
        el.innerHTML = selectedSeats.map(s => `<span class="badge bg-warning text-dark me-1 mb-1">${s.ma}</span>`).join('');
        btn.disabled = false;
    }
    hidden.innerHTML = selectedSeats.map(s => `<input type="hidden" name="ghe_chon[]" value="${s.id}">`).join('');
    document.getElementById('soGhe').textContent = selectedSeats.length;
    document.getElementById('tamTinh').textContent = new Intl.NumberFormat('vi-VN').format(selectedSeats.reduce((a,s)=>a+s.gia,0)) + ' VNĐ';
}

function holdSeatsAjax() {
    if (selectedSeats.length === 0) return;
    const formData = new FormData();
    formData.append('suat_id', SUAT_ID);
    formData.append('action', 'hold');
    selectedSeats.forEach(s => formData.append('ghe_ids[]', s.id));
    fetch('api/hold_ghe.php', {method:'POST', body: formData})
        .then(r => r.json())
        .then(data => {
            if (data.success && data.het_han) {
                holdExpire = new Date(data.het_han.replace(' ','T'));
                startCountdown();
            }
        }).catch(()=>{});
}

function startCountdown() {
    document.getElementById('countdownHold').classList.remove('d-none');
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        const now = new Date();
        const diff = Math.max(0, Math.floor((holdExpire - now) / 1000));
        const m = String(Math.floor(diff/60)).padStart(2,'0');
        const s = String(diff%60).padStart(2,'0');
        document.getElementById('timerHold').textContent = m + ':' + s;
        if (diff <= 0) {
            clearInterval(timerInterval);
            alert('Hết thời gian giữ ghế! Vui lòng chọn lại.');
            location.reload();
        }
    }, 1000);
}

function refreshGheStatus() {
    fetch('api/ghe_status.php?suat_id=' + SUAT_ID)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            document.querySelectorAll('.seat').forEach(seat => {
                const id = parseInt(seat.dataset.id);
                if (seat.classList.contains('baotri')) return;
                const isSold = data.ghe_da_ban.includes(id) && !data.ghe_cua_toi.includes(id);
                const isMine = selectedSeats.some(s => parseInt(s.id) === id);
                seat.classList.remove('available','selected','sold');
                if (isSold) seat.classList.add('sold');
                else if (isMine) seat.classList.add('selected', seat.dataset.loai);
                else seat.classList.add('available', seat.dataset.loai);
            });
        }).catch(()=>{});
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
