<?php
require "../config/auth.php";
require "../includes/upload.php";
$id = (int)($_GET['id'] ?? 0);

// Only orders that are already in the Recycle Bin can be permanently deleted
$stmt = $pdo->prepare("SELECT item_name, image_path FROM orders WHERE order_id=? AND is_deleted=1");
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);

if ($o) {
    $pdo->prepare("DELETE FROM orders WHERE order_id=?")->execute([$id]);
    remove_order_image($o['image_path']);
    log_action($pdo, 'PURGE', 'orders', $id, "Permanently deleted order '" . $o['item_name'] . "'");
}
header("Location: recycle_bin.php?msg=purged");
exit;
