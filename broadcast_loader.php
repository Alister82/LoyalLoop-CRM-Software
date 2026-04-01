<?php
include 'db_connect.php';

// 1. Set Headers to force download
header('Content-Type: text/x-vcard; charset=utf-8');
header('Content-Disposition: attachment; filename="retail_pulse_customers.vcf"');

// 2. Fetch All Customers
$sql = "SELECT id, name, phone FROM customers";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // 3. Format Name to group them easily
        // We prefix with "RP-" so they all appear together in your contact list
        $displayName = "RP-{$row['name']}"; 
        
        // 4. Generate vCard Format
        echo "BEGIN:VCARD\r\n";
        echo "VERSION:3.0\r\n";
        echo "FN:$displayName\r\n"; // Full Name
        echo "N:;$displayName;;;\r\n";
        echo "TEL;TYPE=CELL:{$row['phone']}\r\n"; // Phone Number
        echo "END:VCARD\r\n";
    }
} else {
    echo "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:No Customers\r\nEND:VCARD";
}
exit();
?>