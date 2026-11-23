<?php
require "../config/auth.php";
// SEARCH: matches ID, name, email, phone or address
$q = trim($_GET['q'] ?? '');
$sql = "SELECT c.*,
               CONCAT(a.first_name,' ',a.last_name) AS added_name,
               CONCAT(u.first_name,' ',u.last_name) AS updated_name
        FROM customers c
        LEFT JOIN users a ON c.added_by = a.user_id
        LEFT JOIN users u ON c.last_updated_by = u.user_id";
$params = [];
if ($q !== '') {
    $sql .= " WHERE c.customer_id = ? OR c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.address LIKE ?";
    $like = "%" . $q . "%";
    $params = [ctype_digit($q) ? (int)$q : 0, $like, $like, $like, $like];
}
$sql .= " ORDER BY c.customer_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$qs = $q !== '' ? "&q=" . urlencode($q) : "";   // keeps the search after Edit/Delete
?>

<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8">
    <title>Grocery Store - Customer</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>
<body>
    <?php include "../includes/navbar.php"; ?>
<div class="page">
    <h1>Grocery Store - Customer's Information</h1>
    <p><a class="btn" id="ac" href="add.php">+ Add Customer</a></p>
    <form method="get" class="search-bar">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by ID, name, email, phone or address">
        <button type="submit">Search</button>
        <?php if ($q !== ''): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($q !== ''): ?>
        <p class="search-info"><?= count($customers) ?> result(s) for "<b><?= htmlspecialchars($q) ?></b>"</p>
    <?php endif; ?>
        <div class="table-wrap"><table>
        <tr><th>ID</th><th>Full Name</th><th>Email Address</th><th>Phone Number</th><th>Address</th><th>Added By</th><th>Date &amp; Time Added</th><th>Last Updated By</th><th>Last Updated (Date &amp; Time)</th><th>Actions</th></tr>
        <?php if (!$customers): ?>
            <tr><td colspan="10" style="text-align:center">No customer found.</td></tr>
        <?php endif; ?>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= $c['customer_id'] ?></td>
                <td><?= htmlspecialchars($c['full_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['phone']) ?></td>
                <td><?= htmlspecialchars($c['address']) ?></td>
                <td><?= htmlspecialchars($c['added_name'] ?? '—') ?></td>
                <td><?= date('M d, Y h:i:s A', strtotime($c['date_added'])) ?></td>
                <td><?= htmlspecialchars($c['updated_name'] ?? '—') ?></td>
                <td><?= date('M d, Y h:i:s A', strtotime($c['last_updated'])) ?></td>
                <td><a class="btn" id="ed" href="edit.php?id=<?= $c['customer_id'] ?><?= $qs ?>">Edit</a>
                <a class="btn del" id="de" href="delete.php?id=<?= $c['customer_id'] ?><?= $qs ?>" onclick="return confirm('Delete this customer and all their orders?')">Delete</a></td>
            </tr>
        <?php endforeach; ?>
        </table></div>
    </div>
</body>
</html>
