<?php 
include 'auth_session.php'; 
include 'db_connect.php'; 

$shop_id   = $_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'];
$msg = ''; $msg_type = '';

// --- WhatsApp Blast ---
if (isset($_POST['prepare_blast'])) {
    $_SESSION['campaign_msg']    = $_POST['bulk_msg'];
    $_SESSION['campaign_active'] = true;
}
if (isset($_POST['clear_blast'])) {
    unset($_SESSION['campaign_msg']);
    unset($_SESSION['campaign_active']);
}

// --- Email Blast ---
$email_ready = false;
if (isset($_POST['generate_email_link'])) {
    $subject  = $_POST['email_subject'];
    $raw_body = $_POST['email_body'];
    $sql = "SELECT email FROM customers WHERE shop_id='$shop_id' AND email IS NOT NULL AND email != ''";
    $res = $conn->query($sql);
    $emails = [];
    while ($r = $res->fetch_assoc()) $emails[] = $r['email'];
    if (!empty($emails)) {
        $final_body  = $raw_body . "\n\nWarm regards,\nThe Team at $shop_name";
        $mailto_link = "mailto:?bcc=" . implode(',', $emails) 
                     . "&subject=" . rawurlencode($subject) 
                     . "&body=" . rawurlencode($final_body);
        $email_ready = true;
        $email_count = count($emails);
        $msg = "✅ Ready to send to $email_count customers.";
        $msg_type = 'success';
    } else {
        $msg = "⚠️ No customers with email addresses found.";
        $msg_type = 'warning';
    }
}

// --- Delete customer ---
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    $conn->query("DELETE FROM customers WHERE id='$did' AND shop_id='$shop_id'");
    header("Location: customers.php"); exit();
}

// Stats
$total_c  = $conn->query("SELECT COUNT(*) as c FROM customers WHERE shop_id='$shop_id'")->fetch_assoc()['c'];
$wa_c     = $conn->query("SELECT COUNT(*) as c FROM customers WHERE shop_id='$shop_id' AND phone IS NOT NULL")->fetch_assoc()['c'];
$email_c  = $conn->query("SELECT COUNT(*) as c FROM customers WHERE shop_id='$shop_id' AND email IS NOT NULL AND email != ''")->fetch_assoc()['c'];
$loyal_c  = $conn->query("SELECT COUNT(*) as c FROM customers WHERE shop_id='$shop_id' AND visit_count >= 5")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers & CRM – LoyalLoop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── CRM-specific extras ── */

        /* ── Import Modal Styles ── */
        .import-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .import-modal-overlay.active { display: flex; }
        .import-modal {
            background: var(--surface);
            border-radius: 18px;
            width: 780px;
            max-width: 96vw;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 80px rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            overflow: hidden;
            animation: modalIn 0.22s ease;
        }
        .import-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #0f172a, #1e1b4b);
            color: white;
        }
        .import-modal-header h2 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }
        .import-modal-header p {
            font-size: 0.78rem;
            color: #94a3b8;
            margin: 3px 0 0;
        }
        .modal-x {
            width: 30px; height: 30px;
            border: none;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            cursor: pointer;
            color: white;
            font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .modal-x:hover { background: rgba(255,255,255,0.2); }

        /* Step indicator */
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
        }
        .step-dot {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            position: relative;
        }
        .step-dot.active  { color: var(--primary); }
        .step-dot.done    { color: var(--success); }
        .step-dot .dot-circle {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem;
            font-weight: 800;
            color: #9ca3af;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .step-dot.active .dot-circle {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
        }
        .step-dot.done .dot-circle {
            background: var(--success);
            color: white;
        }
        .step-line {
            flex: 1;
            height: 2px;
            background: #e5e7eb;
            margin: 0 12px;
        }
        .step-line.done { background: var(--success); }

        /* Modal body / footer */
        .import-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }
        .import-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--surface-2);
        }

        /* Upload area */
        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--surface-2);
        }
        .drop-zone:hover, .drop-zone.dragging {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .drop-zone i { font-size: 2.5rem; color: var(--text-muted); margin-bottom: 14px; display: block; }
        .drop-zone.dragging i { color: var(--primary); }
        .drop-zone h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px; }
        .drop-zone p  { font-size: 0.82rem; color: var(--text-secondary); margin: 0; }
        .drop-zone .file-types {
            display: flex; gap: 8px; justify-content: center; margin-top: 14px;
        }
        .file-type-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* Column mapping */
        .col-map-row {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .col-map-row:last-child { border-bottom: none; }
        .col-source {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 7px;
            padding: 8px 12px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #0284c7;
        }
        .col-arrow { color: var(--text-muted); font-size: 1rem; text-align:center; }

        /* Preview table */
        .preview-table-wrap { overflow: auto; max-height: 320px; border-radius: 10px; border: 1px solid var(--border); }
        .preview-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .preview-table th {
            position: sticky; top: 0;
            background: #1e293b; color: white;
            padding: 9px 12px;
            font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .preview-table td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; }
        .preview-table tr:last-child td { border-bottom: none; }
        .preview-table tr:hover td { background: #fafbff; }
        .preview-row-ok   td:first-child { border-left: 3px solid #22c55e; }
        .preview-row-warn td:first-child { border-left: 3px solid #f59e0b; }
        .preview-row-err  td:first-child { border-left: 3px solid #ef4444; }
        .row-status { font-size: 0.7rem; font-weight: 700; }

        /* Result summary */
        .result-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }
        .result-card {
            border-radius: 12px;
            padding: 18px;
            text-align: center;
        }
        .result-card .r-val  { font-size: 2rem; font-weight: 800; }
        .result-card .r-lbl  { font-size: 0.76rem; font-weight: 600; margin-top: 4px; }
        .result-imported { background: #f0fdf4; color: #166534; }
        .result-updated  { background: var(--primary-light); color: var(--primary-dark); }
        .result-skipped  { background: #fef2f2; color: #991b1b; }

        /* Template download hint */
        .template-hint {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.82rem;
            color: #78350f;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 16px;
        }
        .campaign-banner {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1.5px solid #86efac;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }
        .campaign-banner .c-msg {
            font-size: 0.86rem;
            color: #14532d;
            font-weight: 500;
        }
        .campaign-banner strong { color: #166534; }

        .blast-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .blast-card-header {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
        }
        .blast-card-header .b-icon {
            width: 34px; height: 34px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .blast-card-header h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        .blast-card-header p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin: 2px 0 0;
        }
        .blast-card-body { padding: 16px 18px; }

        .customer-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ddd6fe, #c7d2fe);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 800;
            color: #4f46e5;
            flex-shrink: 0;
        }
        .visit-dots {
            display: flex;
            gap: 3px;
        }
        .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #e5e7eb;
        }
        .dot.fill { background: var(--primary); }
        .dot.gold  { background: #f59e0b; }

        .wa-active-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0fdf4;
            color: #16a34a;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 700;
            border: 1px solid #86efac;
        }

        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-2);
            flex-wrap: wrap;
        }
        .filter-chip {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.77rem;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid var(--border-2);
            background: var(--surface);
            color: var(--text-secondary);
            transition: all 0.15s;
        }
        .filter-chip.active, .filter-chip:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Top bar -->
    <div class="topbar">
        <div class="topbar-title">
            <i class="fas fa-users" style="color:var(--primary);"></i>
            Customers & CRM
        </div>
        <div class="topbar-actions">
            <?php if (isset($_SESSION['campaign_active'])): ?>
            <span class="wa-active-badge">
                <i class="fas fa-circle" style="font-size:0.5rem;"></i> Campaign Active
            </span>
            <?php endif; ?>
            <button class="btn btn-secondary btn-sm" onclick="openImportModal()" title="Import from Excel or CSV">
                <i class="fas fa-file-import"></i> Import Customers
            </button>
            <input type="text" class="search-box" id="crmSearch" placeholder="Search customers...">
        </div>
    </div>

    <div class="page-body">

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg ?>
                <?php if ($email_ready): ?>
                    <a href="<?= $mailto_link ?>" target="_blank" class="btn btn-primary btn-sm" style="margin-left:auto;">
                        <i class="fas fa-paper-plane"></i> Open Email App
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Campaign Active Banner -->
        <?php if (isset($_SESSION['campaign_active']) && $_SESSION['campaign_active']): ?>
        <div class="campaign-banner">
            <div class="c-msg">
                <i class="fab fa-whatsapp" style="color:#22c55e; margin-right:6px;"></i>
                <strong>Campaign Active:</strong> "<?= htmlspecialchars($_SESSION['campaign_msg']) ?>"
                — Click <em>Send</em> next to any customer to send this message.
            </div>
            <form method="POST" style="margin:0;">
                <button type="submit" name="clear_blast" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> End Campaign
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card" style="--card-accent:#2563eb; --card-icon-bg:#eff6ff;">
                <div class="label">Total Customers</div>
                <div class="value"><?= $total_c ?></div>
                <div class="sub">All time</div>
                <div class="icon-bg"><i class="fas fa-users" style="font-size:0.9rem;"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#22c55e; --card-icon-bg:#f0fdf4;">
                <div class="label">WhatsApp Ready</div>
                <div class="value"><?= $wa_c ?></div>
                <div class="sub">Has phone number</div>
                <div class="icon-bg" style="color:#22c55e;"><i class="fab fa-whatsapp" style="font-size:0.9rem;"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#7c3aed; --card-icon-bg:#f5f3ff;">
                <div class="label">Email Subscribers</div>
                <div class="value"><?= $email_c ?></div>
                <div class="sub">Has email address</div>
                <div class="icon-bg" style="color:#7c3aed;"><i class="fas fa-envelope" style="font-size:0.9rem;"></i></div>
            </div>
            <div class="stat-card" style="--card-accent:#f59e0b; --card-icon-bg:#fffbeb;">
                <div class="label">Loyal Customers</div>
                <div class="value"><?= $loyal_c ?></div>
                <div class="sub">5+ visits</div>
                <div class="icon-bg" style="color:#f59e0b;"><i class="fas fa-star" style="font-size:0.9rem;"></i></div>
            </div>
        </div>

        <!-- BROADCAST TOOLS -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">

            <!-- WhatsApp Blast -->
            <div class="blast-card">
                <div class="blast-card-header">
                    <div class="b-icon" style="background:#f0fdf4; color:#22c55e;">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                        <h4>WhatsApp Campaign</h4>
                        <p>Set a message and click Send next to each customer</p>
                    </div>
                </div>
                <div class="blast-card-body">
                    <?php if (isset($_SESSION['campaign_active']) && $_SESSION['campaign_active']): ?>
                        <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px; margin-bottom:12px;">
                            <div style="font-size:0.78rem; font-weight:700; color:#166534; margin-bottom:4px;">ACTIVE MESSAGE</div>
                            <div style="font-size:0.86rem; color:#14532d; font-style:italic;">
                                "<?= htmlspecialchars($_SESSION['campaign_msg']) ?>"
                            </div>
                        </div>
                        <form method="POST">
                            <button type="submit" name="clear_blast" class="btn btn-danger" style="width:100%;">
                                <i class="fas fa-stop-circle"></i> Stop Campaign
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group" style="margin-bottom:12px;">
                                <label class="form-label">Message Template</label>
                                <textarea name="bulk_msg" class="form-control" rows="2" 
                                    placeholder="Hi {name}, 50% Off Sale this weekend! 🎉" required></textarea>
                            </div>
                            <button type="submit" name="prepare_blast" class="btn btn-wa" style="width:100%;">
                                <i class="fab fa-whatsapp"></i> Start Campaign
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email Blast -->
            <div class="blast-card">
                <div class="blast-card-header">
                    <div class="b-icon" style="background:var(--primary-light); color:var(--primary);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h4>Email Broadcast</h4>
                        <p>Opens your email app with all subscribers BCC'd</p>
                    </div>
                </div>
                <div class="blast-card-body">
                    <?php if ($email_ready && isset($mailto_link)): ?>
                        <div style="text-align:center; padding:16px 0;">
                            <div style="font-size:0.82rem; color:var(--success); margin-bottom:12px; font-weight:600;">
                                <i class="fas fa-check-circle"></i> Ready for <?= $email_count ?> subscribers
                            </div>
                            <a href="<?= $mailto_link ?>" target="_blank" class="btn btn-primary" style="width:100%;">
                                <i class="fas fa-paper-plane"></i> Open in Email App
                            </a>
                            <a href="customers.php" style="display:block; margin-top:10px; font-size:0.8rem; color:var(--text-secondary);">
                                ← Reset
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group" style="margin-bottom:10px;">
                                <label class="form-label">Subject Line</label>
                                <input type="text" name="email_subject" class="form-control" 
                                    placeholder="e.g., Exclusive Member Sale – This Weekend Only!" required>
                            </div>
                            <div class="form-group" style="margin-bottom:12px;">
                                <label class="form-label">Message</label>
                                <textarea name="email_body" class="form-control" rows="2" 
                                    placeholder="Dear valued customer, we have an exciting offer..." required></textarea>
                            </div>
                            <button type="submit" name="generate_email_link" class="btn btn-primary" style="width:100%;">
                                <i class="fas fa-envelope-open-text"></i> Generate Email Link
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- CUSTOMER TABLE -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Customer Directory</h3>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="badge badge-blue"><?= $total_c ?> total</span>
                </div>
            </div>

            <div class="filter-bar">
                <span style="font-size:0.78rem; font-weight:600; color:var(--text-secondary);">Filter:</span>
                <button class="filter-chip active" onclick="filterCustomers('all', this)">All</button>
                <button class="filter-chip" onclick="filterCustomers('loyal', this)">⭐ Loyal (5+ visits)</button>
                <button class="filter-chip" onclick="filterCustomers('email', this)">📧 Has Email</button>
                <button class="filter-chip" onclick="filterCustomers('new', this)">🆕 New (1 visit)</button>
            </div>

            <div class="table-container">
                <table id="customerTable">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Visits</th>
                            <th>Last Visit</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sql = "SELECT * FROM customers WHERE shop_id='$shop_id' ORDER BY last_visit DESC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $init    = strtoupper(substr($row['name'] ?: '?', 0, 1));
                            $visits  = (int)$row['visit_count'];
                            $is_loyal = $visits >= 5;
                            $days_ago = $row['last_visit'] 
                                ? floor((time() - strtotime($row['last_visit'])) / 86400) 
                                : null;

                            // WA message
                            $raw_msg = "Hello {$row['name']}, thank you for visiting!";
                            if (isset($_SESSION['campaign_active']) && $_SESSION['campaign_active']) {
                                $raw_msg = str_replace('{name}', $row['name'], $_SESSION['campaign_msg']);
                            }
                            $wa_link = "https://wa.me/91{$row['phone']}?text=" . urlencode($raw_msg);

                            // Visit dots (max 7)
                            $dots = '';
                            for ($d = 1; $d <= min(7, $visits); $d++) {
                                $cls = $is_loyal ? 'dot gold' : 'dot fill';
                                $dots .= "<div class='$cls'></div>";
                            }
                            for ($d = $visits + 1; $d <= 7; $d++) $dots .= "<div class='dot'></div>";

                            $badge = $is_loyal 
                                ? "<span class='badge badge-orange'>⭐ Loyal</span>"
                                : ($visits == 1 
                                    ? "<span class='badge badge-blue'>🆕 New</span>"
                                    : "<span class='badge badge-gray'>Regular</span>");

                            $email_display = !empty($row['email']) 
                                ? htmlspecialchars($row['email']) 
                                : "<span style='color:var(--text-muted); font-style:italic;'>—</span>";

                            $last_visit_str = $days_ago !== null 
                                ? ($days_ago == 0 ? 'Today' : ($days_ago == 1 ? 'Yesterday' : "$days_ago days ago"))
                                : '—';

                            // Row data attributes for JS filtering  
                            $data_loyal = $is_loyal ? 'true' : 'false';
                            $data_email = !empty($row['email']) ? 'true' : 'false';
                            $data_new   = $visits == 1 ? 'true' : 'false';
                    ?>
                    <tr data-loyal="<?= $data_loyal ?>" data-email="<?= $data_email ?>" data-new="<?= $data_new ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div class="customer-avatar"><?= $init ?></div>
                                <div>
                                    <div style="font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($row['name']) ?></div>
                                    <?php if ($row['loyalty_points'] > 0): ?>
                                    <div style="font-size:0.72rem; color:var(--warning);">
                                        <i class="fas fa-coins"></i> <?= $row['loyalty_points'] ?> pts
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-size:0.85rem;"><?= htmlspecialchars($row['phone']) ?></span>
                        </td>
                        <td style="font-size:0.82rem; color:var(--info);"><?= $email_display ?></td>
                        <td>
                            <div style="margin-bottom:4px; font-weight:700; font-size:0.88rem;"><?= $visits ?></div>
                            <div class="visit-dots"><?= $dots ?></div>
                        </td>
                        <td style="font-size:0.82rem; color:var(--text-secondary);"><?= $last_visit_str ?></td>
                        <td><?= $badge ?></td>
                        <td>
                            <div style="display:flex; gap:6px; align-items:center;">
                                <a href="<?= $wa_link ?>" target="_blank" 
                                   class="btn btn-sm btn-wa" 
                                   title="<?= isset($_SESSION['campaign_active']) ? 'Send Campaign Message' : 'Send WhatsApp' ?>">
                                    <i class="fab fa-whatsapp"></i> 
                                    <?= isset($_SESSION['campaign_active']) ? 'Send' : 'WA' ?>
                                </a>
                                <a href="customers.php?delete_id=<?= $row['id'] ?>"
                                   onclick="return confirm('Delete this customer permanently?')"
                                   class="btn btn-sm" style="background:var(--danger-bg); color:var(--danger);">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px 20px; color:var(--text-muted);">
                            <i class="fas fa-users" style="font-size:2rem; display:block; margin-bottom:10px; opacity:0.3;"></i>
                            No customers yet. Start billing to automatically add customers!
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- end page-body -->
</div><!-- end main-content -->

<script>
// Search
document.getElementById('crmSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#customerTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Filter chips
function filterCustomers(type, btn) {
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#customerTable tbody tr').forEach(row => {
        if (type === 'all') { row.style.display = ''; return; }
        if (type === 'loyal') row.style.display = row.dataset.loyal === 'true' ? '' : 'none';
        if (type === 'email') row.style.display = row.dataset.email === 'true' ? '' : 'none';
        if (type === 'new')   row.style.display = row.dataset.new   === 'true' ? '' : 'none';
    });
}
</script>

<!-- ═══════════════════════════════════════
     IMPORT CUSTOMERS MODAL
═══════════════════════════════════════ -->
<div class="import-modal-overlay" id="importModalOverlay">
  <div class="import-modal">

    <!-- Header -->
    <div class="import-modal-header">
      <div>
        <h2><i class="fas fa-file-import" style="margin-right:8px; color:#60a5fa;"></i>Import Customers from Excel / CSV</h2>
        <p>Upload your spreadsheet, map the columns, preview, and import in one click.</p>
      </div>
      <button class="modal-x" onclick="closeImportModal()">✕</button>
    </div>

    <!-- Step Indicator -->
    <div class="step-indicator">
      <div class="step-dot active" id="step-dot-1">
        <div class="dot-circle" id="step-circle-1">1</div>
        <span>Upload File</span>
      </div>
      <div class="step-line" id="step-line-1"></div>
      <div class="step-dot" id="step-dot-2">
        <div class="dot-circle" id="step-circle-2">2</div>
        <span>Map Columns</span>
      </div>
      <div class="step-line" id="step-line-2"></div>
      <div class="step-dot" id="step-dot-3">
        <div class="dot-circle" id="step-circle-3">3</div>
        <span>Preview &amp; Import</span>
      </div>
      <div class="step-line" id="step-line-3"></div>
      <div class="step-dot" id="step-dot-4">
        <div class="dot-circle" id="step-circle-4">4</div>
        <span>Done</span>
      </div>
    </div>

    <!-- Body -->
    <div class="import-modal-body">

      <!-- ── STEP 1: Upload ── -->
      <div id="import-step-1">
        <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
          <i class="fas fa-cloud-upload-alt" id="dropIcon"></i>
          <h3>Drag & Drop your file here</h3>
          <p>or click to browse from your computer</p>
          <div class="file-types">
            <span class="file-type-badge" style="background:#f0fdf4; color:#166534;">📗 .xlsx</span>
            <span class="file-type-badge" style="background:#eff6ff; color:#2563eb;">📘 .xls</span>
            <span class="file-type-badge" style="background:#faf5ff; color:#7c3aed;">📄 .csv</span>
          </div>
        </div>
        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none" onchange="handleFileSelect(this.files[0])">

        <div class="template-hint">
          <i class="fas fa-lightbulb" style="color:#f59e0b; flex-shrink:0; margin-top:2px;"></i>
          <div>
            <strong>Pro tip:</strong> Your spreadsheet should have column headers like 
            <code style="background:#fff; padding:1px 5px; border-radius:4px;">Name</code>, 
            <code style="background:#fff; padding:1px 5px; border-radius:4px;">Phone</code>, 
            <code style="background:#fff; padding:1px 5px; border-radius:4px;">Email</code> — but any header names work, you'll map them in the next step.
            <br><br>
            <button onclick="downloadTemplate()" class="btn btn-sm btn-secondary" style="margin-top:4px;">
              <i class="fas fa-download"></i> Download Sample Template
            </button>
          </div>
        </div>
      </div>

      <!-- ── STEP 2: Column Mapping ── -->
      <div id="import-step-2" style="display:none;">
        <div style="margin-bottom:18px;">
          <div style="font-size:0.9rem; font-weight:700; color:var(--text-primary); margin-bottom:4px;">
            📋 Map Your Spreadsheet Columns
          </div>
          <div style="font-size:0.82rem; color:var(--text-secondary);">We detected the following columns. Tell us which one corresponds to each customer field.</div>
        </div>

        <div style="background:var(--surface-2); border-radius:12px; padding:16px; border:1px solid var(--border);">
          <div style="display:grid; grid-template-columns:1fr auto 1fr; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:var(--text-muted); margin-bottom:10px; gap:12px;">
            <span>Your Column</span><span></span><span>Maps to Field</span>
          </div>

          <div class="col-map-row">
            <div class="col-source">Required</div>
            <div class="col-arrow">→</div>
            <select class="form-control" id="map_phone" style="font-size:0.84rem;">
              <option value="">— Select column for Phone —</option>
            </select>
          </div>
          <div style="font-size:0.72rem; color:var(--danger); margin:-8px 0 10px; padding-left:4px;">
            <i class="fas fa-asterisk"></i> Phone is required — rows without a valid 10-digit number will be skipped.
          </div>

          <div class="col-map-row">
            <div class="col-source">Optional</div>
            <div class="col-arrow">→</div>
            <select class="form-control" id="map_name" style="font-size:0.84rem;">
              <option value="">— Select column for Name —</option>
            </select>
          </div>

          <div class="col-map-row">
            <div class="col-source">Optional</div>
            <div class="col-arrow">→</div>
            <select class="form-control" id="map_email" style="font-size:0.84rem;">
              <option value="">— Select column for Email —</option>
            </select>
          </div>
        </div>

        <div id="col-map-info" style="margin-top:14px; font-size:0.82rem; color:var(--text-secondary);">
          <i class="fas fa-table"></i> <strong id="rowCountLabel">0 rows</strong> detected in your file.
        </div>
      </div>

      <!-- ── STEP 3: Preview ── -->
      <div id="import-step-3" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
          <div>
            <div style="font-size:0.9rem; font-weight:700; color:var(--text-primary);">📋 Preview — First 10 rows</div>
            <div style="font-size:0.78rem; color:var(--text-secondary); margin-top:2px;">
              Green = ready to import · Orange = will be updated · Red = will be skipped (invalid phone)
            </div>
          </div>
          <div id="previewStats" style="font-size:0.82rem; font-weight:600; color:var(--text-secondary);"></div>
        </div>
        <div class="preview-table-wrap">
          <table class="preview-table" id="previewTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="previewBody"></tbody>
          </table>
        </div>
      </div>

      <!-- ── STEP 4: Result ── -->
      <div id="import-step-4" style="display:none;">
        <div style="text-align:center; margin-bottom:20px;">
          <div style="font-size:3rem; margin-bottom:8px;">🎉</div>
          <div style="font-size:1.1rem; font-weight:800; color:var(--text-primary);">Import Complete!</div>
          <div style="font-size:0.84rem; color:var(--text-secondary); margin-top:4px;">Your customer database has been updated.</div>
        </div>
        <div class="result-grid">
          <div class="result-card result-imported">
            <div class="r-val" id="res-imported">0</div>
            <div class="r-lbl">✅ New Customers Added</div>
          </div>
          <div class="result-card result-updated">
            <div class="r-val" id="res-updated">0</div>
            <div class="r-lbl">🔄 Existing Updated</div>
          </div>
          <div class="result-card result-skipped">
            <div class="r-val" id="res-skipped">0</div>
            <div class="r-lbl">⚠️ Rows Skipped</div>
          </div>
        </div>
        <div id="error-log-wrap" style="display:none;">
          <div style="font-size:0.8rem; font-weight:700; color:var(--danger); margin-bottom:8px;">
            <i class="fas fa-exclamation-triangle"></i> Skipped Rows:
          </div>
          <div id="error-log" style="background:#fef2f2; border-radius:8px; padding:12px; font-size:0.78rem; color:#991b1b; max-height:140px; overflow-y:auto; line-height:1.8;"></div>
        </div>
      </div>

    </div><!-- end modal body -->

    <!-- Footer -->
    <div class="import-modal-footer">
      <button class="btn btn-ghost" id="importBackBtn" onclick="importBack()" style="display:none;">
        <i class="fas fa-arrow-left"></i> Back
      </button>
      <div style="margin-left:auto; display:flex; gap:10px;">
        <button class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
        <button class="btn btn-primary" id="importNextBtn" onclick="importNext()" disabled>
          Continue <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- SheetJS (handles both Excel & CSV) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// ── Import Modal State ──
let importStep = 1;
let parsedData  = [];   // raw rows from SheetJS as array of objects
let headers     = [];   // column header names from spreadsheet
let mappedRows  = [];   // final rows after column mapping

// ── Open / Close ──
function openImportModal() {
    importStep = 1;
    parsedData = []; headers = []; mappedRows = [];
    document.getElementById('fileInput').value = '';
    showStep(1);
    updateStepUI();
    document.getElementById('importModalOverlay').classList.add('active');
    resetDropZone();
}
function closeImportModal() {
    document.getElementById('importModalOverlay').classList.remove('active');
}
document.getElementById('importModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});

// ── Step Navigation ──
function showStep(n) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('import-step-' + i);
        if (el) el.style.display = (i === n) ? 'block' : 'none';
    }
    importStep = n;
    updateStepUI();
    updateFooterBtns();
}
function importNext() {
    if (importStep === 1) return; // handled by file select
    if (importStep === 2) {
        if (!document.getElementById('map_phone').value) {
            alert('⚠️ Please select the Phone column — it is required.');
            return;
        }
        buildMappedRows();
        buildPreview();
        showStep(3);
    } else if (importStep === 3) {
        doImport();
    }
}
function importBack() {
    if (importStep > 1) showStep(importStep - 1);
}
function updateStepUI() {
    for (let i = 1; i <= 4; i++) {
        const dot    = document.getElementById('step-dot-' + i);
        const circle = document.getElementById('step-circle-' + i);
        const line   = document.getElementById('step-line-' + i);
        if (dot) {
            dot.className = 'step-dot' + (i === importStep ? ' active' : (i < importStep ? ' done' : ''));
            if (i < importStep) circle.innerHTML = '✓';
            else circle.innerHTML = i;
        }
        if (line) line.className = 'step-line' + (i < importStep ? ' done' : '');
    }
}
function updateFooterBtns() {
    const next = document.getElementById('importNextBtn');
    const back = document.getElementById('importBackBtn');
    const cancel = next.nextElementSibling && next.previousElementSibling;

    if (importStep === 1) {
        next.style.display = 'none';
        back.style.display = 'none';
    } else if (importStep === 2) {
        next.style.display = 'inline-flex';
        next.innerHTML = 'Preview <i class="fas fa-eye" style="margin-left:6px;"></i>';
        next.disabled = false;
        back.style.display = 'inline-flex';
    } else if (importStep === 3) {
        next.style.display = 'inline-flex';
        next.innerHTML = '<i class="fas fa-upload" style="margin-right:6px;"></i> Import Now';
        next.className = 'btn btn-primary';
        next.style.background = '#059669';
        back.style.display = 'inline-flex';
    } else if (importStep === 4) {
        next.style.display = 'none';
        back.style.display = 'none';
        // Replace cancel with "Done"
        document.querySelector('.import-modal-footer .btn-secondary').textContent = 'Close & Refresh';
        document.querySelector('.import-modal-footer .btn-secondary').onclick = function() { location.reload(); };
    }
}

// ── Drop Zone ──
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragging'); });
dropZone.addEventListener('dragleave',() => dropZone.classList.remove('dragging'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragging');
    const file = e.dataTransfer.files[0];
    if (file) handleFileSelect(file);
});
function resetDropZone() {
    dropZone.innerHTML = `
        <i class="fas fa-cloud-upload-alt" style="font-size:2.5rem; color:var(--text-muted); margin-bottom:14px; display:block;"></i>
        <h3>Drag & Drop your file here</h3>
        <p>or click to browse from your computer</p>
        <div style="display:flex; gap:8px; justify-content:center; margin-top:14px;">
            <span class="file-type-badge" style="background:#f0fdf4; color:#166534;">📗 .xlsx</span>
            <span class="file-type-badge" style="background:#eff6ff; color:#2563eb;">📘 .xls</span>
            <span class="file-type-badge" style="background:#faf5ff; color:#7c3aed;">📄 .csv</span>
        </div>`;
}

// ── File Parsing (SheetJS) ──
function handleFileSelect(file) {
    if (!file) return;
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx','xls','csv'].includes(ext)) {
        alert('⚠️ Please upload an .xlsx, .xls, or .csv file.'); return;
    }

    // Show loading state on drop zone
    dropZone.innerHTML = `<i class="fas fa-spinner fa-spin" style="font-size:2rem; color:var(--primary); display:block; margin-bottom:12px;"></i>
        <h3>Reading ${file.name}...</h3><p>Please wait</p>`;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const workbook = XLSX.read(e.target.result, { type: 'binary' });
            const sheetName = workbook.SheetNames[0];
            const sheet = workbook.Sheets[sheetName];
            const json = XLSX.utils.sheet_to_json(sheet, { defval: '' });

            if (!json.length) {
                alert('⚠️ The file appears to be empty or has no readable rows.');
                resetDropZone(); return;
            }

            parsedData = json;
            headers = Object.keys(json[0]);

            // Show success in drop zone
            dropZone.innerHTML = `
                <i class="fas fa-check-circle" style="font-size:2.5rem; color:#22c55e; margin-bottom:12px; display:block;"></i>
                <h3 style="color:#166534;">${file.name}</h3>
                <p style="color:#166534;">${json.length} rows detected &mdash; ready to map columns</p>`;

            // Populate column selects
            populateColumnSelectors();
            document.getElementById('rowCountLabel').textContent = json.length + ' rows';
            showStep(2);
        } catch(err) {
            alert('❌ Could not read the file. Make sure it is a valid Excel or CSV file.\n' + err.message);
            resetDropZone();
        }
    };
    reader.readAsBinaryString(file);
}

function populateColumnSelectors() {
    const selectors = ['map_phone','map_name','map_email'];
    const guessMap  = { map_phone: ['phone','mobile','contact','number','ph','cell'], map_name: ['name','customer','full name','fullname'], map_email: ['email','e-mail','mail'] };
    selectors.forEach(id => {
        const sel = document.getElementById(id);
        sel.innerHTML = '<option value="">— Not in my file —</option>';
        headers.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h; opt.textContent = h;
            sel.appendChild(opt);
        });
        // Auto-guess
        const guesses = guessMap[id] || [];
        for (const h of headers) {
            if (guesses.some(g => h.toLowerCase().includes(g))) { sel.value = h; break; }
        }
    });
}

// ── Build mapped rows ──
function buildMappedRows() {
    const phoneCol = document.getElementById('map_phone').value;
    const nameCol  = document.getElementById('map_name').value;
    const emailCol = document.getElementById('map_email').value;

    mappedRows = parsedData.map(row => ({
        phone: phoneCol ? String(row[phoneCol] || '') : '',
        name:  nameCol  ? String(row[nameCol]  || '') : '',
        email: emailCol ? String(row[emailCol] || '') : '',
    }));
}

// ── Preview ──
function buildPreview() {
    const tbody = document.getElementById('previewBody');
    const preview = mappedRows.slice(0, 10);
    let okCount = 0, errCount = 0;

    tbody.innerHTML = preview.map((r, i) => {
        const phoneClean = r.phone.replace(/\D/g, '')
                            .replace(/^91(\d{10})$/, '$1')
                            .replace(/^091(\d{10})$/, '$1');
        const valid = phoneClean.length === 10;
        if (valid) okCount++; else errCount++;
        const rowClass = valid ? 'preview-row-ok' : 'preview-row-err';
        const status = valid
            ? `<span class="row-status" style="color:#166534;">✓ Ready</span>`
            : `<span class="row-status" style="color:#dc2626;">✗ Invalid phone</span>`;
        return `<tr class="${rowClass}">
            <td style="color:var(--text-muted);">${i+1}</td>
            <td>${r.name || '<span style="color:var(--text-muted)">—</span>'}</td>
            <td style="font-family:monospace;">${r.phone || '<span style="color:var(--text-muted)">—</span>'}</td>
            <td>${r.email || '<span style="color:var(--text-muted)">—</span>'}</td>
            <td>${status}</td>
        </tr>`;
    }).join('');

    const remaining = mappedRows.length - preview.length;
    document.getElementById('previewStats').innerHTML =
        `<span style="color:var(--success);">✓ ${okCount} valid</span> &nbsp;
         <span style="color:var(--danger);">✗ ${errCount} invalid</span>
         ${remaining > 0 ? `<span style="color:var(--text-muted);"> + ${remaining} more rows not shown</span>` : ''}`;
}

// ── Import ──
function doImport() {
    const nextBtn = document.getElementById('importNextBtn');
    nextBtn.disabled = true;
    nextBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';

    const formData = new FormData();
    formData.append('rows', JSON.stringify(mappedRows));

    fetch('import_customers.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            document.getElementById('res-imported').textContent = data.imported || 0;
            document.getElementById('res-updated').textContent  = data.updated  || 0;
            document.getElementById('res-skipped').textContent  = data.skipped  || 0;
            if (data.errors && data.errors.length) {
                document.getElementById('error-log-wrap').style.display = 'block';
                document.getElementById('error-log').innerHTML = data.errors.map(e => '• ' + e).join('<br>');
            }
            showStep(4);
        })
        .catch(() => {
            alert('❌ Import failed. Please try again.');
            nextBtn.disabled = false;
            nextBtn.innerHTML = '<i class="fas fa-upload"></i> Import Now';
        });
}

// ── Template Download ──
function downloadTemplate() {
    const ws_data = [
        ['Name', 'Phone', 'Email'],
        ['Rahul Sharma', '9876543210', 'rahul@example.com'],
        ['Priya Verma',  '9123456789', 'priya@example.com'],
        ['Amit Patel',   '9988776655', ''],
    ];
    const ws = XLSX.utils.aoa_to_sheet(ws_data);
    ws['!cols'] = [{wch:20},{wch:15},{wch:30}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Customers');
    XLSX.writeFile(wb, 'LoyalLoop_Customer_Template.xlsx');
}
</script>

</body>
</html>