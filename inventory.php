<?php
include 'auth_session.php';
include 'db_connect.php';

$shop_id = $_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'];
$msg = ''; $msg_type = '';

// --- 1. HANDLE ADD PRODUCT ---
if (isset($_POST['add_product'])) {
    $name     = $conn->real_escape_string(trim($_POST['name']));
    $price    = floatval($_POST['price']);
    $stock    = intval($_POST['stock']);
    $expiry   = $conn->real_escape_string($_POST['expiry']);
    $reorder  = intval($_POST['reorder_level'] ?? 10);
    $category = $conn->real_escape_string(trim($_POST['category'] ?? 'General'));
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 'NULL';

    $sql = "INSERT INTO products (shop_id, name, price, stock_qty, expiry_date, status, reorder_level, category, default_supplier_id) 
            VALUES ('$shop_id', '$name', '$price', '$stock', '$expiry', 'active', '$reorder', '$category', $supplier_id)";
    
    if ($conn->query($sql) === TRUE) {
        $msg = "✅ Product <strong>$name</strong> added successfully!";
        $msg_type = 'success';
    } else {
        $msg = "❌ Error: " . $conn->error;
        $msg_type = 'error';
    }
}

// --- 2. HANDLE DELETE (SOFT DELETE) ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("UPDATE products SET status = 'deleted' WHERE id='$id' AND shop_id='$shop_id'");
    echo "<script>alert('Product removed.'); window.location='inventory.php';</script>";
    exit();
}

// --- 3. HANDLE AJAX: Update Supplier for a Product ---
if (isset($_POST['ajax_update_supplier'])) {
    $pid = intval($_POST['product_id']);
    $sid = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 'NULL';
    $conn->query("UPDATE products SET default_supplier_id = $sid WHERE id = '$pid' AND shop_id = '$shop_id'");
    echo json_encode(['status' => 'ok']);
    exit();
}

// --- Fetch Suppliers for dropdown ---
$sup_result = $conn->query("SELECT id, name FROM suppliers WHERE shop_id='$shop_id' ORDER BY name ASC");
$suppliers = [];
while ($row = $sup_result->fetch_assoc()) $suppliers[] = $row;

// --- Fetch products ---
$result = $conn->query("
    SELECT p.*, s.name as supplier_name 
    FROM products p
    LEFT JOIN suppliers s ON p.default_supplier_id = s.id
    WHERE p.shop_id='$shop_id' AND p.status='active'
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory – LoyalLoop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        .page-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 26px 30px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 28px rgba(15,23,42,0.18);
        }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .page-header p  { margin: 4px 0 0; color: #94a3b8; font-size: 0.88rem; }

        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error   { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }

        .add-form-card {
            background: white;
            border-radius: 14px;
            padding: 26px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        .add-form-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr 1.2fr 1.2fr 1.5fr auto;
            gap: 12px;
            align-items: end;
        }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input, .form-group select {
            padding: 9px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.88rem;
            color: #1e293b;
            transition: border-color 0.2s;
            font-family: 'Inter', sans-serif;
            background: #fafafa;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            background: white;
        }
        .btn-add {
            padding: 10px 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-add:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Stock Table */
        .stock-card {
            background: white;
            border-radius: 14px;
            padding: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .stock-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stock-card-header h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 11px 18px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #f1f5f9;
        }
        td {
            padding: 13px 18px;
            border-bottom: 1px solid #f8fafc;
            font-size: 0.88rem;
            color: #334155;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbff; }

        .stock-low  { color: #dc2626; font-weight: 700; }
        .stock-ok   { color: #059669; font-weight: 700; }
        .expiry-warn { color: #d97706; font-weight: 600; }
        .expiry-danger { color: #dc2626; font-weight: 700; }

        /* Inline supplier dropdown */
        .supplier-select {
            padding: 5px 9px;
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
            color: #334155;
            background: #fafafa;
            cursor: pointer;
            transition: border-color 0.2s;
            max-width: 160px;
        }
        .supplier-select:focus { outline: none; border-color: #2563eb; }
        .supplier-select.saving { border-color: #f59e0b; }
        .supplier-select.saved  { border-color: #22c55e; }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px; height: 32px;
            border-radius: 7px;
            text-decoration: none;
            transition: all 0.15s;
        }
        .action-link.edit { background: #eff6ff; color: #2563eb; }
        .action-link.del  { background: #fef2f2; color: #ef4444; }
        .action-link:hover { transform: scale(1.1); }

        .search-box {
            padding: 8px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            width: 220px;
        }
        .search-box:focus { outline: none; border-color: #2563eb; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-boxes" style="color:#60a5fa; margin-right:10px;"></i>Inventory</h1>
            <p>Manage stock, link suppliers, and set reorder levels for smart replenishment</p>
        </div>
        <div style="font-size:0.85rem; color:#60a5fa;">
            <i class="fas fa-lightbulb"></i> Tip: Link suppliers to enable AI ordering
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- ADD PRODUCT FORM -->
    <div class="add-form-card">
        <h3><i class="fas fa-plus-circle" style="color:#2563eb;"></i> Add New Product</h3>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" placeholder="e.g. Amul Butter 500g" required>
                </div>
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Stock Qty</label>
                    <input type="number" name="stock" placeholder="0" required>
                </div>
                <div class="form-group">
                    <label>Reorder Level</label>
                    <input type="number" name="reorder_level" placeholder="10" value="10">
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry" required>
                </div>
                <div class="form-group">
                    <label>Supplier (Optional)</label>
                    <select name="supplier_id">
                        <option value="">— No Supplier —</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" name="add_product" class="btn-add">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>
            <div style="margin-top:12px;">
                <div class="form-group" style="max-width:200px;">
                    <label>Category</label>
                    <select name="category">
                        <option value="General">General</option>
                        <option value="Dairy">Dairy</option>
                        <option value="Beverages">Beverages</option>
                        <option value="Snacks">Snacks</option>
                        <option value="Groceries">Groceries</option>
                        <option value="Personal Care">Personal Care</option>
                        <option value="Household">Household</option>
                        <option value="Frozen">Frozen</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- STOCK TABLE -->
    <div class="stock-card">
        <div class="stock-card-header">
            <h3>📦 Current Stock (<?= $result->num_rows ?> items)</h3>
            <input type="text" class="search-box" id="searchInput" placeholder="🔍 Search products..." onkeyup="filterTable()">
        </div>

        <table id="productTable">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Reorder At</th>
                    <th>Expiry Date</th>
                    <th>Supplier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $today = date('Y-m-d');
                $warn_date = date('Y-m-d', strtotime('+45 days'));

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $is_low     = $row['stock_qty'] <= $row['reorder_level'];
                        $is_expired = $row['expiry_date'] < $today;
                        $is_expiring = !$is_expired && $row['expiry_date'] <= $warn_date;

                        $stock_class  = $is_low  ? 'stock-low'   : 'stock-ok';
                        $expiry_class = $is_expired ? 'expiry-danger' : ($is_expiring ? 'expiry-warn' : '');

                        // Build supplier options
                        $sup_options = "<option value=''>— None —</option>";
                        foreach ($suppliers as $sup) {
                            $sel = ($sup['id'] == $row['default_supplier_id']) ? 'selected' : '';
                            $sup_options .= "<option value='{$sup['id']}' $sel>" . htmlspecialchars($sup['name']) . "</option>";
                        }

                        echo "
                        <tr>
                            <td>
                                <strong>{$row['name']}</strong>
                                " . ($is_low ? "<br><span style='font-size:0.72rem; color:#dc2626;'>⚠️ Low Stock</span>" : "") . "
                            </td>
                            <td>₹{$row['price']}</td>
                            <td class='$stock_class'>{$row['stock_qty']}</td>
                            <td style='color:#64748b;'>{$row['reorder_level']}</td>
                            <td class='$expiry_class'>
                                {$row['expiry_date']}
                                " . ($is_expired ? "<br><span style='font-size:0.72rem;'>🔴 EXPIRED</span>" : ($is_expiring ? "<br><span style='font-size:0.72rem;'>⚠️ Expiring Soon</span>" : "")) . "
                            </td>
                            <td>
                                <select class='supplier-select' 
                                        onchange='updateSupplier(this, {$row['id']})'>
                                    $sup_options
                                </select>
                            </td>
                            <td>
                                <a href='edit_product.php?id={$row['id']}' class='action-link edit' title='Edit'>
                                    <i class='fas fa-edit'></i>
                                </a>
                                &nbsp;
                                <a href='inventory.php?delete_id={$row['id']}' 
                                   class='action-link del' title='Remove'
                                   onclick='return confirm(\"Remove this product?\")'>
                                    <i class='fas fa-trash'></i>
                                </a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#94a3b8;'>No products yet. Add your first product above!</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Inline supplier update via AJAX
function updateSupplier(select, productId) {
    select.classList.add('saving');
    const supplierId = select.value;

    fetch('inventory.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `ajax_update_supplier=1&product_id=${productId}&supplier_id=${supplierId}`
    })
    .then(r => r.json())
    .then(data => {
        select.classList.remove('saving');
        if (data.status === 'ok') {
            select.classList.add('saved');
            setTimeout(() => select.classList.remove('saved'), 1500);
        }
    })
    .catch(() => {
        select.classList.remove('saving');
    });
}

// Table search filter
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#productTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>

</body>
</html>