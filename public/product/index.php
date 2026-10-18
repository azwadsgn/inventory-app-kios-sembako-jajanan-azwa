<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../views/header.php';

$limit       = 10;
$page        = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset      = ($page - 1) * $limit;
$search      = isset($_GET['search'])      ? trim($_GET['search'])      : '';
$category_id = isset($_GET['category_id']) ? $_GET['category_id']       : '';

$query  = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
if (!empty($search))      { $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if (!empty($category_id)) { $query .= " AND p.category_id = ?";               $params[] = $category_id; }

$stmt_total = $pdo->prepare($query);
$stmt_total->execute($params);
$total_data  = $stmt_total->rowCount();
$total_pages = ceil($total_data / $limit);

$query .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
$stmt  = $pdo->prepare($query);
$stmt->execute($params);
$products   = $stmt->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="page-header">
    <div>
        <div class="page-title">Daftar Produk</div>
        <div class="page-subtitle">Manajemen produk kios sembako</div>
    </div>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="create.php" class="btn btn-primary">+ Tambah Produk</a>
    <?php endif; ?>
</div>

<!-- Search / Filter bar -->
<form method="GET" style="display:flex; gap:10px; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
    <input type="text" name="search" class="form-control"
           style="max-width:280px; flex:1;"
           placeholder="Cari nama atau SKU..."
           value="<?= escape($search) ?>">
    <select name="category_id" class="form-control" style="max-width:200px;">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
            <?= escape($cat['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if ($search || $category_id): ?>
    <a href="index.php" class="btn btn-secondary">Reset</a>
    <?php endif; ?>
</form>

<div class="card">
    <div style="overflow-x:auto;">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><code><?= escape($p['sku']) ?></code></td>
                        <td style="font-weight:500;"><?= escape($p['name']) ?></td>
                        <td style="color:var(--gray-500);font-size:0.85rem;">
                            <?= escape($p['category_name'] ?? 'Tanpa Kategori') ?>
                        </td>
                        <td style="font-weight:600;">Rp <?= number_format($p['price'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge <?= $p['stock'] > 5 ? 'badge-green' : 'badge-red' ?>">
                                <?= $p['stock'] ?> pcs
                            </span>
                        </td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin hapus produk ini?')">Hapus</a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $_SESSION['role'] === 'admin' ? 6 : 5 ?>"
                            style="text-align:center; padding:40px; color:var(--text-muted);">
                            <div style="font-size:2rem;margin-bottom:8px;">📦</div>
                            Belum ada produk yang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex; justify-content:center; gap:4px;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category_id=<?= urlencode($category_id) ?>"
           style="display:inline-flex; align-items:center; padding:6px 12px;
                  border:1px solid <?= $page == $i ? '#16a34a' : 'var(--border)' ?>;
                  border-radius:8px; font-size:0.85rem; text-decoration:none;
                  background:<?= $page == $i ? '#16a34a' : '#fff' ?>;
                  color:<?= $page == $i ? '#fff' : 'var(--gray-700)' ?>;">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php echo "</div></body></html>"; ?>