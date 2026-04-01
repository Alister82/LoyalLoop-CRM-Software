<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
$cur = basename($_SERVER['PHP_SELF']);
$owner = isset($_SESSION['owner_name']) ? $_SESSION['owner_name'] : 'Owner';
$shop  = isset($_SESSION['shop_name'])  ? $_SESSION['shop_name']  : 'LoyalLoop';
$initials = strtoupper(substr($owner, 0, 1));
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-icon">⚡</div>
        <div class="logo-text">
            <div class="logo-name"><?= htmlspecialchars($shop) ?></div>
            <div class="logo-sub">LoyalLoop CRM</div>
        </div>
    </div>

    <!-- User -->
    <div class="sidebar-user">
        <div class="avatar"><?= $initials ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($owner) ?></div>
            <div class="user-role">Store Owner</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <div class="nav-section-label">Main</div>

        <a href="index.php" class="<?= $cur == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie nav-icon"></i>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="billing.php" class="<?= $cur == 'billing.php' ? 'active' : '' ?>">
            <i class="fas fa-cash-register nav-icon"></i>
            <span class="nav-label">POS / Billing</span>
        </a>
        <a href="customers.php" class="<?= $cur == 'customers.php' ? 'active' : '' ?>">
            <i class="fas fa-users nav-icon"></i>
            <span class="nav-label">Customers & CRM</span>
        </a>
        <a href="inventory.php" class="<?= $cur == 'inventory.php' ? 'active' : '' ?>">
            <i class="fas fa-boxes nav-icon"></i>
            <span class="nav-label">Inventory</span>
        </a>

        <div class="nav-section-label">Smart Tools</div>

        <a href="suppliers.php" class="<?= $cur == 'suppliers.php' ? 'active' : '' ?>">
            <i class="fas fa-industry nav-icon"></i>
            <span class="nav-label">Suppliers</span>
        </a>
        <a href="replenishment.php" class="<?= $cur == 'replenishment.php' ? 'active' : '' ?>">
            <i class="fas fa-brain nav-icon"></i>
            <span class="nav-label">Replenishment AI</span>
            <?php if ($cur != 'replenishment.php'): ?>
                <span class="nav-badge">AI</span>
            <?php endif; ?>
        </a>

    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt" style="width:18px; text-align:center; opacity:0.7;"></i>
            Logout
        </a>
    </div>

</div>