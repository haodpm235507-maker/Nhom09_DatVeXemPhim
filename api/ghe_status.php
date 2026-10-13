<?php
/**
 * API: Lấy trạng thái ghế real-time (AJAX)
 * GET ?suat_id=xxx
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$suatId = (int)($_GET['suat_id'] ?? 0);
if ($suatId <= 0) {
    echo json_encode(['error' => 'suat_id không hợp lệ']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$sessionId = session_id();

$daBan = layGheDaBan($suatId);
$cuaToi = layGheDangGiuCuaToi($suatId, $userId, $sessionId);

// Lấy thông tin hold của tôi (thời gian hết hạn)
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT MIN(het_han) as het_han FROM ghe_giu WHERE suat_chieu_id = ? AND (nguoi_dung_id = ? OR session_id = ?) AND het_han > NOW()");
$stmt->execute([$suatId, $userId, $sessionId]);
$holdInfo = $stmt->fetch();

echo json_encode([
    'success' => true,
    'ghe_da_ban' => array_map('intval', $daBan),
    'ghe_cua_toi' => array_map('intval', $cuaToi),
    'het_han_hold' => $holdInfo['het_han'] ?? null,
    'server_time' => date('Y-m-d H:i:s')
]);
