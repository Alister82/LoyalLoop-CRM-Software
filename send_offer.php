<?php
include 'auth_session.php';
include 'db_connect.php';

$shop_id = $_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'];

// 1. Fetch Expiring Items (FIXED: 45 Days & status='active')
$exp_sql = "SELECT name, price, stock_qty FROM products 
            WHERE shop_id = '$shop_id' 
            AND status = 'active' 
            AND expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY) 
            AND stock_qty > 0";
$exp_res = $conn->query($exp_sql);

$offer_items = [];
if ($exp_res->num_rows > 0) {
    while($row = $exp_res->fetch_assoc()) {
        // "Professional" Item formatting
        $discount_price = ceil($row['price'] * 0.8);
        $offer_items[] = "  • " . $row['name'] . " (Member Price: Rs. " . $discount_price . ")"; 
    }
} else {
    echo "<script>alert('No items available for this offer (Next 45 Days).'); window.location.href='index.php';</script>";
    exit();
}

// 2. Fetch Emails
$cust_sql = "SELECT email FROM customers WHERE shop_id = '$shop_id' AND email IS NOT NULL AND email != ''";
$cust_res = $conn->query($cust_sql);

$emails = [];
if ($cust_res->num_rows > 0) {
    while($row = $cust_res->fetch_assoc()) {
        $emails[] = $row['email'];
    }
} else {
    echo "<script>alert('No customer emails found.'); window.location.href='index.php';</script>";
    exit();
}

$recipient_list = implode(',', $emails); 
$items_list_string = implode("\n", $offer_items);

// 3. THE "ANTI-SPAM" CONTENT WRITING
$subject = "Priority Offer: Clearance Sale at $shop_name";

$body = "Dear Valued Customer,\n\n";
$body .= "We hope you are having a wonderful week.\n\n";
$body .= "We are currently refreshing our inventory at $shop_name and noticed you are eligible for a priority offer on select items expiring soon. \n\n";
$body .= "As a loyal patron, we have reserved the following stock for you at a special member rate (20% advantage):\n\n";
$body .= $items_list_string . "\n\n";
$body .= "These items are available at our store starting today. Please visit us to claim them before we open this stock to the general public.\n\n";
$body .= "Warm regards,\n\n";
$body .= "The Team at $shop_name";

// 4. Generate Link (Mailto Method)
$mailto_link = "mailto:?bcc=" . $recipient_list . 
               "&subject=" . rawurlencode($subject) . 
               "&body=" . rawurlencode($body);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Priority Notification</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background:#f8fafc; display:flex; justify-content:center; align-items:center; height:100vh;">

    <div class="card" style="width:600px; padding:40px; border-top:5px solid #2563eb;">
        
        <div style="text-align:center; margin-bottom:20px;">
            <i class="fas fa-shield-alt" style="font-size:3rem; color:#2563eb;"></i>
            <h2 style="color:#1e293b; margin-top:10px;">Professional Email Generator</h2>
            <p style="color:#64748b;">Generated optimized content for <b><?php echo count($emails); ?></b> customers.</p>
        </div>

        <div style="background:#fff; border:1px solid #e2e8f0; padding:20px; border-radius:8px; margin-bottom:25px;">
            <p style="font-size:0.9rem; color:#64748b; font-weight:bold; margin-bottom:5px;">SUBJECT:</p>
            <div style="padding:10px; background:#f1f5f9; border-radius:4px; margin-bottom:15px; font-family:monospace;">
                <?php echo $subject; ?>
            </div>

            <p style="font-size:0.9rem; color:#64748b; font-weight:bold; margin-bottom:5px;">BODY:</p>
            <div style="padding:10px; background:#f1f5f9; border-radius:4px; font-family:monospace; white-space: pre-line; max-height:200px; overflow-y:auto;">
                <?php echo $body; ?>
            </div>
        </div>

        <div style="text-align:center;">
            <a href="<?php echo $mailto_link; ?>" 
               style="background-color: #2563eb; color: white; padding: 15px 40px; text-decoration: none; font-size: 1.1rem; font-weight: bold; border-radius: 5px; display:inline-block; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);">
                <i class="fas fa-paper-plane"></i> Open Email App & Send
            </a>
            
            <br><br>
            <a href="index.php" style="color:#64748b; font-weight:bold;">Cancel</a>
        </div>
    </div>

</body>
</html>