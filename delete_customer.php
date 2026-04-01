<?php
include 'db_connect.php';

// Check if an ID is provided in the URL (e.g., delete_customer.php?id=5)
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // OPTIONAL: Delete the customer's sales history first 
    // (Otherwise, the database might block deletion due to Foreign Key constraints)
    $conn->query("DELETE FROM sales WHERE customer_id = $id");

    // NOW delete the customer
    $sql = "DELETE FROM customers WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Success! Redirect back to customers page
        header("Location: customers.php?msg=deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    // If someone tries to open this file without an ID, kick them back
    header("Location: customers.php");
}
?>