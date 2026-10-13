<?php
/**
 * Các hàm tiện ích chung - Phiên bản 2.0
 * Thêm: CSRF, Logging, Hold ghế, Auto-cancel timeout
 */
require_once __DIR__ . '/../config/database.php';

// ============ CSRF ============
function taoCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function kiemTraCsrf($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(taoCsrfToken()) . '">';
}

// ============ LOGGING ============
function ghiNhatKy($hanhDong, $chiTiet = '', $userId = null) {
    try {
        $pdo = getConnection();
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $stmt = $pdo->prepare("INSERT INTO nhat_ky (nguoi_dung_id, hanh_dong, chi_tiet, ip_address, user_agent) VALUES (?,?,?,?,?)");
        $stmt->execute([$userId, $hanhDong, $chiTiet, $ip, $ua]);
    } catch (Exception $e) {
        // Không làm gián đoạn luồng chính
    }
}

// ============ GIÁ GHẾ ============
function layGiaGhe($loaiGhe, $giaCoBan) {
    switch ($loaiGhe) {
        case 'vip': return $giaCoBan + 20000;
        case 'doi': return $giaCoBan + 80000;
        default: return $giaCoBan;
    }
}

// ============ SUẤT CHIẾU ============
function kiemTraSuatChieuHopLe($suatChieuId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM suat_chieu WHERE id = ? AND trang_thai = 'hoat_dong'");
    $stmt->execute([$suatChieuId]);
    $suat = $stmt->fetch();
    if (!$suat) return false;
    $thoiGianBatDau = strtotime($suat['ngay_chieu'] . ' ' . $suat['gio_bat_dau']);
    if ($thoiGianBatDau - time() < 15 * 60) return false;
    return true;
}

// ============ GHẾ ĐÃ BÁN + ĐANG GIỮ ============
function layGheDaBan($suatChieuId) {
    $pdo = getConnection();
    // Ghế trong vé hợp lệ
    $stmt = $pdo->prepare("
        SELECT ctv.ghe_id FROM chi_tiet_ve ctv
        JOIN ve v ON ctv.ve_id = v.id
        WHERE v.suat_chieu_id = ? AND v.trang_thai IN ('cho_thanh_toan','da_thanh_toan','da_su_dung')
    ");
    $stmt->execute([$suatChieuId]);
    $daBan = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Ghế đang được người khác giữ (chưa hết hạn)
    $stmt2 = $pdo->prepare("
        SELECT ghe_id FROM ghe_giu 
        WHERE suat_chieu_id = ? AND het_han > NOW()
    ");
    $stmt2->execute([$suatChieuId]);
    $dangGiu = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    return array_unique(array_merge($daBan, $dangGiu));
}

function layGheDangGiuCuaToi($suatChieuId, $userId, $sessionId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT ghe_id FROM ghe_giu 
        WHERE suat_chieu_id = ? AND (nguoi_dung_id = ? OR session_id = ?) AND het_han > NOW()
    ");
    $stmt->execute([$suatChieuId, $userId, $sessionId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ============ HOLD GHẾ ============
function giuGhe($suatChieuId, $gheIds, $userId, $sessionId, $phutGiu = 5) {
    $pdo = getConnection();
    // Xóa hold cũ của user này trên suất này
    $pdo->prepare("DELETE FROM ghe_giu WHERE suat_chieu_id = ? AND (nguoi_dung_id = ? OR session_id = ?)")
        ->execute([$suatChieuId, $userId, $sessionId]);

    $hetHan = date('Y-m-d H:i:s', time() + $phutGiu * 60);
    $stmt = $pdo->prepare("INSERT INTO ghe_giu (suat_chieu_id, ghe_id, nguoi_dung_id, session_id, het_han) VALUES (?,?,?,?,?)");
    
    foreach ($gheIds as $gheId) {
        // Kiểm tra ghế còn trống
        $check = $pdo->prepare("
            SELECT 1 FROM chi_tiet_ve ctv JOIN ve v ON ctv.ve_id = v.id
            WHERE ctv.ghe_id = ? AND v.suat_chieu_id = ? AND v.trang_thai IN ('cho_thanh_toan','da_thanh_toan','da_su_dung')
        ");
        $check->execute([$gheId, $suatChieuId]);
        if ($check->fetch()) continue;

        $check2 = $pdo->prepare("SELECT 1 FROM ghe_giu WHERE suat_chieu_id = ? AND ghe_id = ? AND het_han > NOW() AND nguoi_dung_id != ?");
        $check2->execute([$suatChieuId, $gheId, $userId]);
        if ($check2->fetch()) continue;

        try {
            $stmt->execute([$suatChieuId, $gheId, $userId, $sessionId, $hetHan]);
        } catch (Exception $e) {
            // unique constraint - ghế đã bị giữ
        }
    }
    return $hetHan;
}

function xoaGiuGhe($suatChieuId, $userId, $sessionId) {
    $pdo = getConnection();
    $pdo->prepare("DELETE FROM ghe_giu WHERE suat_chieu_id = ? AND (nguoi_dung_id = ? OR session_id = ?)")
        ->execute([$suatChieuId, $userId, $sessionId]);
}

// ============ AUTO-CANCEL TIMEOUT ============
function tuDongHuyVeHetHan() {
    $pdo = getConnection();
    // Hủy vé chờ thanh toán đã quá hạn
    $stmt = $pdo->prepare("
        SELECT id FROM ve 
        WHERE trang_thai = 'cho_thanh_toan' 
          AND het_han_thanh_toan IS NOT NULL 
          AND het_han_thanh_toan < NOW()
    ");
    $stmt->execute();
    $veIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($veIds as $veId) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE ve SET trang_thai = 'da_huy' WHERE id = ?")->execute([$veId]);
            $pdo->prepare("DELETE FROM chi_tiet_ve WHERE ve_id = ?")->execute([$veId]);
            $pdo->commit();
            ghiNhatKy('TU_DONG_HUY_VE', "Vé ID $veId hết hạn thanh toán", null);
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }

    // Xóa hold ghế hết hạn
    $pdo->prepare("DELETE FROM ghe_giu WHERE het_han < NOW()")->execute();
    
    return count($veIds);
}

// ============ MÃ GIẢM GIÁ ============
function apDungMaGiamGia($maCode, $tongTien) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM ma_giam_gia WHERE ma_code = ?");
    $stmt->execute([strtoupper(trim($maCode))]);
    $ma = $stmt->fetch();
    
    if (!$ma) return ['success' => false, 'so_tien_giam' => 0, 'message' => 'Mã giảm giá không tồn tại.', 'ma_id' => null];
    if ($ma['trang_thai'] !== 'hoat_dong') return ['success' => false, 'so_tien_giam' => 0, 'message' => 'Mã giảm giá đã bị khóa hoặc hết hạn.', 'ma_id' => null];
    
    $homNay = date('Y-m-d');
    if ($homNay < $ma['ngay_bat_dau'] || $homNay > $ma['ngay_ket_thuc']) {
        return ['success' => false, 'so_tien_giam' => 0, 'message' => 'Mã giảm giá đã hết hạn hoặc chưa có hiệu lực.', 'ma_id' => null];
    }
    if ($ma['da_su_dung'] >= $ma['so_lan_su_dung']) {
        return ['success' => false, 'so_tien_giam' => 0, 'message' => 'Mã giảm giá đã hết lượt sử dụng.', 'ma_id' => null];
    }
    
    $soTienGiam = ($ma['loai_giam'] === 'phan_tram') 
        ? floor($tongTien * $ma['gia_tri'] / 100) 
        : min($ma['gia_tri'], $tongTien);
    
    return ['success' => true, 'so_tien_giam' => $soTienGiam, 'message' => 'Áp dụng mã thành công! Giảm ' . formatTien($soTienGiam), 'ma_id' => $ma['id']];
}

// ============ HỦY VÉ ============
function coTheHuyVe($veId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT v.*, sc.ngay_chieu, sc.gio_bat_dau 
        FROM ve v JOIN suat_chieu sc ON v.suat_chieu_id = sc.id WHERE v.id = ?
    ");
    $stmt->execute([$veId]);
    $ve = $stmt->fetch();
    
    if (!$ve) return ['ok' => false, 'message' => 'Không tìm thấy vé.'];
    if ($ve['trang_thai'] !== 'da_thanh_toan') return ['ok' => false, 'message' => 'Chỉ có thể hủy vé đã thanh toán.'];
    
    $thoiGianChieu = strtotime($ve['ngay_chieu'] . ' ' . $ve['gio_bat_dau']);
    if ($thoiGianChieu - time() < 30 * 60) {
        return ['ok' => false, 'message' => 'Chỉ được hủy vé trước giờ chiếu ít nhất 30 phút.'];
    }
    return ['ok' => true, 'message' => 'Có thể hủy vé.', 've' => $ve];
}

// ============ TIỆN ÍCH ============
function clean($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header('Location: ' . $url);
    exit;
}

function hienThiFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo clean($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Chạy auto-cancel mỗi khi load trang (đơn giản, không cần cron)
tuDongHuyVeHetHan();
