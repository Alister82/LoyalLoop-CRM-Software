<?php
include 'auth_session.php';
include 'db_connect.php';

$shop_id = $_SESSION['shop_id'];
$product_id = $_GET['id'];

// 1. Fetch Existing Data
$sql = "SELECT * FROM products WHERE id='$product_id' AND shop_id='$shop_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Product not found or access denied.");
}
$row = $result->fetch_assoc();

// 2. Handle Update Logic
if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $expiry = $_POST['expiry'];

    $update_sql = "UPDATE products SET name='$name', price='$price', stock_qty='$stock', expiry_date='$expiry' WHERE id='$product_id' AND shop_id='$shop_id'";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('Product Updated Successfully!'); window.location='inventory.php';</script>";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Retail Pulse</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container" style="display:flex; justify-content:center; align-items:center; height:100vh;">
    <div class="card" style="width:400px; padding:30px;">
        <h2 style="margin-bottom:20px; color:#333;">Edit Product</h2>
        
        <form method="post">
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Product Name</label>
                <input type="text" name="name" value="<?php echo $row['name']; ?>" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Price (Rs.)</label>
                <input type="number" name="price" value="<?php echo $row['price']; ?>" step="0.01" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Stock Quantity</label>
                <input type="number" name="stock" value="<?php echo $row['stock_qty']; ?>" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;">
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">Expiry Date</label>
                <input type="date" name="expiry" value="<?php echo $row['expiry_date']; ?>" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px;">
            </div>

            <button type="submit" name="update_product" style="width:100%; background:#2563eb; color:white; padding:12px; border:none; border-radius:5px; font-size:1rem; cursor:pointer;">Update Product</button>
            <a href="inventory.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>