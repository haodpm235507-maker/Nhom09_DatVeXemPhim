<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$suatId = (int)($_POST['suat_id'] ?? 0);
$gheIds = $_POST['ghe_ids'] ?? [];
if ($suatId <= 0 || empty($gheIds) || !is_array($gheIds)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}
if (count($gheIds) > 8) {
    echo json_encode(['success' => false, 'message' => 'Tối đa 8 ghế']);
    exit;
}

$gheIds = array_map('intval', $gheIds);
$hetHan = giuGhe($suatId, $gheIds, $_SESSION['user_id'], session_id(), 5);
echo json_encode(['success' => true, 'het_han' => $hetHan]);
