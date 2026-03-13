<?php
/**
 * COSMIC C2 LOGIN PAGE
 * Authenticates users against the c2 database.
 */
require_once __DIR__ . '/includes/security_functions.php';
require_once dirname(__DIR__) . '/system/modules/C2Engine.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['c2_user'])) {
    header('Location: c2-dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $c2 = new C2Engine();
    $user = $c2->authenticate($username, $password);
    
    if ($user) {
        $_SESSION['c2_user'] = $user['username'];
        $_SESSION['c2_role'] = $user['role'];
        $_SESSION['c2_user_id'] = $user['id'];
        header('Location: c2-dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>COSMIC C2 Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <style>
        .login-container { max-width:400px; margin:100px auto; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="login-container">
        <div class="glass-panel">
            <h1 style="text-align:center;">🌀 COSMIC C2</h1>
            <h2 style="text-align:center;">Authentication</h2>
            <?php if ($error): ?><div class="error-box"><?php echo $error; ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">LOGIN</button>
            </form>
            <div class="footer" style="text-align:center; margin-top:20px;">
                Default: admin / admin (change after first login)
            </div>
        </div>
    </div>
</body>
</html>
