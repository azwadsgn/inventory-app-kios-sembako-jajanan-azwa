<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

// Hanya admin yang boleh akses
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../../views/header.php';

$errors  = [];
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku         = trim($_POST['sku']);
    $name        = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];

    $errors = validate_product_input($sku, $name, $price, $stock);

    if (empty($errors)) {
        $stmt_check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $stmt_check->execute([$sku, $id]);
        if ($stmt_check->rowCount() > 0) $errors[] = "SKU sudah digunakan oleh produk lain!";
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE products SET category_id=?, sku=?, name=?, price=?, stock=? WHERE id=?")
                ->execute([$category_id, $sku, $name, $price, $stock, $id]);
            echo "<script>alert('Produk berhasil diperbarui!'); window.location='index.php';</script>";
            exit;
        } catch (\PDOException $e) {
            $errors[] = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <div class="page-title">Edit Produk</div>
        <div class="page-subtitle">Perbarui data produk: <strong><?= escape($product['name']) ?></strong></div>
    </div>
    <a href="index.php" class="btn btn-secondary">← Kembali</a>
</div>

<div style="max-width:600px;">
    <div class="card">
        <div style="padding:20px 24px; border-bottom:1px solid var(--border); background:#fffbeb; border-radius:14px 14px 0 0;">
            <div style="font-size:1rem; font-weight:700; color:#92400e;">✏️ Edit Produk</div>
        </div>
        <div style="padding:28px 24px;">

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <?php foreach ($errors as $err): ?>
                    <div>⚠ <?= escape($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">SKU Produk <span style="color:var(--danger);font-size:0.75rem;">(unik)</span></label>
                    <input type="text" name="sku" class="form-control"
                           value="<?= escape($product['sku']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= escape($product['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Pilih Kategori —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= escape($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                               value="<?= escape($product['price']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" class="form-control"
                               value="<?= escape($product['stock']) ?>" required>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:24px;">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-warning">Perbarui Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo "</div></body></html>"; ?>