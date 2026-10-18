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
    $notes_select = $_POST['notes_select'] ?? '';
    $notes_other  = trim($_POST['notes_other'] ?? '');
    $notes        = ($notes_select === 'Lainnya' && $notes_other !== '')
                    ? 'Lainnya: ' . $notes_other
                    : $notes_select;
    $user_id    = $_SESSION['user_id'];

    if (empty($product_id)) $errors[] = "Pilih produk terlebih dahulu.";
    if ($quantity <= 0)     $errors[] = "Jumlah barang keluar harus lebih besar dari 0.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt_check = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
            $stmt_check->execute([$product_id]);
            $prod = $stmt_check->fetch();

            if (!$prod) throw new \Exception("Produk tidak ditemukan.");
            if ($quantity > $prod['stock']) {
                throw new \Exception("Stok tidak mencukupi! '{$prod['name']}' saat ini: {$prod['stock']} pcs.");
            }

            $pdo->prepare("INSERT INTO stock_transactions (product_id, user_id, type, quantity, notes) VALUES (?, ?, 'OUT', ?, ?)")
                ->execute([$product_id, $user_id, $quantity, $notes]);
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                ->execute([$quantity, $product_id]);

            $pdo->commit();
            echo "<script>alert('Transaksi barang keluar berhasil dicatat!'); window.location='history.php';</script>";
            exit;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <div class="page-title">Barang Keluar</div>
        <div class="page-subtitle">Catat penjualan / pengeluaran barang dari gudang</div>
    </div>
    <a href="history.php" class="btn btn-secondary">← Kembali ke Histori</a>
</div>

<div style="max-width:640px;">
    <div class="card">
        <div style="padding:20px 24px; border-bottom:1px solid var(--border); background:#fef2f2; border-radius:14px 14px 0 0;">
            <div style="font-size:1rem; font-weight:700; color:#b91c1c;">📤 Form Barang Keluar (Penjualan)</div>
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
                            <?= escape($p['sku']) ?> · <?= escape($p['name']) ?> (Tersedia: <?= $p['stock'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jumlah Keluar (pcs)</label>
                    <input type="number" name="quantity" class="form-control"
                           min="1" placeholder="Contoh: 5" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan / Tujuan</label>
                    <select name="notes_select" id="notesSelect" class="form-control" required
                            onchange="toggleOther(this.value)">
                        <option value="">— Pilih Keterangan —</option>
                        <option value="Terjual ke pembeli">🛒 Terjual ke pembeli</option>
                        <option value="Barang rusak">⚠️ Barang rusak</option>
                        <option value="Barang kadaluarsa">📅 Barang kadaluarsa</option>
                        <option value="Lainnya">✏️ Lainnya…</option>
                    </select>
                </div>

                <div class="form-group" id="otherGroup" style="display:none; margin-top:-10px;">
                    <label class="form-label">Keterangan Lainnya <span style="font-weight:400;color:var(--text-muted);">(opsional)</span></label>
                    <input type="text" name="notes_other" id="notesOther" class="form-control"
                           placeholder="Tuliskan keterangan…">
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;">
                    <a href="history.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-danger">Simpan Barang Keluar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleOther(val) {
        const group = document.getElementById('otherGroup');
        const input = document.getElementById('notesOther');
        if (val === 'Lainnya') {
            group.style.display = 'block';
            input.focus();
        } else {
            group.style.display = 'none';
            input.value = '';
        }
    }
</script>
<?php echo "</div></body></html>"; ?>