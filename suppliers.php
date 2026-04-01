<?php
include 'auth_session.php';
include 'db_connect.php';

$shop_id = $_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'];
$msg = '';
$msg_type = '';

// --- HANDLE ADD SUPPLIER ---
if (isset($_POST['add_supplier'])) {
    $name   = $conn->real_escape_string(trim($_POST['name']));
    $phone  = $conn->real_escape_string(trim($_POST['whatsapp_number']));
    $company = $conn->real_escape_string(trim($_POST['company']));
    $notes  = $conn->real_escape_string(trim($_POST['notes']));

    if ($name && $phone) {
        $sql = "INSERT INTO suppliers (shop_id, name, whatsapp_number, company, notes) 
                VALUES ('$shop_id', '$name', '$phone', '$company', '$notes')";
        if ($conn->query($sql)) {
            $msg = "✅ Supplier <strong>$name</strong> added successfully!";
            $msg_type = 'success';
        } else {
            $msg = "❌ Error: " . $conn->error;
            $msg_type = 'error';
        }
    } else {
        $msg = "⚠️ Name and WhatsApp number are required.";
        $msg_type = 'warning';
    }
}

// --- HANDLE DELETE SUPPLIER ---
if (isset($_GET['delete_id'])) {
    $did = (int)$_GET['delete_id'];
    // Unlink products first (set to NULL), then delete
    $conn->query("UPDATE products SET default_supplier_id = NULL WHERE default_supplier_id = '$did' AND shop_id = '$shop_id'");
    $conn->query("DELETE FROM suppliers WHERE id = '$did' AND shop_id = '$shop_id'");
    echo "<script>alert('Supplier removed.'); window.location='suppliers.php';</script>";
    exit();
}

// --- HANDLE EDIT SUPPLIER ---
if (isset($_POST['edit_supplier'])) {
    $eid     = (int)$_POST['edit_id'];
    $name    = $conn->real_escape_string(trim($_POST['edit_name']));
    $phone   = $conn->real_escape_string(trim($_POST['edit_phone']));
    $company = $conn->real_escape_string(trim($_POST['edit_company']));
    $notes   = $conn->real_escape_string(trim($_POST['edit_notes']));

    $conn->query("UPDATE suppliers SET name='$name', whatsapp_number='$phone', company='$company', notes='$notes'
                  WHERE id='$eid' AND shop_id='$shop_id'");
    $msg = "✅ Supplier updated successfully!";
    $msg_type = 'success';
}

// --- FETCH ALL SUPPLIERS WITH PRODUCT COUNT ---
$suppliers_result = $conn->query("
    SELECT s.*, COUNT(p.id) as product_count
    FROM suppliers s
    LEFT JOIN products p ON p.default_supplier_id = s.id AND p.shop_id = s.shop_id AND p.status='active'
    WHERE s.shop_id = '$shop_id'
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$suppliers = [];
while ($row = $suppliers_result->fetch_assoc()) $suppliers[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers – LoyalLoop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        .page-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 28px 30px;
            border-radius: 14px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(15,23,42,0.18);
        }
        .page-header h1 { font-size: 1.6rem; font-weight: 700; margin: 0; }
        .page-header p  { margin: 4px 0 0; color: #94a3b8; font-size: 0.9rem; }
        .page-header .header-icon {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        .supplier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .supplier-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #f1f5f9;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .supplier-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2563eb, #7c3aed);
        }
        .supplier-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .supplier-card .s-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 3px;
        }
        .supplier-card .s-company {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 14px;
        }
        .supplier-card .s-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: #475569;
            margin-bottom: 8px;
        }
        .supplier-card .s-detail i { 
            color: #2563eb; 
            width: 16px; 
            text-align: center; 
        }

        .product-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #eff6ff;
            color: #2563eb;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }
        .btn-sm {
            padding: 7px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-edit  { background: #eff6ff; color: #2563eb; }
        .btn-del   { background: #fef2f2; color: #ef4444; }
        .btn-wa    { background: #f0fdf4; color: #16a34a; }
        .btn-sm:hover { opacity: 0.85; transform: scale(0.98); }

        /* Add form */
        .add-form-card {
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }
        .add-form-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1.5fr;
            gap: 14px;
            align-items: end;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input, .form-group textarea, .form-group select {
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #1e293b;
            transition: border-color 0.2s;
            font-family: 'Inter', sans-serif;
            background: #fafafa;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
        }
        .btn-primary {
            padding: 10px 22px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error   { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-warning { background: #fffbeb; color: #92400e; border-left: 4px solid #fbbf24; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; display: block; }
        .empty-state p { font-size: 1rem; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 16px;
            padding: 32px;
            width: 500px;
            max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .modal .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }
        .modal .form-group.full { grid-column: 1 / -1; }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 24px;
        }
        .btn-cancel {
            padding: 10px 20px;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        .section-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f1f5f9;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-industry" style="color:#60a5fa; margin-right:10px;"></i>Supplier Management</h1>
            <p>Manage your suppliers and link them to products for smart order generation</p>
        </div>
        <div class="header-icon">🏭</div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- ADD SUPPLIER FORM -->
    <div class="add-form-card">
        <h3><i class="fas fa-plus-circle" style="color:#2563eb;"></i> Add New Supplier</h3>
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label>Supplier / Distributor Name *</label>
                    <input type="text" name="name" placeholder="e.g. Amul Distributor Delhi" required>
                </div>
                <div class="form-group">
                    <label>WhatsApp Number *</label>
                    <input type="tel" name="whatsapp_number" placeholder="91XXXXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label>Company / Brand</label>
                    <input type="text" name="company" placeholder="e.g. Gujarat Cooperative">
                </div>
            </div>
            <div style="margin-top:14px;">
                <div class="form-group">
                    <label>Notes (optional)</label>
                    <input type="text" name="notes" placeholder="Credit terms, delivery days, etc.">
                </div>
            </div>
            <div style="margin-top:18px;">
                <button type="submit" name="add_supplier" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Supplier
                </button>
            </div>
        </form>
    </div>

    <!-- SUPPLIERS LIST -->
    <div class="section-label">Your Suppliers (<?= count($suppliers) ?>)</div>

    <?php if (empty($suppliers)): ?>
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <p>No suppliers added yet. Add your first supplier above!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="supplier-grid">
            <?php foreach ($suppliers as $sup): ?>
                <div class="supplier-card">
                    <div class="s-name"><?= htmlspecialchars($sup['name']) ?></div>
                    <div class="s-company"><?= htmlspecialchars($sup['company'] ?: 'No company name') ?></div>

                    <div class="s-detail">
                        <i class="fab fa-whatsapp"></i>
                        <span><?= htmlspecialchars($sup['whatsapp_number']) ?></span>
                    </div>

                    <?php if ($sup['notes']): ?>
                    <div class="s-detail">
                        <i class="fas fa-sticky-note"></i>
                        <span><?= htmlspecialchars($sup['notes']) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="product-badge">
                        <i class="fas fa-box"></i>
                        <?= $sup['product_count'] ?> product<?= $sup['product_count'] != 1 ? 's' : '' ?> linked
                    </div>

                    <div class="card-actions">
                        <button class="btn-sm btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($sup)) ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a class="btn-sm btn-wa" href="https://wa.me/<?= $sup['whatsapp_number'] ?>" target="_blank">
                            <i class="fab fa-whatsapp"></i> Chat
                        </a>
                        <a class="btn-sm btn-del" 
                           href="suppliers.php?delete_id=<?= $sup['id'] ?>"
                           onclick="return confirm('Remove this supplier? Their products will be unlinked.')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top:16px; padding:16px; background:#eff6ff; border-radius:10px; font-size:0.87rem; color:#2563eb;">
        <i class="fas fa-info-circle"></i>
        <strong>Tip:</strong> After adding suppliers, go to <a href="inventory.php" style="color:#1d4ed8; font-weight:700;">Inventory</a> to link suppliers to your products. Then visit <a href="replenishment.php" style="color:#1d4ed8; font-weight:700;">Replenishment AI</a> to auto-generate orders!
    </div>

</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3><i class="fas fa-edit" style="color:#2563eb; margin-right:8px;"></i>Edit Supplier</h3>
        <form method="post">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Supplier Name *</label>
                    <input type="text" name="edit_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>WhatsApp Number *</label>
                    <input type="tel" name="edit_phone" id="edit_phone" required>
                </div>
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="edit_company" id="edit_company">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" name="edit_notes" id="edit_notes">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="edit_supplier" class="btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(sup) {
    document.getElementById('edit_id').value = sup.id;
    document.getElementById('edit_name').value = sup.name;
    document.getElementById('edit_phone').value = sup.whatsapp_number;
    document.getElementById('edit_company').value = sup.company || '';
    document.getElementById('edit_notes').value = sup.notes || '';
    document.getElementById('editModal').classList.add('active');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

</body>
</html>
