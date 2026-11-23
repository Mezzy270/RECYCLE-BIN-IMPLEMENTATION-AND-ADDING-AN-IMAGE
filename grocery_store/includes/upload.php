<?php
// Saves ONE image for an order. Returns [path|null, error|null].
// $path is stored in orders.image_path (relative to the project root).
function save_order_image($file) {
    // No file chosen -> that's fine, image is optional
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) { return [null, null]; }
    if ($file['error'] !== UPLOAD_ERR_OK)  { return [null, "Image upload failed (error code " . $file['error'] . "). Try a smaller file."]; }
    if ($file['size'] > 2 * 1024 * 1024)   { return [null, "Image is too large. Maximum size is 2 MB."]; }

    // Check it is really an image (not just a renamed file)
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) { return [null, "Only JPG, PNG, GIF or WEBP images are allowed."]; }

    $dir = dirname(__DIR__) . "/uploads/orders/";
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }

    $name = "order_" . bin2hex(random_bytes(8)) . "." . $allowed[$info[2]];   // random name, safe extension
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) { return [null, "Could not save the image on the server."]; }
    return ["uploads/orders/" . $name, null];
}

// Deletes an image file from disk (used when replaced or permanently deleted)
function remove_order_image($path) {
    if (!$path) return;
    $full = dirname(__DIR__) . "/" . $path;
    if (strpos($path, "uploads/orders/") === 0 && is_file($full)) { @unlink($full); }
}
