<?php
function validate_product_input($sku, $name, $price, $stock) {
    $errors = [];
    if (empty(trim($sku))) $errors[] = "SKU wajib diisi.";
    if (empty(trim($name))) $errors[] = "Nama produk wajib diisi.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Harga tidak boleh negatif.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "Stok tidak boleh negatif.";
    return $errors;
}
?>