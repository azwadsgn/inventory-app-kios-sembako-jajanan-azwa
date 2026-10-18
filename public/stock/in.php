<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../views/header.php';

$errors   = [];
$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];
    $notes      = trim($_POST['notes']);
    $user_id    = $_SESSION['user_id'];

    if (empty($product_id))  $errors[] = "Pilih produk terlebih dahulu.";
    if ($quantity <= 0)      $errors[] = "Jumlah barang masuk harus lebih besar dari 0.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO stock_transactions (product_id, user_id, type, quantity, notes) VALUES (?, ?, 'IN', ?, ?)")
                ->execute([$product_id, $user_id, $quantity, $notes]);
            $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                ->execute([$quantity, $product_id]);
            $pdo->commit();
            echo "<script>alert('Transaksi barang masuk berhasil dicatat!'); window.location='history.php';</script>";
            exit;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $errors[] = "Gagal memproses transaksi: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <div class="page-title">Barang Masuk</div>
        <div class="page-subtitle">Catat restock / penerimaan barang ke gudang</div>
    </div>
    <a href="history.php" class="btn btn-secondary">← Kembali ke Histori</a>
</div>

<div style="max-width:640px;">
    <div class="card">
        <div style="padding:20px 24px; border-bottom:1px solid var(--border); background:var(--gray-50); border-radius:14px 14px 0 0;">
            <div style="font-size:1rem; font-weight:700; color:var(--green-700);">📥 Form Barang Masuk (Restock)</div>
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
                    <label class="form-label">Pilih Produk</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">— Pilih Produk —</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= escape($p['sku']) ?> · <?= escape($p['name']) ?> (Stok: <?= $p['stock'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jumlah Masuk (pcs)</label>
                    <input type="number" name="quantity" class="form-control"
                           min="1" placeholder="Contoh: 50" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan / Sumber</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Contoh: Dari Distributor Beras Jaya"></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;">
                    <a href="history.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Barang Masuk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo "</div></body></html>"; ?>