<?php
session_start();
require 'db.php';
 
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
 
    if (!$name) $errors[] = "Name is required.";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if (!$password) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
 
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: set_availability.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign Up</title>
<style>
  body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; }
  .container {
    max-width: 400px; margin: 50px auto; background: white; padding: 30px;
    border-radius: 8px; box-shadow: 0 6px 15px rgba(0,0,0,0.1);
  }
  h2 { text-align: center; color: #1976d2; margin-bottom: 20px; }
  input[type=text], input[type=email], input[type=password] {
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
  <h2>Create Account</h2>
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
    <input type="text" name="name" placeholder="Your Name" value="<?=htmlspecialchars($_POST['name'] ?? '')?>" required>
    <input type="email" name="email" placeholder="Email Address" value="<?=htmlspecialchars($_POST['email'] ?? '')?>" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Sign Up</button>
  </form>
  <p style="text-align:center; margin-top:15px;">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
 
