<?php
include 'auth_session.php'; 
include 'db_connect.php';

if (!isset($_POST['submit_bill'])) {
    header("Location: billing.php");
    exit();
}

$shop_id = $_SESSION['shop_id']; // Current Shop
$c_name = $_POST['c_name'];
$c_phone = $_POST['c_phone'];
$c_email = isset($_POST['c_email']) ? $_POST['c_email'] : ''; // Capture Email

// 1. SAVE OR UPDATE CUSTOMER (Now includes Email)
// Note: We update the email if the customer returns with a new one
$sql = "INSERT INTO customers (shop_id, name, phone, email, visit_count, last_visit) 
        VALUES ('$shop_id', '$c_name', '$c_phone', '$c_email', 1, CURRENT_DATE)
        ON DUPLICATE KEY UPDATE 
        name = VALUES(name),
        email = VALUES(email), 
        visit_count = visit_count + 1, 
        last_visit = CURRENT_DATE";

if (!$conn->query($sql)) {
    die("Error saving customer: " . $conn->error);
}

// Get Customer ID (Specific to THIS shop)
$c_id_res = $conn->query("SELECT id FROM customers WHERE phone='$c_phone' AND shop_id='$shop_id'");
$c_id = $c_id_res->fetch_assoc()['id'];

// 2. PROCESS ITEMS
$grand_total = 0;
$product_ids = $_POST['product_id'];
$quantities = $_POST['qty'];
$items_list = [];

// Calculate Total & Check Stock
for ($i = 0; $i < count($product_ids); $i++) {
    $pid = $product_ids[$i];
    $qty = $quantities[$i];

    // Ensure we only touch products from THIS shop
    $res = $conn->query("SELECT * FROM products WHERE id = $pid AND shop_id = $shop_id");
    $prod = $res->fetch_assoc();

    if ($prod['stock_qty'] < $qty) {
        die("Error: Not enough stock for {$prod['name']}");
    }
    
    $grand_total += ($prod['price'] * $qty);
    $items_list[] = ['id'=>$pid, 'name'=>$prod['name'], 'price'=>$prod['price'], 'qty'=>$qty];
}

// 3. SAVE SALE RECORD
$conn->query("INSERT INTO sales (shop_id, customer_id, total_amount) VALUES ('$shop_id', $c_id, $grand_total)");
$sale_id = $conn->insert_id;

// 4. SAVE ITEMS & DEDUCT STOCK
$wa_item_string = ""; 

foreach ($items_list as $item) {
    $conn->query("INSERT INTO sale_items (sale_id, product_id, quantity, price) 
                  VALUES ($sale_id, {$item['id']}, {$item['qty']}, {$item['price']})");
                  
    $conn->query("UPDATE products SET stock_qty = stock_qty - {$item['qty']} 
                  WHERE id = {$item['id']} AND shop_id = $shop_id");

    $line_cost = $item['price'] * $item['qty'];
    $wa_item_string .= "• {$item['name']} x {$item['qty']} = ₹$line_cost\n";
}

// 5. GENERATE WHATSAPP MESSAGE
$store_name = $_SESSION['shop_name'];
$date = date('d-M-Y h:i A');

$raw_msg  = "*INVOICE #$sale_id* ✅\n";
$raw_msg .= "-------------------------\n";
$raw_msg .= "Store: $store_name\n";
$raw_msg .= "Date: $date\n";
$raw_msg .= "Customer: $c_name\n";
$raw_msg .= "-------------------------\n";
$raw_msg .= "*ITEMS:*\n";
$raw_msg .= $wa_item_string;
$raw_msg .= "-------------------------\n";
$raw_msg .= "*TOTAL: ₹$grand_total*\n";
$raw_msg .= "-------------------------\n";
$raw_msg .= "Thank you for shopping! 🙏";

$wa_link = "https://wa.me/91$c_phone?text=" . urlencode($raw_msg);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill Success</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background:#f1f5f9; display:flex; justify-content:center; align-items:center; height:100vh;">
    
    <div class="card" style="max-width:500px; text-align:center; padding:40px;">
        <i class="fas fa-check-circle" style="font-size:4rem; color:#25D366; margin-bottom:15px;"></i>
        <h1 style="color:#333;">Bill Saved!</h1>
        <h2 style="color:#2563eb;">Total: ₹<?php echo $grand_total; ?></h2>
        
        <p style="color:#64748b;">Invoice #<?php echo $sale_id; ?> generated successfully.</p>
        
        <?php if($c_email): ?>
            <p style="color:#10b981; font-size:0.9rem;">Email saved: <?php echo $c_email; ?></p>
        <?php endif; ?>

        <a href="<?php echo $wa_link; ?>" target="_blank" style="
            display:inline-block; margin-top:20px;
            background-color: #25D366; color: white; 
            padding: 15px 30px; border-radius: 50px; 
            text-decoration: none; font-weight: bold; font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(37, 211, 102, 0.4);">
            <i class="fab fa-whatsapp"></i> Send Invoice
        </a>

        <br><br>
        <a href="billing.php" style="color:#64748b; font-weight:bold;">Create New Bill</a> 
        <span style="color:#ccc; margin:0 10px;">|</span>
        <a href="index.php" style="color:#2563eb; font-weight:bold;">Dashboard</a>
    </div>

</body>
</html>