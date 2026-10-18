<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../views/header.php';

$query = "SELECT t.*, p.name as product_name, p.sku, u.username
          FROM stock_transactions t
          JOIN products p ON t.product_id = p.id
          LEFT JOIN users u ON t.user_id = u.id
          ORDER BY t.created_at DESC";
$transactions = $pdo->query($query)->fetchAll();
?>

<div class="page-header">
    <div>
        <div class="page-title">Histori Transaksi Stok</div>
        <div class="page-subtitle">Semua riwayat barang masuk dan keluar</div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="in.php" class="btn btn-primary btn-sm">+ Barang Masuk</a>
        <a href="out.php" class="btn btn-danger btn-sm">+ Barang Keluar</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨 Cetak</button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Tipe</th>
                    <th>SKU &amp; Produk</th>
                    <th>Jumlah</th>
                    <th>Operator</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td style="white-space:nowrap;color:var(--gray-500);font-size:0.82rem;">
                            <?= escape($t['created_at']) ?>
                        </td>
                        <td>
                            <?php if ($t['type'] === 'IN'): ?>
                                <span class="badge badge-green">▲ MASUK</span>
                            <?php else: ?>
                                <span class="badge badge-red">▼ KELUAR</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?= escape($t['sku']) ?></code>
                            <span style="margin-left:6px;"><?= escape($t['product_name']) ?></span>
                        </td>
                        <td><b><?= number_format($t['quantity'],0,',','.') ?></b> <span style="color:var(--gray-400);font-size:0.8rem;">pcs</span></td>
                        <td><?= escape($t['username'] ?? 'System') ?></td>
                        <td style="color:var(--gray-500);font-size:0.85rem;"><?= escape($t['notes'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <div style="font-size:2rem;margin-bottom:8px;">📋</div>
                            Belum ada histori transaksi.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo "</div></body></html>"; ?>