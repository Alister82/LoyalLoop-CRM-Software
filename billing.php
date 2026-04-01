<?php 
include 'auth_session.php'; 
include 'db_connect.php'; 
$shop_id = $_SESSION['shop_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS / Billing – LoyalLoop</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .pos-container {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 16px;
            height: calc(100vh - 57px);
            overflow: hidden;
        }

        .pos-left { overflow-y: auto; padding: 20px; }
        .pos-right {
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Customer Section */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 14px;
            overflow: hidden;
        }
        .section-card-header {
            padding: 12px 16px;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-card-body { padding: 16px; }

        /* Cart items */
        .cart-items { flex: 1; overflow-y: auto; padding: 0; }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.1s;
        }
        .cart-item:hover { background: var(--surface-2); }
        .cart-item .ci-name { flex: 1; font-size: 0.86rem; font-weight: 600; color: var(--text-primary); }
        .cart-item .ci-price { font-size: 0.8rem; color: var(--text-secondary); }
        .cart-item .qty-control {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .qty-btn {
            width: 26px; height: 26px;
            border: 1.5px solid var(--border-2);
            border-radius: 6px;
            background: var(--surface-2);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.1s;
        }
        .qty-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .qty-input {
            width: 38px;
            text-align: center;
            border: 1.5px solid var(--border-2);
            border-radius: 6px;
            padding: 3px;
            font-size: 0.86rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }
        .cart-item .ci-total { font-weight: 700; font-size: 0.86rem; color: var(--text-primary); min-width:54px; text-align:right; }
        .ci-remove { background:none; border:none; color:#e2e8f0; cursor:pointer; font-size:0.9rem; padding:3px; transition:color 0.1s; }
        .ci-remove:hover { color:#ef4444; }

        .cart-footer {
            border-top: 2px solid var(--border);
            background: var(--surface);
        }
        .cart-summary { padding: 14px 16px; }
        .cart-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
        .cart-row .cr-label { font-size:0.82rem; color:var(--text-secondary); }
        .cart-row .cr-val { font-size:0.86rem; font-weight:600; }
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 0;
            border-top: 1px solid var(--border);
            margin-top: 6px;
        }
        .cart-total-row .total-label { font-size:0.9rem; font-weight:700; color:var(--text-primary); }
        .cart-total-row .total-val { font-size:1.4rem; font-weight:800; color:var(--primary); letter-spacing:-0.03em; }

        /* Product selector */
        .product-search {
            position: relative;
            margin-bottom: 12px;
        }
        .product-search input {
            width: 100%;
            padding: 10px 14px 10px 36px;
            border: 1.5px solid var(--border-2);
            border-radius: var(--radius);
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            background: var(--surface-2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") 10px center no-repeat;
        }
        .product-search input:focus { outline:none; border-color:var(--primary); background-color:var(--surface); }
        .product-search .dropdown {
            position: absolute;
            top: 100%; left: 0; right: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            max-height: 260px;
            overflow-y: auto;
            z-index: 50;
            display: none;
        }
        .product-search .dropdown.show { display: block; }
        .product-option {
            padding: 10px 14px;
            cursor: pointer;
            transition: background 0.1s;
            border-bottom: 1px solid #f3f4f6;
        }
        .product-option:hover { background: var(--primary-light); }
        .product-option:last-child { border-bottom: none; }
        .po-name { font-size: 0.86rem; font-weight: 600; color: var(--text-primary); }
        .po-meta { font-size: 0.75rem; color: var(--text-secondary); margin-top: 1px; }
        .po-price { font-size: 0.86rem; font-weight: 700; color: var(--primary); float:right; }
        .po-stock-low { color: var(--danger); font-size:0.72rem; }

        .pos-right-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pos-right-header h3 { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.01em;
        }
        .btn-checkout:hover { filter: brightness(1.05); }
        .btn-checkout:disabled { background: #9ca3af; cursor: not-allowed; }

        .empty-cart {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            padding: 30px;
            text-align: center;
        }
        .empty-cart i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; }
        .empty-cart p { font-size: 0.86rem; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content" style="overflow:hidden;">

    <div class="topbar">
        <div class="topbar-title">
            <i class="fas fa-cash-register" style="color:var(--primary);"></i>
            Point of Sale
        </div>
        <div class="topbar-actions">
            <span style="font-size:0.8rem; color:var(--text-secondary);">
                <i class="fas fa-clock"></i> <?= date('h:i A') ?>
            </span>
        </div>
    </div>

    <form action="process_bill.php" method="POST" id="billForm">

    <div class="pos-container">

        <!-- LEFT: Customer + Product Selector -->
        <div class="pos-left">

            <!-- Customer Info -->
            <div class="section-card">
                <div class="section-card-header">
                    <i class="fas fa-user"></i> Customer Information
                </div>
                <div class="section-card-body">
                    <div class="form-row form-row-3">
                        <div class="form-group">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" name="c_name" class="form-control" 
                                   placeholder="e.g. Rahul Sharma" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="c_phone" class="form-control" 
                                   placeholder="10-digit number" pattern="[0-9]{10}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email <span style="font-weight:400; text-transform:none;">(optional)</span></label>
                            <input type="email" name="c_email" class="form-control" 
                                   placeholder="rahul@example.com">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Products -->
            <div class="section-card">
                <div class="section-card-header">
                    <i class="fas fa-shopping-cart"></i> Add Products to Cart
                </div>
                <div class="section-card-body">
                    <div class="product-search">
                        <input type="text" id="productSearchInput" placeholder="Search product name..." autocomplete="off">
                        <div class="dropdown" id="productDropdown">
                            <!-- populated by JS -->
                        </div>
                    </div>
                    <div style="font-size:0.78rem; color:var(--text-secondary);">
                        <i class="fas fa-info-circle"></i> 
                        Search and click a product to add it to the cart →
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT: Cart -->
        <div class="pos-right">
            <div class="pos-right-header">
                <h3><i class="fas fa-shopping-bag" style="color:var(--primary); margin-right:6px;"></i>Cart</h3>
                <span class="badge badge-blue" id="cartCount">0 items</span>
            </div>

            <div id="cartItems" class="cart-items">
                <div class="empty-cart" id="emptyCart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>No items yet.<br>Search and add products.</p>
                </div>
            </div>

            <div class="cart-footer">
                <div class="cart-summary">
                    <div class="cart-row">
                        <span class="cr-label">Subtotal</span>
                        <span class="cr-val" id="subtotalDisplay">₹0.00</span>
                    </div>
                    <div class="cart-total-row">
                        <span class="total-label">Total</span>
                        <span class="total-val" id="totalDisplay">₹0.00</span>
                    </div>
                </div>
                <button type="submit" name="submit_bill" class="btn-checkout" id="checkoutBtn" disabled>
                    <i class="fas fa-check-circle"></i> Generate Invoice
                </button>
            </div>

        </div>

    </div>

    <!-- Hidden inputs for cart items (submitted via form) -->
    <div id="hiddenInputs"></div>

    </form>

</div>

<script>
// Product data from PHP
const products = [
    <?php
    $result = $conn->query("SELECT * FROM products WHERE shop_id='$shop_id' AND status='active' AND stock_qty > 0 ORDER BY name ASC");
    while ($row = $result->fetch_assoc()) {
        echo "{id:" . $row['id'] . ", name:'" . addslashes($row['name']) . "', price:" . $row['price'] . ", stock:" . $row['stock_qty'] . "},\n";
    }
    ?>
];

let cart = {}; // { product_id: { name, price, qty, stock } }

// ── Search ──
const searchInput = document.getElementById('productSearchInput');
const dropdown    = document.getElementById('productDropdown');

searchInput.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if (!q) { dropdown.classList.remove('show'); return; }

    const matches = products.filter(p => p.name.toLowerCase().includes(q));
    if (!matches.length) {
        dropdown.innerHTML = `<div style="padding:14px; color:var(--text-muted); font-size:0.84rem;">No products found</div>`;
    } else {
        dropdown.innerHTML = matches.map(p => `
            <div class="product-option" onclick="addToCart(${p.id})">
                <span class="po-price">₹${p.price.toFixed(2)}</span>
                <div class="po-name">${p.name}</div>
                <div class="po-meta">
                    Stock: ${p.stock} units
                    ${p.stock <= 5 ? '<span class="po-stock-low">⚠ Low</span>' : ''}
                </div>
            </div>
        `).join('');
    }
    dropdown.classList.add('show');
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.product-search')) dropdown.classList.remove('show');
});

// ── Cart Logic ──
function addToCart(id) {
    const p = products.find(x => x.id === id);
    if (!p) return;
    dropdown.classList.remove('show');
    searchInput.value = '';

    if (cart[id]) {
        if (cart[id].qty < p.stock) cart[id].qty++;
    } else {
        cart[id] = { name: p.name, price: p.price, qty: 1, stock: p.stock };
    }
    renderCart();
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty = Math.max(1, Math.min(cart[id].stock, cart[id].qty + delta));
    if (cart[id].qty <= 0) { delete cart[id]; }
    renderCart();
}

function setQty(id, val) {
    if (!cart[id]) return;
    const q = parseInt(val) || 1;
    cart[id].qty = Math.max(1, Math.min(cart[id].stock, q));
    renderCart();
}

function removeFromCart(id) {
    delete cart[id];
    renderCart();
}

function renderCart() {
    const ids = Object.keys(cart);
    const cartDiv = document.getElementById('cartItems');
    const emptyDiv = document.getElementById('emptyCart');
    const countBadge = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const hiddenDiv = document.getElementById('hiddenInputs');

    if (!ids.length) {
        cartDiv.innerHTML = '';
        cartDiv.appendChild(emptyDiv);
        emptyDiv.style.display = 'flex';
        document.getElementById('totalDisplay').textContent = '₹0.00';
        document.getElementById('subtotalDisplay').textContent = '₹0.00';
        countBadge.textContent = '0 items';
        checkoutBtn.disabled = true;
        hiddenDiv.innerHTML = '';
        return;
    }

    emptyDiv.style.display = 'none';
    let total = 0;
    let hiddenHTML = '';
    let itemsHTML = '';

    ids.forEach(id => {
        const item = cart[id];
        const lineTotal = item.price * item.qty;
        total += lineTotal;
        itemsHTML += `
            <div class="cart-item">
                <div style="flex:1; min-width:0;">
                    <div class="ci-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.name}</div>
                    <div class="ci-price">₹${item.price.toFixed(2)} each</div>
                </div>
                <div class="qty-control">
                    <button type="button" class="qty-btn" onclick="changeQty(${id},-1)">−</button>
                    <input class="qty-input" type="number" value="${item.qty}" min="1" max="${item.stock}" 
                           onchange="setQty(${id}, this.value)">
                    <button type="button" class="qty-btn" onclick="changeQty(${id},1)">+</button>
                </div>
                <div class="ci-total">₹${lineTotal.toFixed(2)}</div>
                <button type="button" class="ci-remove" onclick="removeFromCart(${id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        hiddenHTML += `<input type="hidden" name="product_id[]" value="${id}">
                       <input type="hidden" name="qty[]" value="${item.qty}">`;
    });

    cartDiv.innerHTML = itemsHTML;
    hiddenDiv.innerHTML = hiddenHTML;
    document.getElementById('totalDisplay').textContent = '₹' + total.toFixed(2);
    document.getElementById('subtotalDisplay').textContent = '₹' + total.toFixed(2);
    countBadge.textContent = ids.length + ' item' + (ids.length != 1 ? 's' : '');
    checkoutBtn.disabled = false;
}
</script>

</body>
</html>