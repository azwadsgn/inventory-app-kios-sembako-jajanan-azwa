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

$errors     = [];
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku         = trim($_POST['sku']);
    $name        = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];

    $errors = validate_product_input($sku, $name, $price, $stock);

    if (empty($errors)) {
        $stmt_check = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt_check->execute([$sku]);
        if ($stmt_check->rowCount() > 0) {
            $errors[] = "SKU produk tersebut sudah terdaftar. SKU harus unik!";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("INSERT INTO products (category_id, sku, name, price, stock) VALUES (?, ?, ?, ?, ?)")
                ->execute([$category_id, $sku, $name, $price, $stock]);
            echo "<script>alert('Produk berhasil ditambahkan!'); window.location='index.php';</script>";
            exit;
        } catch (\PDOException $e) {
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <div class="page-title">Tambah Produk</div>
        <div class="page-subtitle">Daftarkan produk baru ke sistem inventori</div>
    </div>
    <a href="index.php" class="btn btn-secondary">← Kembali</a>
</div>

<div style="max-width:600px;">
    <div class="card">
        <div style="padding:20px 24px; border-bottom:1px solid var(--border); background:var(--gray-50); border-radius:14px 14px 0 0;">
            <div style="font-size:1rem; font-weight:700; color:var(--green-700);">📦 Form Produk Baru</div>
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
                           placeholder="Contoh: BRS-001"
                           value="<?= escape($_POST['sku'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="Contoh: Beras Ramos 5kg"
                           value="<?= escape($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Pilih Kategori —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" step="0.01" name="price" class="form-control"
                               placeholder="0"
                               value="<?= escape($_POST['price'] ?? '') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" name="stock" class="form-control"
                               placeholder="0"
                               value="<?= escape($_POST['stock'] ?? '') ?>" required>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:24px;">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo "</div></body></html>"; ?>