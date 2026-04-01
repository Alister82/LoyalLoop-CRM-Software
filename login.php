<?php
session_start();
include 'db_connect.php';

$error = '';
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $pass = $_POST['password'];

    $sql = "SELECT * FROM shops WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verify Password
        if (password_verify($pass, $row['password'])) {
            // SUCCESS: Set Session Variables
            $_SESSION['shop_id'] = $row['id'];
            $_SESSION['shop_name'] = $row['shop_name'];
            $_SESSION['owner_name'] = $row['owner_name'];
            
            header("Location: index.php"); // Go to Dashboard
            exit();
        } else {
            $error = "Invalid Password!";
        }
    } else {
        $error = "No account found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – LoyalLoop CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --surface: #ffffff;
            --bg: #f8fafc;
            --text: #1e293b;
            --text-muted: #64748b;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 40%),
                              radial-gradient(circle at bottom left, #dbeafe 0%, #f8fafc 40%);
        }
        .auth-container {
            display: flex;
            width: 900px;
            max-width: 95vw;
            height: 550px;
            background: var(--surface);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .auth-banner {
            flex: 1;
            background: linear-gradient(135deg, #0f172a, #1e1b4b);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-banner::before {
            content: '';
            position: absolute;
            top: -50px; left: -50px;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .auth-banner::after {
            content: '';
            position: absolute;
            bottom: -50px; right: -50px;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(139,92,246,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .auth-logo {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 2;
        }
        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(59,130,246,0.4);
            font-size: 1rem;
        }
        .auth-banner-text {
            position: relative;
            z-index: 2;
        }
        .auth-banner-text h2 {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0 0 15px;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }
        .auth-banner-text p {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.5;
            margin: 0;
            max-width: 85%;
        }
        
        .auth-form-wrapper {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-header h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            margin: 0 0 6px;
        }
        .auth-header p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0 0 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: var(--text);
            transition: all 0.2s;
            background: #f8fafc;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
        }
        
        .btn-auth {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-auth:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.25);
        }
        
        .auth-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.88rem;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            transition: color 0.15s;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        @media (max-width: 768px) {
            .auth-container { flex-direction: column; height: auto; border-radius: 16px; }
            .auth-banner { padding: 30px; }
            .auth-form-wrapper { padding: 30px; }
            .auth-banner-text p { display: none; }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-banner">
        <div class="auth-logo">
            <div class="logo-icon"><i class="fas fa-bolt"></i></div>
            LoyalLoop
        </div>
        <div class="auth-banner-text">
            <h2>Manage your store smarter.</h2>
            <p>Welcome back to your complete intelligent dashboard for sales, inventory, and automated AI replenishment.</p>
        </div>
    </div>
    
    <div class="auth-form-wrapper">
        <div class="auth-header">
            <h3>Sign in to your account</h3>
            <p>Enter your details below to access your dashboard.</p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration successful! Please log in.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="shop@example.com" required autofocus>
            </div>
            
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label class="form-label" style="margin-bottom:0;">Password</label>
                    <!-- <a href="#" style="font-size:0.75rem; color:var(--primary); font-weight:600; text-decoration:none;">Forgot password?</a> -->
                </div>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" name="login" class="btn-auth">Sign In</button>
        </form>
        
        <div class="auth-footer">
            Don't have an account? <a href="register.php">Create a Shop Account</a>
        </div>
    </div>
</div>

</body>
</html>