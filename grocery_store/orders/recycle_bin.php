<?php
require "../config/auth.php";
$bin = $pdo->query("SELECT o.*, c.full_name,
                           CONCAT(d.first_name,' ',d.last_name) AS deleted_name
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.customer_id
                    LEFT JOIN users d ON o.deleted_by = d.user_id
                    WHERE o.is_deleted = 1
                    ORDER BY o.date_deleted DESC")->fetchAll(PDO::FETCH_ASSOC);
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Grocery Store - Recycle Bin</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body>
        <?php include "../includes/navbar.php"; ?>
<div class="page">
        <h1>🗑️ Recycle Bin - Deleted Orders</h1>
        <?php if ($msg === 'restored'): ?><div class="ok">Order restored. It is back in the Orders table.</div><?php endif; ?>
        <?php if ($msg === 'purged'): ?><div class="ok">Order permanently deleted.</div><?php endif; ?>
        <p><a class="btn" href="index.php">← Back to Orders</a></p>
        <div class="table-wrap"><table>
            <tr><th>Order ID</th><th>Customer</th><th>Image</th><th>Item</th><th>Quantity</th><th>Total Price</th><th>Order Date</th><th>Deleted By</th><th>Date &amp; Time Deleted</th><th>Actions</th></tr>
            <?php if (!$bin): ?>
            <tr><td colspan="10" style="text-align:center">The Recycle Bin is empty.</td></tr>
            <?php endif; ?>
            <?php foreach ($bin as $o): ?>
            <tr>
                <td><?= $o['order_id'] ?></td>
                <td><?= htmlspecialchars($o['full_name']) ?></td>
                <td><?php if ($o['image_path']): ?><a href="../<?= htmlspecialchars($o['image_path']) ?>" target="_blank"><img class="thumb" src="../<?= htmlspecialchars($o['image_path']) ?>" alt="Order image"></a><?php else: ?>—<?php endif; ?></td>
                <td><?= htmlspecialchars($o['item_name']) ?></td>
                <td><?= $o['quantity'] ?></td>
                <td><?= $o['total_price'] ?></td>
                <td><?= $o['order_date'] ?></td>
                <td><?= htmlspecialchars($o['deleted_name'] ?? '—') ?></td>
                <td><?= date('M d, Y h:i:s A', strtotime($o['date_deleted'])) ?></td>
                <td><a class="btn" href="restore.php?id=<?= $o['order_id'] ?>" onclick="return confirm('Restore this order?')">Restore</a>
                <a class="btn del" href="purge.php?id=<?= $o['order_id'] ?>" onclick="return confirm('Permanently delete this order? This cannot be undone.')">Delete Permanently</a></td>
            </tr>
            <?php endforeach; ?>
        </table></div>
    </div>
</body>
</html>
