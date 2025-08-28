<?php
session_start();
require 'db.php';
 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
$daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$errors = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmtDel = $pdo->prepare("DELETE FROM availability WHERE user_id = ?");
    $stmtDel->execute([$user_id]);
 
    foreach ($daysOfWeek as $day) {
        if (isset($_POST[$day . '_start'], $_POST[$day . '_end']) &&
            $_POST[$day . '_start'] !== '' && $_POST[$day . '_end'] !== '') {
 
            $start = $_POST[$day . '_start'];
            $end = $_POST[$day . '_end'];
 
            if ($start >= $end) {
                $errors[] = "$day: Start time must be before end time.";
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO availability (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                $stmtIns->execute([$user_id, $day, $start, $end]);
            }
        }
    }
 
    if (empty($errors)) {
        $success = "Availability updated successfully.";
    }
}
 
$stmt = $pdo->prepare("SELECT * FROM availability WHERE user_id = ?");
$stmt->execute([$user_id]);
$availabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
$availabilityMap = [];
foreach ($availabilities as $a) {
    $availabilityMap[$a['day_of_week']] = $a;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Set Availability</title>
<style>
  body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; }
  .container {
    max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
  }
  h2 { color: #1976d2; text-align: center; margin-bottom: 25px; }
  .errors { color: #d32f2f; margin-bottom: 15px; }
  .success { color: #388e3c; margin-bottom: 15px; text-align: center; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 12px 10px; text-align: center; border-bottom: 1px solid #ddd; }
  th { background: #1976d2; color: white; }
  input[type=time] {
    width: 120px;
    padding: 7px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  button {
    margin-top: 25px;
    width: 100%;
    padding: 12px;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
  }
  button:hover {
    background: #155a9a;
  }
  nav {
    margin-top: 20px;
    text-align: center;
  }
  nav a {
    color: #1976d2;
    text-decoration: none;
    font-weight: 600;
    margin: 0 15px;
  }
  nav a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
<div class="container">
  <h2>Set Your Weekly Availability</h2>
  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?=htmlspecialchars($err)?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php elseif ($success): ?>
    <div class="success"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
 
  <form method="POST">
    <table>
      <thead>
        <tr><th>Day</th><th>Start Time</th><th>End Time</th></tr>
      </thead>
      <tbody>
        <?php foreach ($daysOfWeek as $day): ?>
          <tr>
            <td><?=htmlspecialchars($day)?></td>
            <td>
              <input type="time" name="<?=$day?>_start" value="<?=htmlspecialchars($availabilityMap[$day]['start_time'] ?? '')?>">
            </td>
            <td>
              <input type="time" name="<?=$day?>_end" value="<?=htmlspecialchars($availabilityMap[$day]['end_time'] ?? '')?>">
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="submit">Save Availability</button>
  </form>
 
  <nav>
    <a href="dashboard.php">Go to Dashboard</a> |
    <a href="logout.php">Logout</a>
  </nav>
</div>
</body>
</html>
 
