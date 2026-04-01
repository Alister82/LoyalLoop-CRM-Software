<?php
// Force the server to show us the error message
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Database Connection...</h1>";

// Try to include the connection file
require 'db_connect.php';

echo "<h3>✅ SUCCESS! Database connected.</h3>";
echo "If you see this, the database file is correct.";
?>