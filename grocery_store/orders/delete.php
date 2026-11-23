<?php
require "../config/auth.php";
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT item_name FROM orders WHERE order_id=? AND is_deleted=0");
$stmt->execute([$id]);
$item = $stmt->fetchColumn();

if ($item !== false) {
    // SOFT DELETE: the row stays in the table, it is only moved to the Recycle Bin.
    // last_updated=last_updated stops MySQL from changing the "last updated" time.
    $stmt = $pdo->prepare("UPDATE orders SET is_deleted=1, deleted_by=?, date_deleted=NOW(), last_updated=last_updated WHERE order_id=?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    log_action($pdo, 'DELETE', 'orders', $id, "Moved order '$item' to the Recycle Bin");
}
header("Location: index.php" . (($_GET['q'] ?? '') !== '' ? "?q=" . urlencode($_GET['q']) : ""));
exit;
