<?php
session_start();
require 'db.php';
 
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if (!$email || !$password) {
        $errors[] = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: set_availability.php');
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login</title>
<style>
  body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; }
  .container {
    max-width: 400px; margin: 50px auto; background: white; padding: 30px;
    border-radius: 8px; box-shadow: 0 6px 15px rgba(0,0,0,0.1);
  }
  h2 { text-align: center; color: #1976d2; margin-bottom: 20px; }
  input[type=email], input[type=password] {
    width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;
  }
  button {
    width: 100%; padding: 12px; background: #1976d2; color: white; border: none;
    border-radius: 4px; font-weight: 700; cursor: pointer;
  }
  button:hover { background: #155a9a; }
  .errors { color: #d32f2f; margin-bottom: 15px; }
</style>
</head>
<body>
<div class="container">
  <h2>Login</h2>
  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?=htmlspecialchars($err)?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
  <form method="POST">
    <input type="email" name="email" placeholder="Email Address" value="<?=htmlspecialchars($_POST['email'] ?? '')?>" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
  <p style="text-align:center; margin-top:15px;">Don't have an account? <a href="signup.php">Sign Up</a></p>
</div>
</body>
</html>
 
