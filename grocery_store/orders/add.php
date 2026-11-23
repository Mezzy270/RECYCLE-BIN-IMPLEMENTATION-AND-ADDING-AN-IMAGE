<?php
require "../config/auth.php";
require "../includes/upload.php";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // One optional image per order
    [$imagePath, $error] = save_order_image($_FILES['image'] ?? null);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Start at 1 and keep going until we find an unused ID
    $newId = 1;
    $check = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ?");
    $check->execute([$newId]);
    while ($check->fetch()) {
        $newId++;
        $check->execute([$newId]);
    }

    $stmt = $pdo->prepare("INSERT INTO orders (order_id, customer_id, item_name, quantity, total_price, order_date, added_by, last_updated_by, image_path) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$newId, $_POST['customer_id'], $_POST['item_name'], $_POST['quantity'], $_POST['total_price'], $_POST['order_date'], $_SESSION['user_id'], $_SESSION['user_id'], $imagePath]);
    log_action($pdo, 'INSERT', 'orders', $newId, "Added order '" . $_POST['item_name'] . "'");
    header("Location: index.php"); exit;
}
$customers = $pdo->query("SELECT customer_id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$products  = $pdo->query("SELECT product_name, price FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Add Order</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>
    <body>
        <?php include "../includes/navbar.php"; ?>
<div class="page">
        <h1>Add Order</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <select name="customer_id" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="item_name" id="item" list="productList" placeholder="Item name (pick from the list)" autocomplete="off" required>
            <datalist id="productList">
                <?php foreach ($products as $pr): ?>
                <option value="<?= htmlspecialchars($pr['product_name']) ?>">
                <?php endforeach; ?>
            </datalist>
            <input name="quantity" id="qty" type="number" min="1" placeholder="Quantity" required>
            <input name="total_price" id="total" type="number" step="0.01" placeholder="Total price" required>
            <input name="order_date" type="date" required>
            <label class="file-label">Order image (optional, JPG/PNG/GIF/WEBP, max 2 MB)
                <input name="image" type="file" accept="image/*" id="imgInput">
            </label>
            <img id="imgPreview" class="preview" alt="" style="display:none">
            <button type="submit">Save Changes</button>
            <a id="can" href="index.php">Cancel</a>
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
        // live preview of the chosen image
        document.getElementById('imgInput').addEventListener('change', function () {
            const p = document.getElementById('imgPreview');
            if (this.files[0]) { p.src = URL.createObjectURL(this.files[0]); p.style.display = 'block'; } else { p.style.display = 'none'; }
        });
    </script>
</body>
</html>
