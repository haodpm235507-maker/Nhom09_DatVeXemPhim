<?php
/**
 * Đăng xuất
 */
session_start();
session_unset();
session_destroy();
header('Location: /dat-ve-xem-phim/index.php');
exit;
?>