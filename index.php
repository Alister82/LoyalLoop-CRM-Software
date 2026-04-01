<?php 
include 'auth_session.php'; 
include 'db_connect.php'; 
$shop_id = $_SESSION['shop_id'];
$owner   = $_SESSION['owner_name'];
$today   = date('Y-m-d');
$month   = date('Y-m');

// Stats
$p_count = $conn->query("SELECT COUNT(*) as c FROM products WHERE shop_id='$shop_id' AND status='active'")->fetch_assoc()['c'];
$c_count = $conn->query("SELECT COUNT(*) as c FROM customers WHERE shop_id='$shop_id'")->fetch_assoc()['c'];
$e_count = $conn->query("SELECT COUNT(*) as c FROM products WHERE shop_id='$shop_id' AND status='active' AND expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY)")->fetch_assoc()['c'];
$s_count = $conn->query("SELECT COUNT(*) as c FROM suppliers WHERE shop_id='$shop_id'")->fetch_assoc()['c'];

// Revenue this month
$rev_res = $conn->query("SELECT COALESCE(SUM(total_amount),0) as rev FROM sales WHERE shop_id='$shop_id' AND DATE_FORMAT(sale_date,'%Y-%m')='$month'");
$revenue = $rev_res->fetch_assoc()['rev'];

// Sales today
$today_res = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM sales WHERE shop_id='$shop_id' AND DATE(sale_date)='$today'");
$today_sales = $today_res->fetch_assoc()['t'];

// Expiring products
$exp_result = $conn->query("SELECT name, stock_qty, expiry_date FROM products WHERE shop_id='$shop_id' AND status='active' AND expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY) ORDER BY expiry_date ASC LIMIT 6");

// Recent sales
$recent_sales = $conn->query("SELECT s.id, s.total_amount, s.sale_date, c.name as cname FROM sales s LEFT JOIN customers c ON s.customer_id=c.id WHERE s.shop_id='$shop_id' ORDER BY s.sale_date DESC LIMIT 6");

// Low stock
$low_stock = $conn->query("SELECT name, stock_qty, reorder_level FROM products WHERE shop_id='$shop_id' AND status='active' AND stock_qty <= reorder_level ORDER BY stock_qty ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – LoyalLoop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .welcome-strip {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
            color: white;
            padding: 22px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .welcome-strip h1 {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin: 0;
        }
        .welcome-strip p { color: #94a3b8; font-size: 0.85rem; margin: 3px 0 0; }
        .welcome-strip .badges { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
        .strip-badge {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            color: #c7d2fe;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .quick-btn {
            flex: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.15s;
            cursor: pointer;
        }
        .quick-btn:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .quick-btn .q-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .quick-btn .q-label { font-size: 0.82rem; font-weight: 700; color: var(--text-primary); }
        .quick-btn .q-sub { font-size: 0.72rem; color: var(--text-secondary); margin-top: 1px; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-secondary);
            margin: 0 0 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .mini-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .mini-list-item:last-child { border-bottom: none; }
        .mini-list-item .m-name { font-size: 0.86rem; font-weight: 500; color: var(--text-primary); }
        .mini-list-item .m-sub  { font-size: 0.72rem; color: var(--text-secondary); margin-top: 1px; }
        .mini-list-item .m-right { text-align:right; flex-shrink:0; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Welcome strip -->
    <div class="welcome-strip">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h1>Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>, <?= htmlspecialchars($owner) ?>! 👋</h1>
                <p>Here's what's happening in your store today — <?= date('l, d M Y') ?></p>
                <div class="badges">
                    <span class="strip-badge">📅 <?= date('h:i A') ?></span>
                    <span class="strip-badge">🏪 <?= htmlspecialchars($_SESSION['shop_name']) ?></span>
                    <?php if ($e_count > 0): ?>
                    <span class="strip-badge" style="background:rgba(239,68,68,0.2); color:#fca5a5; border-color:rgba(239,68,68,0.3);">
                        ⚠️ <?= $e_count ?> Expiring Items
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="billing.php" class="btn btn-primary" style="white-space:nowrap;">
                <i class="fas fa-plus"></i> New Bill
            </a>
        </div>
    </div>

    <div class="page-body">

        <!-- STATS -->
        <div class="stats-grid" style="grid-template-columns: repeat(6, 1fr);">
            <div class="stat-card" style="--card-accent:#2563eb; --card-icon-bg:#eff6ff;">
                <div class="label">Products</div>
                <div class="value"><?= $p_count ?></div>
                <div class="sub">Active items</div>
                <div class="icon-bg"><i class="fas fa-boxes"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#059669; --card-icon-bg:#f0fdf4;">
                <div class="label">Customers</div>
                <div class="value"><?= $c_count ?></div>
                <div class="sub">All time</div>
                <div class="icon-bg" style="color:#059669;"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#7c3aed; --card-icon-bg:#f5f3ff;">
                <div class="label">Month Revenue</div>
                <div class="value" style="font-size:1.4rem;">₹<?= number_format($revenue) ?></div>
                <div class="sub"><?= date('M Y') ?></div>
                <div class="icon-bg" style="color:#7c3aed;"><i class="fas fa-chart-line"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#0284c7; --card-icon-bg:#f0f9ff;">
                <div class="label">Today's Sales</div>
                <div class="value" style="font-size:1.4rem;">₹<?= number_format($today_sales) ?></div>
                <div class="sub"><?= date('d M') ?></div>
                <div class="icon-bg" style="color:#0284c7;"><i class="fas fa-receipt"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#dc2626; --card-icon-bg:#fef2f2;">
                <div class="label">Expiring (45d)</div>
                <div class="value" style="color:<?= $e_count > 0 ? 'var(--danger)' : 'inherit' ?>;"><?= $e_count ?></div>
                <div class="sub">Need attention</div>
                <div class="icon-bg" style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#d97706; --card-icon-bg:#fffbeb;">
                <div class="label">Suppliers</div>
                <div class="value"><?= $s_count ?></div>
                <div class="sub">Registered</div>
                <div class="icon-bg" style="color:#d97706;"><i class="fas fa-industry"></i></div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <a href="billing.php" class="quick-btn">
                <div class="q-icon" style="background:#eff6ff; color:#2563eb;"><i class="fas fa-cash-register"></i></div>
                <div><div class="q-label">New Sale</div><div class="q-sub">Create bill & invoice</div></div>
            </a>
            <a href="inventory.php" class="quick-btn">
                <div class="q-icon" style="background:#f0fdf4; color:#059669;"><i class="fas fa-plus-circle"></i></div>
                <div><div class="q-label">Add Product</div><div class="q-sub">Update inventory</div></div>
            </a>
            <a href="customers.php" class="quick-btn">
                <div class="q-icon" style="background:#f5f3ff; color:#7c3aed;"><i class="fas fa-bullhorn"></i></div>
                <div><div class="q-label">Send Campaign</div><div class="q-sub">WhatsApp or email blast</div></div>
            </a>
            <a href="replenishment.php" class="quick-btn">
                <div class="q-icon" style="background:#fffbeb; color:#d97706;"><i class="fas fa-brain"></i></div>
                <div><div class="q-label">AI Reorder</div><div class="q-sub">Smart replenishment</div></div>
            </a>
            <?php if ($e_count > 0): ?>
            <a href="send_offer.php" class="quick-btn">
                <div class="q-icon" style="background:#fef2f2; color:#dc2626;"><i class="fas fa-tag"></i></div>
                <div><div class="q-label">Send Expiry Offer</div><div class="q-sub">20% off email blast</div></div>
            </a>
            <?php endif; ?>
        </div>

        <!-- TWO COLUMN CARDS -->
        <div class="two-col">

            <!-- Expiry Alerts -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-circle" style="color:#dc2626;"></i> Expiry Alerts</h3>
                    <?php if ($e_count > 0): ?>
                    <a href="send_offer.php" class="btn btn-sm btn-danger">
                        <i class="fas fa-envelope"></i> Send Offer
                    </a>
                    <?php else: ?>
                    <span class="badge badge-green">✅ All Fresh</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($exp_result->num_rows > 0):
                        while ($row = $exp_result->fetch_assoc()):
                            $is_exp = $row['expiry_date'] < $today;
                            $days_left = ceil((strtotime($row['expiry_date']) - time()) / 86400);
                    ?>
                    <div class="mini-list-item">
                        <div>
                            <div class="m-name"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="m-sub">Stock: <?= $row['stock_qty'] ?> units</div>
                        </div>
                        <div class="m-right">
                            <?php if ($is_exp): ?>
                                <span class="badge badge-red">🔴 EXPIRED</span>
                            <?php elseif ($days_left <= 7): ?>
                                <span class="badge badge-red"><?= $days_left ?>d left</span>
                            <?php else: ?>
                                <span class="badge badge-orange"><?= $days_left ?>d left</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="empty-state" style="padding:30px 20px;">
                        <i class="fas fa-check-circle" style="color:var(--success); font-size:2rem; opacity:1;"></i>
                        <p style="margin-top:8px; font-size:0.86rem;">No expiring items in the next 45 days!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-receipt" style="color:var(--primary);"></i> Recent Sales</h3>
                    <a href="billing.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> New Bill</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_sales->num_rows > 0):
                        while ($s = $recent_sales->fetch_assoc()):
                    ?>
                    <div class="mini-list-item">
                        <div>
                            <div class="m-name"><?= htmlspecialchars($s['cname'] ?: 'Walk-in Customer') ?></div>
                            <div class="m-sub">Invoice #<?= $s['id'] ?> · <?= date('d M, h:i A', strtotime($s['sale_date'])) ?></div>
                        </div>
                        <div class="m-right">
                            <span style="font-weight:700; color:var(--success);">₹<?= number_format($s['total_amount'], 2) ?></span>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="empty-state" style="padding:30px 20px;">
                        <i class="fas fa-receipt"></i>
                        <p>No sales yet. <a href="billing.php">Create your first bill!</a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Low Stock Alert -->
        <?php 
        $low_count = $conn->query("SELECT COUNT(*) as c FROM products WHERE shop_id='$shop_id' AND status='active' AND stock_qty <= reorder_level")->fetch_assoc()['c'];
        if ($low_count > 0): 
        $low_stock = $conn->query("SELECT name, stock_qty, reorder_level, default_supplier_id FROM products WHERE shop_id='$shop_id' AND status='active' AND stock_qty <= reorder_level ORDER BY stock_qty ASC LIMIT 5");
        ?>
        <div class="card" style="margin-top:16px; border-left:4px solid #f59e0b;">
            <div class="card-header">
                <h3><i class="fas fa-battery-quarter" style="color:#f59e0b;"></i> Low Stock Alert</h3>
                <a href="replenishment.php" class="btn btn-sm" style="background:#fffbeb; color:#d97706;">
                    <i class="fas fa-brain"></i> View AI Reorder Plan
                </a>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:10px;">
                <?php while ($row = $low_stock->fetch_assoc()): ?>
                <div style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:12px;">
                    <div style="font-weight:600; font-size:0.86rem; color:var(--text-primary); margin-bottom:4px;">
                        <?= htmlspecialchars($row['name']) ?>
                    </div>
                    <div style="font-size:0.78rem; color:var(--danger); font-weight:700;">
                        <?= $row['stock_qty'] ?> / <?= $row['reorder_level'] ?> units
                    </div>
                    <div style="margin-top:6px; background:#fee2e2; border-radius:4px; height:4px; overflow:hidden;">
                        <div style="background:#ef4444; height:100%; width:<?= min(100, ($row['stock_qty']/$row['reorder_level'])*100) ?>%;"></div>
                    </div>
                </div>
                <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>