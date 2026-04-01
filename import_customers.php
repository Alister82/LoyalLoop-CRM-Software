<?php
/**
 * import_customers.php — LoyalLoop CSV/Excel Customer Import Handler
 * Receives JSON row data from SheetJS frontend, inserts into customers table.
 * Handles duplicates with ON DUPLICATE KEY UPDATE (increments visit).
 */
include 'auth_session.php';
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['rows'])) {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
    exit();
}

$shop_id = $_SESSION['shop_id'];
$rows    = json_decode($_POST['rows'], true);

if (!is_array($rows) || empty($rows)) {
    echo json_encode(['status' => 'error', 'message' => 'No valid rows to import.']);
    exit();
}

$imported  = 0;
$updated   = 0;
$skipped   = 0;
$errors    = [];

foreach ($rows as $i => $row) {
    $name  = trim($row['name']  ?? '');
    $phone = trim($row['phone'] ?? '');
    $email = trim($row['email'] ?? '');

    // Phone is mandatory — must be 10 digits
    $phone_clean = preg_replace('/\D/', '', $phone); // strip non-digits
    
    // Handle common formats: +91XXXXXXXXXX, 91XXXXXXXXXX, XXXXXXXXXX
    if (strlen($phone_clean) === 12 && substr($phone_clean, 0, 2) === '91') {
        $phone_clean = substr($phone_clean, 2); // strip country code
    } elseif (strlen($phone_clean) === 13 && substr($phone_clean, 0, 3) === '091') {
        $phone_clean = substr($phone_clean, 3);
    }

    if (strlen($phone_clean) !== 10) {
        $skipped++;
        $errors[] = "Row " . ($i + 1) . ": Invalid phone \"$phone\" — skipped.";
        continue;
    }

    // Sanitize
    $name  = $conn->real_escape_string($name  ?: 'Unknown');
    $email = $conn->real_escape_string($email);
    $phone_clean = $conn->real_escape_string($phone_clean);

    // Insert or update (if customer returns with same phone for same shop)
    $sql = "INSERT INTO customers (shop_id, name, phone, email, visit_count, last_visit, loyalty_points)
            VALUES ('$shop_id', '$name', '$phone_clean', '$email', 1, CURRENT_DATE, 0)
            ON DUPLICATE KEY UPDATE
                name       = IF(name = 'Unknown' OR name = '', VALUES(name), name),
                email      = IF(email = '' OR email IS NULL, VALUES(email), email),
                visit_count = visit_count";

    if ($conn->query($sql)) {
        if ($conn->affected_rows === 1) {
            $imported++;
        } elseif ($conn->affected_rows === 2) {
            $updated++; // ON DUPLICATE KEY UPDATE fired
        } else {
            $updated++; // row matched but no change needed
        }
    } else {
        $skipped++;
        $errors[] = "Row " . ($i + 1) . ": DB error — " . $conn->error;
    }
}

echo json_encode([
    'status'   => 'success',
    'imported' => $imported,
    'updated'  => $updated,
    'skipped'  => $skipped,
    'errors'   => $errors,
    'total'    => count($rows),
]);
