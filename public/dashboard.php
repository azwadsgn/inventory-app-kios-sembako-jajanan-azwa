<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../views/header.php';

$isAdmin      = ($_SESSION['role'] === 'admin');
$display_name = escape($_SESSION['display_name'] ?? ucfirst($_SESSION['role']));

// ─── Filter periode ──────────────────────────────────────────────────────────
$period   = $_GET['period']   ?? 'all';
$date_val = trim($_GET['date_val'] ?? '');

// Validasi & normalisasi
$where_date   = '1=1';
$period_label = 'Semua Waktu';

switch ($period) {
    case 'daily':
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_val)) {
            $date_val = date('Y-m-d');
        }
        $where_date   = "DATE(t.created_at) = '$date_val'";
        $period_label = date('d F Y', strtotime($date_val));
        break;

    case 'monthly':
        if (!preg_match('/^\d{4}-\d{2}$/', $date_val)) {
            $date_val = date('Y-m');
        }
        [$y, $m] = explode('-', $date_val);
        $where_date   = "YEAR(t.created_at)=$y AND MONTH(t.created_at)=$m";
        $period_label = date('F Y', mktime(0,0,0,$m,1,$y));
        break;

    case 'yearly':
        if (!preg_match('/^\d{4}$/', $date_val)) {
            $date_val = date('Y');
        }
        $where_date   = "YEAR(t.created_at) = $date_val";
        $period_label = "Tahun $date_val";
        break;

    default:
        $period   = 'all';
        $date_val = '';
}

// ─── Statistik ───────────────────────────────────────────────────────────────
$stats         = $pdo->query("SELECT COUNT(*) as total_products, SUM(stock) as total_stock FROM products")->fetch();

// Transaksi IN/OUT dengan filter
$trans_summary = $pdo->query("
    SELECT
        COALESCE(SUM(CASE WHEN type='IN'  THEN quantity END),0) as total_in,
        COALESCE(SUM(CASE WHEN type='OUT' THEN quantity END),0) as total_out
    FROM stock_transactions t
    WHERE $where_date
")->fetch();

// Top 5 produk terlaris dengan filter
$top_products = $pdo->query("
    SELECT p.name, SUM(t.quantity) as total_sold
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.type = 'OUT' AND $where_date
    GROUP BY t.product_id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// ─── Admin only ───────────────────────────────────────────────────────────────
$total_revenue  = 0;
$revenue_trend  = [];
if ($isAdmin) {
    $total_revenue = $pdo->query("
        SELECT COALESCE(SUM(t.quantity * p.price),0) as rev
        FROM stock_transactions t
        JOIN products p ON t.product_id = p.id
        WHERE t.type='OUT' AND $where_date
    ")->fetch()['rev'] ?? 0;

    // Tren pendapatan — granularity tergantung periode
    switch ($period) {
        case 'daily':
            [$y,$mo,$d] = explode('-', $date_val);
            $trend_sql = "
                SELECT DATE_FORMAT(t.created_at,'%H:00') as label,
                       SUM(t.quantity * p.price) as revenue
                FROM stock_transactions t JOIN products p ON t.product_id=p.id
                WHERE t.type='OUT' AND DATE(t.created_at)='$date_val'
                GROUP BY HOUR(t.created_at) ORDER BY HOUR(t.created_at)";
            break;
        case 'monthly':
            [$y,$m] = explode('-', $date_val);
            $trend_sql = "
                SELECT DATE_FORMAT(t.created_at,'%d %b') as label,
                       SUM(t.quantity * p.price) as revenue
                FROM stock_transactions t JOIN products p ON t.product_id=p.id
                WHERE t.type='OUT' AND YEAR(t.created_at)=$y AND MONTH(t.created_at)=$m
                GROUP BY DATE(t.created_at) ORDER BY DATE(t.created_at)";
            break;
        case 'yearly':
            $trend_sql = "
                SELECT DATE_FORMAT(t.created_at,'%b') as label,
                       SUM(t.quantity * p.price) as revenue
                FROM stock_transactions t JOIN products p ON t.product_id=p.id
                WHERE t.type='OUT' AND YEAR(t.created_at)=$date_val
                GROUP BY MONTH(t.created_at) ORDER BY MONTH(t.created_at)";
            break;
        default:
            $trend_sql = "
                SELECT DATE_FORMAT(t.created_at,'%b %Y') as label,
                       SUM(t.quantity * p.price) as revenue
                FROM stock_transactions t JOIN products p ON t.product_id=p.id
                WHERE t.type='OUT'
                GROUP BY YEAR(t.created_at),MONTH(t.created_at)
                ORDER BY YEAR(t.created_at),MONTH(t.created_at) LIMIT 12";
    }
    $revenue_trend = $pdo->query($trend_sql)->fetchAll();
}

// Rentang tahun untuk pilihan yearly
$year_range = range(date('Y'), 2020);
?>

<style>
/* ── Filter bar ─────────────────────────────────────────────────────────────── */
.period-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 5px 6px;
    box-shadow: var(--shadow-xs);
    flex-wrap: wrap;
}

.period-tab {
    padding: 6px 14px;
    border: none;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    background: transparent;
    color: var(--text-secondary);
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
    font-family: 'Inter', sans-serif;
}

.period-tab:hover { background: var(--gray-100); color: var(--gray-900); }
.period-tab.active { background: var(--green-600); color: #fff; }

.period-date-input {
    display: none;
    align-items: center;
    gap: 6px;
}

.period-date-input.visible { display: flex; }

.period-date-input input,
.period-date-input select {
    padding: 6px 10px;
    border: 1.5px solid var(--border);
    border-radius: 7px;
    font-family: 'Inter', sans-serif;
    font-size: 0.82rem;
    color: var(--gray-700);
    background: var(--gray-50);
    outline: none;
    cursor: pointer;
}

.period-date-input input:focus,
.period-date-input select:focus {
    border-color: var(--green-500);
    box-shadow: 0 0 0 2px rgba(34,197,94,0.12);
}

.btn-apply {
    padding: 6px 14px;
    background: var(--green-700);
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: background 0.15s;
}

.btn-apply:hover { background: var(--green-600); }

/* ── Welcome banner ─────────────────────────────────────────────────────────── */
.welcome-banner {
    background: linear-gradient(135deg, #15803d 0%, #22c55e 100%);
    border-radius: 16px;
    padding: 22px 28px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(22,163,74,0.2);
}

.welcome-banner::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 150px; height: 150px;
    background: rgba(255,255,255,0.07);
    border-radius: 50%;
    pointer-events: none;
}

.welcome-greeting { font-size: 1.25rem; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
.welcome-subtitle { font-size: 0.85rem; color: rgba(255,255,255,0.75); margin-top: 3px; }
.welcome-period { display: inline-block; margin-top: 8px; padding: 2px 10px; background: rgba(255,255,255,0.18); border-radius: 100px; font-size: 0.75rem; color: #fff; font-weight: 500; }

/* ── Stat cards ─────────────────────────────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.stat-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: 18px 18px 16px;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

.stat-accent {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    border-radius: 13px 0 0 13px;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-muted);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--gray-900);
    letter-spacing: -1px;
    line-height: 1;
}

.stat-value small {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--gray-500);
    letter-spacing: 0;
}

.stat-sub {
    margin-top: 10px;
    padding-top: 9px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    gap: 12px;
    font-size: 0.76rem;
    color: var(--text-secondary);
}

.a-green  .stat-accent { background: #22c55e; }
.a-blue   .stat-accent { background: #3b82f6; }
.a-amber  .stat-accent { background: #f59e0b; }
.a-teal   .stat-accent { background: #14b8a6; }

/* ── Chart cards ────────────────────────────────────────────────────────────── */
.chart-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 13px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 18px;
}

.chart-head {
    padding: 13px 18px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--gray-50);
}

.chart-title-text {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-700);
}

.chart-body { padding: 18px; }

.charts-row {
    display: grid;
    grid-template-columns: 1fr 330px;
    gap: 18px;
    margin-bottom: 0;
}

/* ── Leaderboard ────────────────────────────────────────────────────────────── */
.lb-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 0; border-bottom: 1px solid var(--gray-100);
}
.lb-item:last-child { border-bottom: none; }

.lb-rank {
    width: 24px; height: 24px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.72rem; font-weight: 800; flex-shrink: 0;
}

.lb-r1 { background: #fef3c7; color: #92400e; }
.lb-r2 { background: var(--gray-100); color: var(--gray-600); }
.lb-r3 { background: #fff7ed; color: #c2410c; }
.lb-rn { background: var(--gray-50); color: var(--text-muted); }

.lb-info { flex: 1; min-width: 0; }
.lb-name { font-size: 0.83rem; font-weight: 500; color: var(--gray-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lb-bar-wrap { height: 3px; background: var(--gray-100); border-radius: 2px; margin-top: 5px; overflow: hidden; }
.lb-bar { height: 100%; background: linear-gradient(90deg, #16a34a, #4ade80); border-radius: 2px; }
.lb-val { font-size: 0.8rem; font-weight: 700; color: var(--green-700); white-space: nowrap; }

.empty-state { text-align: center; padding: 32px 16px; color: var(--text-muted); }
.empty-state-text { font-size: 0.875rem; margin-top: 6px; }

@media (max-width: 900px) { .charts-row { grid-template-columns: 1fr; } }
</style>

<!-- ── Page header + filter ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">Kios Sembako Azwa &middot; <?= $period_label ?></div>
    </div>

    <!-- Period filter -->
    <form method="GET" id="periodForm" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <div class="period-filter">
            <button type="button" class="period-tab <?= $period==='all'     ? 'active':'' ?>" data-period="all">Semua</button>
            <button type="button" class="period-tab <?= $period==='daily'   ? 'active':'' ?>" data-period="daily">Harian</button>
            <button type="button" class="period-tab <?= $period==='monthly' ? 'active':'' ?>" data-period="monthly">Bulanan</button>
            <button type="button" class="period-tab <?= $period==='yearly'  ? 'active':'' ?>" data-period="yearly">Tahunan</button>

            <!-- Hidden period field -->
            <input type="hidden" name="period" id="hiddenPeriod" value="<?= escape($period) ?>">

            <!-- Daily: date picker -->
            <div class="period-date-input <?= $period==='daily' ? 'visible':'' ?>" id="inp-daily">
                <input type="date" name="date_val" id="inp-date"
                       value="<?= ($period==='daily') ? escape($date_val) : date('Y-m-d') ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>

            <!-- Monthly: month picker -->
            <div class="period-date-input <?= $period==='monthly' ? 'visible':'' ?>" id="inp-monthly">
                <input type="month" name="date_val" id="inp-month"
                       value="<?= ($period==='monthly') ? escape($date_val) : date('Y-m') ?>"
                       max="<?= date('Y-m') ?>">
            </div>

            <!-- Yearly: year select -->
            <div class="period-date-input <?= $period==='yearly' ? 'visible':'' ?>" id="inp-yearly">
                <select name="date_val" id="inp-year">
                    <?php foreach ($year_range as $y): ?>
                    <option value="<?= $y ?>" <?= ($period==='yearly' && $date_val==$y) ? 'selected':'' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-apply">Terapkan</button>
    </form>
</div>

<!-- Welcome banner -->
<div class="welcome-banner">
    <div>
        <div class="welcome-greeting">Selamat Datang, <?= $display_name ?>!</div>
        <div class="welcome-subtitle">Sistem Manajemen Inventori &middot; Kios Sembako Azwa</div>
        <div class="welcome-period">Periode: <?= $period_label ?></div>
    </div>
</div>

<!-- Stat cards -->
<div class="stats-grid">
    <?php if ($isAdmin): ?>
    <div class="stat-card a-amber">
        <div class="stat-accent"></div>
        <div class="stat-label">Total Pendapatan</div>
        <div class="stat-value">Rp <small><?= number_format($total_revenue,0,',','.') ?></small></div>
    </div>
    <?php endif; ?>

    <div class="stat-card a-green">
        <div class="stat-accent"></div>
        <div class="stat-label">Item Produk</div>
        <div class="stat-value"><?= number_format($stats['total_products']??0,0,',','.') ?><small> jenis</small></div>
    </div>

    <div class="stat-card a-blue">
        <div class="stat-accent"></div>
        <div class="stat-label">Total Stok Gudang</div>
        <div class="stat-value"><?= number_format($stats['total_stock']??0,0,',','.') ?><small> pcs</small></div>
    </div>

    <div class="stat-card a-teal">
        <div class="stat-accent"></div>
        <div class="stat-label">Transaksi Stok</div>
        <div class="stat-value"><?= number_format(($trans_summary['total_in']??0)+($trans_summary['total_out']??0),0,',','.') ?></div>
        <div class="stat-sub">
            <span style="color:#16a34a">IN <b><?= number_format($trans_summary['total_in']??0,0,',','.') ?></b></span>
            <span style="color:#ef4444">OUT <b><?= number_format($trans_summary['total_out']??0,0,',','.') ?></b></span>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- ── ADMIN: Grafik Pendapatan (full width) ─────────────────────────────── -->
<div class="chart-card">
    <div class="chart-head">
        <div class="chart-title-text">Grafik Pendapatan</div>
        <span class="badge badge-yellow"><?= $period_label ?></span>
    </div>
    <div class="chart-body">
        <?php if (count($revenue_trend) > 0): ?>
        <canvas id="revenueChart" height="100"></canvas>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-text">Belum ada data pendapatan untuk periode ini</div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Produk Terlaris + Leaderboard ────────────────────────────────────── -->
<div class="charts-row">
    <div class="chart-card" style="margin-bottom:0">
        <div class="chart-head">
            <div class="chart-title-text">Produk Terlaris</div>
            <span class="badge badge-green">Top 5</span>
        </div>
        <div class="chart-body">
            <?php if (count($top_products) > 0): ?>
            <canvas id="topProductsChart" height="185"></canvas>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-text">Belum ada data penjualan untuk periode ini</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="chart-card" style="margin-bottom:0">
        <div class="chart-head">
            <div class="chart-title-text">Ranking Produk</div>
            <span class="badge badge-green">Top 5</span>
        </div>
        <div class="chart-body" style="padding:10px 18px;">
            <?php if (count($top_products) > 0):
                $maxSold = max(array_column($top_products,'total_sold'));
                foreach ($top_products as $i => $tp):
                    $rank  = $i+1;
                    $barW  = $maxSold > 0 ? round($tp['total_sold']/$maxSold*100) : 0;
                    $rc    = $rank===1?'lb-r1':($rank===2?'lb-r2':($rank===3?'lb-r3':'lb-rn'));
            ?>
            <div class="lb-item">
                <div class="lb-rank <?=$rc?>"><?=$rank?></div>
                <div class="lb-info">
                    <div class="lb-name"><?=escape($tp['name'])?></div>
                    <div class="lb-bar-wrap"><div class="lb-bar" style="width:<?=$barW?>%"></div></div>
                </div>
                <div class="lb-val"><?=number_format($tp['total_sold'],0,',','.')?> pcs</div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state"><div class="empty-state-text">Belum ada data</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script>
const ttCfg = {
    backgroundColor:'#111827', titleColor:'#f9fafb', bodyColor:'#d1d5db',
    padding:10, cornerRadius:8, borderColor:'#374151', borderWidth:1,
};

<?php if ($isAdmin && count($revenue_trend) > 0): ?>
(function(){
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($revenue_trend,'label')) ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?= json_encode(array_column($revenue_trend,'revenue')) ?>,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,0.07)',
                fill: true, tension: 0.4,
                pointRadius: 4, pointBackgroundColor: '#16a34a', borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { ...ttCfg, callbacks: { label: c => 'Rp ' + Number(c.parsed.y).toLocaleString('id-ID') } }
            },
            scales: {
                y: { beginAtZero:true, grid:{color:'#f3f4f6'}, ticks:{color:'#9ca3af',font:{size:11}, callback:v=>'Rp '+Number(v).toLocaleString('id-ID')} },
                x: { grid:{display:false}, ticks:{color:'#9ca3af',font:{size:11}} }
            }
        }
    });
})();
<?php endif; ?>

<?php if (count($top_products) > 0): ?>
(function(){
    const ctx = document.getElementById('topProductsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_products,'name')) ?>,
            datasets: [{
                label: 'Terjual (pcs)',
                data: <?= json_encode(array_column($top_products,'total_sold')) ?>,
                backgroundColor: ['rgba(22,163,74,.8)','rgba(16,185,129,.75)','rgba(20,184,166,.70)','rgba(59,130,246,.65)','rgba(139,92,246,.65)'],
                borderColor: ['#16a34a','#10b981','#14b8a6','#3b82f6','#8b5cf6'],
                borderWidth: 2, borderRadius: 8, borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend:{display:false}, tooltip:ttCfg },
            scales: {
                y: { beginAtZero:true, grid:{color:'#f3f4f6'}, ticks:{color:'#9ca3af',font:{size:11}} },
                x: { grid:{display:false}, ticks:{color:'#6b7280',font:{size:11}} }
            }
        }
    });
})();
<?php endif; ?>

// ── Period filter JS ───────────────────────────────────────────────────────────
(function(){
    const tabs      = document.querySelectorAll('.period-tab');
    const hidden    = document.getElementById('hiddenPeriod');
    const inpDaily  = document.getElementById('inp-daily');
    const inpMthly  = document.getElementById('inp-monthly');
    const inpYrly   = document.getElementById('inp-yearly');
    const allInputs = [inpDaily, inpMthly, inpYrly];

    function showInput(period) {
        allInputs.forEach(el => el.classList.remove('visible'));
        if (period === 'daily')   inpDaily.classList.add('visible');
        if (period === 'monthly') inpMthly.classList.add('visible');
        if (period === 'yearly')  inpYrly.classList.add('visible');
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const p = this.dataset.period;
            hidden.value = p;
            showInput(p);

            // Jika "Semua", langsung submit
            if (p === 'all') {
                document.getElementById('periodForm').submit();
            }
        });
    });

    // Untuk daily — auto-open native date picker saat tab dipilih
    document.getElementById('inp-date').addEventListener('click', function() {
        this.showPicker && this.showPicker();
    });
})();
</script>

<?php echo "</div></body></html>"; ?>