<?php
require "../config/auth.php";
require "../includes/upload.php";
$error = "";
$id = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Keep the old image unless a new one is uploaded
    $old = $pdo->prepare("SELECT image_path FROM orders WHERE order_id=? AND is_deleted=0");
    $old->execute([$id]);
    $oldImage = $old->fetchColumn();
    [$newImage, $error] = save_order_image($_FILES['image'] ?? null);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $imagePath = $newImage ?: $oldImage;
    $stmt = $pdo->prepare("UPDATE orders SET customer_id=?, item_name=?, quantity=?, total_price=?, order_date=?, image_path=?, last_updated_by=?, last_updated=NOW() WHERE order_id=? AND is_deleted=0");
    $stmt->execute([$_POST['customer_id'], $_POST['item_name'], $_POST['quantity'], $_POST['total_price'], $_POST['order_date'], $imagePath, $_SESSION['user_id'], $id]);
    if ($newImage && $oldImage) { remove_order_image($oldImage); }   // replaced -> delete old file
    log_action($pdo, 'UPDATE', 'orders', $id, "Updated order '" . $_POST['item_name'] . "'");
    header("Location: " . "index.php" . (($_GET['q'] ?? '') !== '' ? "?q=" . urlencode($_GET['q']) : "")); exit;
}
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id=? AND is_deleted=0");
$stmt->execute([$id]);
$o = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$o) { die("Order not found."); }
$customers = $pdo->query("SELECT customer_id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$products  = $pdo->query("SELECT product_name, price FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Edit Order Information</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body>
        <?php include "../includes/navbar.php"; ?>
<div class="page">
        <h1>Edit Order Information</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="edit.php?id=<?= $id ?><?= ($_GET['q'] ?? '') !== '' ? '&q=' . urlencode($_GET['q']) : '' ?>">
            <select name="customer_id">
                <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>" <?= $c['customer_id'] == $o['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="item_name" id="item" list="productList" value="<?= htmlspecialchars($o['item_name']) ?>" autocomplete="off" required>
            <datalist id="productList">
                <?php foreach ($products as $pr): ?>
                <option value="<?= htmlspecialchars($pr['product_name']) ?>">
                <?php endforeach; ?>
            </datalist>
            <input name="quantity" id="qty" type="number" min="1" value="<?= $o['quantity'] ?>" required>
            <input name="total_price" id="total" type="number" step="0.01" value="<?= $o['total_price'] ?>" required>
            <input name="order_date" type="date" value="<?= $o['order_date'] ?>" required>
            <?php if (!empty($o['image_path'])): ?>
                <p class="file-label">Current image:</p>
                <img class="preview" style="display:block" src="../<?= htmlspecialchars($o['image_path']) ?>" alt="Order image">
            <?php endif; ?>
            <label class="file-label"><?= empty($o['image_path']) ? 'Order image (optional' : 'Replace image (optional' ?>, JPG/PNG/GIF/WEBP, max 2 MB)
                <input name="image" type="file" accept="image/*">
            </label>
            <button type="submit">Update</button> <button id="can"><a href="index.php<?= ($_GET['q'] ?? '') !== '' ? '?q=' . urlencode($_GET['q']) : '' ?>">Cancel</a></button>
        </form>
        <?php $sideClass = 'floating'; include "../includes/products_sidebar.php"; ?>
    </div>
    <script>
        // product name => unit price
        const prices = <?= json_encode(array_column($products, 'price', 'product_name')) ?>;
        const item  = document.getElementById('item');
        const qty   = document.getElementById('qty');
        const total = document.getElementById('total');
        function calc() {
            const p = prices[item.value];
            if (p !== undefined && qty.value) { total.value = (p * qty.value).toFixed(2); }
        }
        item.addEventListener('input', calc);
        qty.addEventListener('input', calc);
    </script>
</body>
</html>
