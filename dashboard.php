<?php
session_start();
require 'db.php';
 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
 
$user_id = $_SESSION['user_id'];
$message = "";
 
// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->execute([$del_id, $user_id]);
    $message = "Appointment deleted successfully.";
}
 
// Handle Edit
if (isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $visitor_name = trim($_POST['visitor_name']);
    $visitor_email = trim($_POST['visitor_email']);
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);
 
    if ($visitor_name && filter_var($visitor_email, FILTER_VALIDATE_EMAIL) && $date && $time) {
        $stmt = $pdo->prepare("UPDATE appointments SET visitor_name=?, visitor_email=?, date=?, time=? WHERE id=? AND user_id=?");
        $stmt->execute([$visitor_name, $visitor_email, $date, $time, $edit_id, $user_id]);
        $message = "Appointment updated successfully.";
    } else {
        $message = "Please provide valid data for all fields.";
    }
}
 
// Fetch appointments
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY date ASC, time ASC");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Dashboard</title>
<style>
    body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; }
    .container {
        max-width: 1000px;
        margin: 30px auto;
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    h1 { color: #1976d2; text-align: center; margin-bottom: 20px; }
    .message { text-align: center; font-weight: 600; color: green; margin-bottom: 15px; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th, td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    th { background: #1976d2; color: white; }
    tr:hover { background: #f1f1f1; }
    .btn {
        padding: 6px 10px;
        border: none;
        border-radius: 4px;
        color: white;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
    }
    .btn-edit { background: #fbc02d; }
    .btn-delete { background: #d32f2f; }
    .btn-save { background: #388e3c; }
    .btn-cancel { background: #757575; }
    form.inline-form { display: inline-block; margin: 0; }
    input[type="text"], input[type="email"], input[type="date"], input[type="time"] {
        padding: 5px;
        width: 100%;
        box-sizing: border-box;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
</style>
</head>
<body>
<div class="container">
    <h1>Your Scheduled Appointments</h1>
    <?php if ($message): ?>
        <div class="message"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>
 
    <?php if (count($appointments) === 0): ?>
        <p style="text-align:center;">No appointments found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Visitor Name</th>
                <th>Email</th>
                <th>Date</th>
                <th>Time</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($appointments as $appt): ?>
                <tr>
                    <form method="POST" class="inline-form">
                        <td><input type="text" name="visitor_name" value="<?=htmlspecialchars($appt['visitor_name'])?>"></td>
                        <td><input type="email" name="visitor_email" value="<?=htmlspecialchars($appt['visitor_email'])?>"></td>
                        <td><input type="date" name="date" value="<?=htmlspecialchars($appt['date'])?>"></td>
                        <td><input type="time" name="time" value="<?=htmlspecialchars($appt['time'])?>"></td>
                        <td>
                            <input type="hidden" name="edit_id" value="<?=$appt['id']?>">
                            <button type="submit" class="btn btn-save">Save</button>
                            <a href="?delete=<?=$appt['id']?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this meeting?')">Delete</a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
 
    <p style="text-align:center; margin-top:20px;">
        <a href="set_availability.php" class="btn btn-edit">Set Availability</a>
        <a href="logout.php" class="btn btn-cancel">Logout</a>
    </p>
</div>
</body>
</html>
 
