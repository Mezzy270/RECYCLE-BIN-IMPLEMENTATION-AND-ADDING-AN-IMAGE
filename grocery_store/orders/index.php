<?php
require "../config/auth.php";
// SEARCH: matches order ID, customer name, item name or order date
$q = trim($_GET['q'] ?? '');
$sql = "SELECT o.*, c.full_name,
               CONCAT(a.first_name,' ',a.last_name) AS added_name,
               CONCAT(u.first_name,' ',u.last_name) AS updated_name
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN users a ON o.added_by = a.user_id
        LEFT JOIN users u ON o.last_updated_by = u.user_id
        WHERE o.is_deleted = 0";          // deleted orders live in the Recycle Bin
$params = [];
if ($q !== '') {
    $sql .= " AND (o.order_id = ? OR c.full_name LIKE ? OR o.item_name LIKE ? OR o.order_date LIKE ?)";
    $like = "%" . $q . "%";
    $params = [ctype_digit($q) ? (int)$q : 0, $like, $like, $like];
}
$sql .= " ORDER BY o.order_id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$qs = $q !== '' ? "&q=" . urlencode($q) : "";   // keeps the search after Edit/Delete
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Grocery Store - Orders</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body>
        <?php include "../includes/navbar.php"; ?>
<div class="page">
        <h1>Grocery Store - Order's Information</h1>
        <p><a class="btn" id="ac" href="add.php">+ Add Order</a></p>
        <form method="get" class="search-bar">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by order ID, customer, item or date (YYYY-MM-DD)">
            <button type="submit">Search</button>
            <?php if ($q !== ''): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
        </form>
        <?php if ($q !== ''): ?>
            <p class="search-info"><?= count($orders) ?> result(s) for "<b><?= htmlspecialchars($q) ?></b>"</p>
        <?php endif; ?>
        <div class="layout"><div class="main"><div class="table-wrap"><table>
            <tr><th>Order ID</th><th>Customer</th><th>Image</th><th>Item</th><th>Quantity</th><th>Total Price</th><th>Order Date</th><th>Added By</th><th>Date &amp; Time Added</th><th>Last Updated By</th><th>Last Updated (Date &amp; Time)</th><th>Actions</th></tr>
            <?php if (!$orders): ?>
            <tr><td colspan="12" style="text-align:center">No order found.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= $o['order_id'] ?></td>
                <td><?= htmlspecialchars($o['full_name']) ?></td>
                <td><?php if ($o['image_path']): ?><a href="../<?= htmlspecialchars($o['image_path']) ?>" target="_blank"><img class="thumb" src="../<?= htmlspecialchars($o['image_path']) ?>" alt="Order image"></a><?php else: ?>—<?php endif; ?></td>
                <td><?= htmlspecialchars($o['item_name']) ?></td>
                <td><?= $o['quantity'] ?></td>
                <td><?= $o['total_price'] ?></td>
                <td><?= $o['order_date'] ?></td>
                <td><?= htmlspecialchars($o['added_name'] ?? '—') ?></td>
                <td><?= date('M d, Y h:i:s A', strtotime($o['date_added'])) ?></td>
                <td><?= htmlspecialchars($o['updated_name'] ?? '—') ?></td>
                <td><?= date('M d, Y h:i:s A', strtotime($o['last_updated'])) ?></td>
                <td><a class="btn" href="edit.php?id=<?= $o['order_id'] ?><?= $qs ?>">Edit</a>
                <a class="btn del" href="delete.php?id=<?= $o['order_id'] ?><?= $qs ?>" onclick="return confirm('Move this order to the Recycle Bin?')">Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </table></div></div><?php include "../includes/products_sidebar.php"; ?></div>
    </div>
</body>
</html>
