<?php
require "../config/auth.php";
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT item_name FROM orders WHERE order_id=? AND is_deleted=1");
$stmt->execute([$id]);
$item = $stmt->fetchColumn();

if ($item !== false) {
    $stmt = $pdo->prepare("UPDATE orders SET is_deleted=0, deleted_by=NULL, date_deleted=NULL, last_updated=last_updated WHERE order_id=?");
    $stmt->execute([$id]);
    log_action($pdo, 'RESTORE', 'orders', $id, "Restored order '$item' from the Recycle Bin");
}
header("Location: recycle_bin.php?msg=restored");
exit;
