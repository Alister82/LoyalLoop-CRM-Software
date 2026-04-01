<?php
include 'auth_session.php';
include 'db_connect.php';
include 'prediction_engine.php';

$shop_id   = $_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'];

// Run the prediction engine
$predictions = getAllProductPredictions($shop_id, $conn);
$group_a     = $predictions['group_a']; // keyed by supplier_id
$group_b     = $predictions['group_b']; // flat array

// Fetch all suppliers for Group B dropdown
$sup_result = $conn->query("SELECT id, name, whatsapp_number FROM suppliers WHERE shop_id='$shop_id' ORDER BY name ASC");
$all_suppliers = [];
while ($row = $sup_result->fetch_assoc()) $all_suppliers[] = $row;

// Confidence label helpers
function confidenceBadge($level) {
    $map = [
        'high'   => ['🟢', '#f0fdf4', '#166534', 'High Confidence'],
        'medium' => ['🟡', '#fffbeb', '#92400e', 'Medium Confidence'],
        'low'    => ['🟠', '#fff7ed', '#9a3412', 'Low — Limited Data'],
        'none'   => ['⚙️',  '#f8fafc', '#64748b', 'Default Estimate'],
    ];
    $v = $map[$level] ?? $map['none'];
    return "<span style='background:{$v[1]}; color:{$v[2]}; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; white-space:nowrap;'>{$v[0]} {$v[3]}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replenishment AI – LoyalLoop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }

        /* ── Hero Header ── */
        .ai-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            color: white;
            padding: 28px 36px;
            border-radius: 18px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 48px rgba(15,23,42,0.25);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .ai-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(99,102,241,0.15);
        }
        .ai-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 30%;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(139,92,246,0.1);
        }
        .ai-hero h1 {
            font-size: 1.9rem;
            font-weight: 800;
            margin: 0 0 6px;
            letter-spacing: -0.03em;
        }
        .ai-hero p { color: #a5b4fc; font-size: 0.95rem; margin: 0 0 20px; }
        .hero-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .hero-badge {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: #c7d2fe;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        /* ── Stats Row ── */
        .replen-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .replen-stat {
            background: white;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--accent);
        }
        .replen-stat .stat-val { font-size: 1.9rem; font-weight: 800; color: var(--accent); }
        .replen-stat .stat-lbl { font-size: 0.8rem; color: #64748b; margin-top: 2px; font-weight: 500; }

        /* ── Section Headers ── */
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .section-title .icon-box {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .section-title h2 { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin: 0; }
        .section-title p  { font-size: 0.82rem; color: #64748b; margin: 2px 0 0; }

        /* ── Supplier Group A ── */
        .supplier-block {
            background: white;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .supplier-block-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 16px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .supplier-block-header .sname {
            font-weight: 700;
            font-size: 1rem;
        }
        .supplier-block-header .scount {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .btn-wa-send {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #25d366;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 0.87rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-wa-send:hover { background: #1fba57; transform: scale(0.98); }

        .order-table { width: 100%; border-collapse: collapse; }
        .order-table th {
            text-align: left;
            padding: 10px 18px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .order-table td {
            padding: 12px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
            color: #334155;
        }
        .order-table tr:last-child td { border-bottom: none; }
        .order-table tr:hover td { background: #fafbff; }

        .qty-badge {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .qty-badge.urgent { background: #fef2f2; color: #dc2626; }

        /* ── Group B (Unassigned) ── */
        .group-b-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .group-b-header {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            padding: 16px 22px;
        }
        .group-b-header h3 { font-size: 1rem; font-weight: 700; margin: 0 0 2px; }
        .group-b-header p  { font-size: 0.82rem; color: #c4b5fd; margin: 0; }

        .group-b-toolbar {
            padding: 16px 22px;
            background: #faf5ff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .group-b-toolbar label { font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .group-b-toolbar select {
            padding: 9px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.88rem;
            color: #1e293b;
            background: white;
            min-width: 220px;
            font-family: 'Inter', sans-serif;
        }
        .btn-send-selected {
            padding: 9px 20px;
            background: #25d366;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.87rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-send-selected:hover { background: #1fba57; }
        .btn-select-all {
            padding: 7px 14px;
            background: #eff6ff;
            color: #2563eb;
            border: none;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
        }

        .group-b-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 22px;
            border-bottom: 1px solid #f8fafc;
            transition: background 0.15s;
        }
        .group-b-item:hover { background: #fafbff; }
        .group-b-item:last-child { border-bottom: none; }
        .group-b-item input[type=checkbox] {
            width: 18px; height: 18px;
            accent-color: #7c3aed;
            cursor: pointer;
            flex-shrink: 0;
        }
        .item-info { flex: 1; }
        .item-name { font-weight: 600; color: #1e293b; font-size: 0.9rem; }
        .item-meta { font-size: 0.78rem; color: #64748b; margin-top: 1px; }
        .item-qty-input {
            padding: 6px 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            font-size: 0.87rem;
            width: 80px;
            text-align: center;
            font-family: 'Inter', sans-serif;
        }

        .no-data-msg {
            padding: 40px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .no-data-msg i { font-size: 2.5rem; display: block; margin-bottom: 12px; color: #cbd5e1; }

        /* ── Tab Switcher ── */
        .tabs {
            display: flex;
            gap: 0;
            background: white;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            width: fit-content;
        }
        .tab-btn {
            padding: 9px 22px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 2px 8px rgba(37,99,235,0.3);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- HERO -->
    <div class="ai-hero">
        <div style="position:relative; z-index:1;">
            <h1>🔮 Replenishment Intelligence</h1>
            <p>AI-powered demand forecasting using your historical sales data. Predict, plan, and order — all in one click.</p>
            <div class="hero-badges">
                <span class="hero-badge">⚡ Weighted Moving Average</span>
                <span class="hero-badge">📈 Trend Detection</span>
                <span class="hero-badge">📱 WhatsApp Order</span>
                <span class="hero-badge">🛒 Smart Grouping</span>
                <span class="hero-badge">🧠 Auto-Learns from Sales</span>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <?php
        $total_products  = count($group_b) + array_sum(array_map(fn($s) => count($s['products']), $group_a));
        $assigned_count  = array_sum(array_map(fn($s) => count($s['products']), $group_a));
        $unassigned_count = count($group_b);
        $supplier_count  = count($group_a);

        // Products that actually need ordering (reorder_qty > 0) in group_b
        $b_needs_order = count(array_filter($group_b, fn($p) => $p['reorder_qty'] > 0));
    ?>
    <div class="replen-stats">
        <div class="replen-stat" style="--accent: #2563eb;">
            <div class="stat-val"><?= $total_products ?></div>
            <div class="stat-lbl">Total Products Analyzed</div>
        </div>
        <div class="replen-stat" style="--accent: #059669;">
            <div class="stat-val"><?= $supplier_count ?></div>
            <div class="stat-lbl">Supplier Groups (Auto)</div>
        </div>
        <div class="replen-stat" style="--accent: #7c3aed;">
            <div class="stat-val"><?= $unassigned_count ?></div>
            <div class="stat-lbl">Unassigned Products</div>
        </div>
        <div class="replen-stat" style="--accent: #dc2626;">
            <div class="stat-val"><?= $b_needs_order ?></div>
            <div class="stat-lbl">Unassigned Needing Restock</div>
        </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('group-a', this)">
            🏭 Group A — Assigned Suppliers (<?= count($group_a) ?>)
        </button>
        <button class="tab-btn" onclick="showTab('group-b', this)">
            🛒 Group B — Unassigned Pool (<?= count($group_b) ?>)
        </button>
    </div>

    <!-- ═══════════════════════════════════════
         GROUP A: ASSIGNED SUPPLIER ORDERS
    ═══════════════════════════════════════ -->
    <div id="tab-group-a">

        <div class="section-title">
            <div class="icon-box" style="background:#eff6ff; color:#2563eb;">🏭</div>
            <div>
                <h2>Assigned Supplier Groups</h2>
                <p>Products linked to specific suppliers are auto-grouped below. One click to send the order via WhatsApp.</p>
            </div>
        </div>

        <?php if (empty($group_a)): ?>
            <div class="card">
                <div class="no-data-msg">
                    <i class="fas fa-link-slash"></i>
                    No products are linked to suppliers yet.
                    <br>
                    <a href="inventory.php" style="color:#2563eb; font-weight:700; margin-top:8px; display:inline-block;">
                        Go to Inventory to link suppliers →
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($group_a as $sid => $supplier): ?>
                <?php
                // Build WhatsApp message for this supplier
                $shop = $shop_name;
                $wa_msg  = "🛒 *PURCHASE ORDER — {$shop}*\n";
                $wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
                $wa_msg .= "Date: " . date('d M Y') . "\n";
                $wa_msg .= "Supplier: {$supplier['supplier_name']}\n";
                $wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
                $wa_msg .= "*ITEMS REQUIRED:*\n";
                $item_count = 0;
                foreach ($supplier['products'] as $prod) {
                    // Use reorder_qty if predicted > 0, else fall back to reorder_level or 10
                    $order_qty = $prod['reorder_qty'] > 0
                        ? $prod['reorder_qty']
                        : max(1, (int)($prod['reorder_level'] ?: 10));
                    $wa_msg .= "• {$prod['name']} — {$order_qty} units\n";
                    $item_count++;
                }
                $wa_msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
                $wa_msg .= "Total Items: {$item_count}\n";
                $wa_msg .= "Sent via LoyalLoop CRM 🚀\n";
                $wa_msg .= "Please confirm receipt. 🙏";
                $wa_link = "https://wa.me/{$supplier['supplier_wa']}?text=" . urlencode($wa_msg);
                ?>
                <div class="supplier-block">
                    <div class="supplier-block-header">
                        <div>
                            <div class="sname">
                                <i class="fas fa-industry" style="margin-right:8px; color:#60a5fa;"></i>
                                <?= htmlspecialchars($supplier['supplier_name']) ?>
                            </div>
                            <div class="scount">
                                <?= count($supplier['products']) ?> product<?= count($supplier['products']) != 1 ? 's' : '' ?> 
                                · WA: <?= htmlspecialchars($supplier['supplier_wa']) ?>
                            </div>
                        </div>
                        <a href="<?= $wa_link ?>" target="_blank" class="btn-wa-send">
                            <i class="fab fa-whatsapp"></i>
                            Send Order via WhatsApp
                        </a>
                    </div>

                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Predicted Need</th>
                                <th>Suggested Order</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplier['products'] as $prod): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($prod['name']) ?></td>
                                    <td>
                                        <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-size:0.78rem;">
                                            <?= htmlspecialchars($prod['category'] ?: 'General') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span style="color: <?= $prod['needs_restock'] ? '#dc2626' : '#059669' ?>; font-weight:700;">
                                                <?= $prod['stock_qty'] ?> units
                                            </span>
                                            <?php if ($prod['needs_restock']): ?>
                                            <span style="background:#fef2f2; color:#dc2626; padding:2px 7px; border-radius:10px; font-size:0.68rem; font-weight:800; border:1px solid #fca5a5;">⚠ LOW</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($prod['reorder_level'] > 0): ?>
                                        <div style="font-size:0.68rem; color:#94a3b8; margin-top:2px;">Reorder level: <?= $prod['reorder_level'] ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $prod['predicted_qty'] ?> units/mo
                                        <?php if ($prod['confidence'] === 'none'): ?>
                                        <div style="font-size:0.68rem; color:#94a3b8; margin-top:1px;">est.</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($prod['reorder_qty'] > 0): ?>
                                            <span class="qty-badge <?= $prod['reorder_qty'] > 20 ? 'urgent' : '' ?>">
                                                +<?= $prod['reorder_qty'] ?> units
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#059669; font-size:0.82rem;">✅ Well Stocked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= confidenceBadge($prod['confidence']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════
         GROUP B: UNASSIGNED POOL
    ═══════════════════════════════════════ -->
    <div id="tab-group-b" style="display:none;">

        <div class="section-title">
            <div class="icon-box" style="background:#f5f3ff; color:#7c3aed;">🛒</div>
            <div>
                <h2>Unassigned Product Pool</h2>
                <p>Select products, choose a supplier, and send the order list directly via WhatsApp.</p>
            </div>
        </div>

        <?php if (empty($group_b)): ?>
            <div class="card">
                <div class="no-data-msg">
                    <i class="fas fa-check-circle" style="color:#22c55e;"></i>  
                    All products are linked to suppliers! You're well organized. 🎉
                </div>
            </div>
        <?php else: ?>
            <div class="group-b-card">
                <div class="group-b-header">
                    <h3>🛒 Generic / Unassigned Products</h3>
                    <p>Select what you need to restock, pick a supplier, and send the list.</p>
                </div>

                <div class="group-b-toolbar">
                    <button class="btn-select-all" onclick="toggleAll()">Select All</button>
                    <label>Send to Supplier:</label>
                    <select id="b_supplier_select">
                        <option value="">— Choose a Supplier —</option>
                        <?php foreach ($all_suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>" 
                                    data-wa="<?= htmlspecialchars($sup['whatsapp_number']) ?>"
                                    data-name="<?= htmlspecialchars($sup['name']) ?>">
                                <?= htmlspecialchars($sup['name']) ?> (<?= $sup['whatsapp_number'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn-send-selected" onclick="sendGroupB()">
                        <i class="fab fa-whatsapp"></i> Send Selected to Supplier
                    </button>
                </div>

                <?php foreach ($group_b as $prod): ?>
                    <div class="group-b-item">
                        <input type="checkbox" 
                               class="b-check" 
                               data-name="<?= htmlspecialchars($prod['name']) ?>"
                               data-qty="<?= $prod['reorder_qty'] > 0 ? $prod['reorder_qty'] : 1 ?>"
                               id="b_<?= $prod['product_id'] ?>">

                        <div class="item-info">
                            <div class="item-name"><?= htmlspecialchars($prod['name']) ?></div>
                            <div class="item-meta">
                                Stock: <?= $prod['stock_qty'] ?> · Predicted need: <?= $prod['predicted_qty'] ?>/mo
                                · <?= ucfirst($prod['category'] ?: 'General') ?>
                            </div>
                        </div>

                        <div style="text-align:right; margin-right:8px;">
                            <?= confidenceBadge($prod['confidence']) ?>
                            <?php if ($prod['reorder_qty'] > 0): ?>
                                <div style="font-size:0.75rem; color:#dc2626; margin-top:4px; font-weight:600;">
                                    ⚠️ Needs <?= $prod['reorder_qty'] ?> units
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label style="font-size:0.75rem; color:#64748b; display:block; margin-bottom:3px;">Qty to Order</label>
                            <input class="item-qty-input b-qty" 
                                   type="number" 
                                   value="<?= max(1, $prod['reorder_qty']) ?>" 
                                   min="1"
                                   data-id="<?= $prod['product_id'] ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($all_suppliers)): ?>
            <div style="margin-top:16px; padding:14px 18px; background:#fffbeb; border-radius:10px; font-size:0.87rem; color:#92400e;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>No suppliers added yet!</strong> 
                <a href="suppliers.php" style="color:#d97706; font-weight:700;">Add suppliers first</a> to be able to send orders.
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div><!-- end main-content -->

<script>
// Tab switching
function showTab(name, btn) {
    document.getElementById('tab-group-a').style.display = name === 'group-a' ? 'block' : 'none';
    document.getElementById('tab-group-b').style.display = name === 'group-b' ? 'block' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// Select All toggle for Group B
let allSelected = false;
function toggleAll() {
    allSelected = !allSelected;
    document.querySelectorAll('.b-check').forEach(cb => cb.checked = allSelected);
    document.querySelector('.btn-select-all').textContent = allSelected ? 'Deselect All' : 'Select All';
}

// Build WhatsApp message for Group B and open it
function sendGroupB() {
    const select = document.getElementById('b_supplier_select');
    const option = select.options[select.selectedIndex];

    if (!option.value) {
        alert('⚠️ Please choose a supplier first!');
        return;
    }

    const wa_number = option.getAttribute('data-wa');
    const sup_name  = option.getAttribute('data-name');

    const checkboxes = document.querySelectorAll('.b-check:checked');
    if (checkboxes.length === 0) {
        alert('⚠️ Please select at least one product!');
        return;
    }

    // Build message
    const shop = '<?= addslashes($shop_name) ?>';
    const date  = new Date().toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });

    let msg  = `🛒 *PURCHASE ORDER — ${shop}*\n`;
    msg     += `━━━━━━━━━━━━━━━━━━━━━\n`;
    msg     += `Date: ${date}\n`;
    msg     += `Supplier: ${sup_name}\n`;
    msg     += `━━━━━━━━━━━━━━━━━━━━━\n`;
    msg     += `*ITEMS REQUIRED:*\n`;

    checkboxes.forEach(cb => {
        const pid  = cb.id.replace('b_', '');
        const name = cb.getAttribute('data-name');
        const qtyInput = document.querySelector(`.b-qty[data-id="${pid}"]`);
        const qty  = qtyInput ? qtyInput.value : cb.getAttribute('data-qty');
        msg += `• ${name} — ${qty} units\n`;
    });

    msg += `━━━━━━━━━━━━━━━━━━━━━\n`;
    msg += `Total Items: ${checkboxes.length}\n`;
    msg += `Sent via LoyalLoop CRM 🚀\n`;
    msg += `Please confirm receipt. 🙏`;

    const link = `https://wa.me/${wa_number}?text=${encodeURIComponent(msg)}`;
    window.open(link, '_blank');
}
</script>

</body>
</html>
