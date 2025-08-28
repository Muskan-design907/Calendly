<?php
session_start();
require 'db.php';
 
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
 
if (!$user_id) {
    echo "Invalid booking link.";
    exit;
}
 
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
 
if (!$user) {
    echo "User not found.";
    exit;
}
 
$stmt = $pdo->prepare("SELECT * FROM availability WHERE user_id = ? ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$stmt->execute([$user_id]);
$availabilities = $stmt->fetchAll();
 
$errors = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = trim($_POST['visitor_name'] ?? '');
    $visitor_email = trim($_POST['visitor_email'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
 
    if (!$visitor_name) $errors[] = "Your name is required.";
    if (!$visitor_email || !filter_var($visitor_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (!$date) $errors[] = "Date is required.";
    if (!$time) $errors[] = "Time slot is required.";
 
    $today = date('Y-m-d');
    if ($date < $today) $errors[] = "Date cannot be in the past.";
 
    $dayName = date('l', strtotime($date));
    $validSlot = false;
    foreach ($availabilities as $avail) {
        if ($avail['day_of_week'] === $dayName) {
            if ($time >= substr($avail['start_time'], 0, 5) && $time < substr($avail['end_time'], 0, 5)) {
                $validSlot = true;
                break;
            }
        }
    }
    if (!$validSlot) $errors[] = "Selected time slot is not available.";
 
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE user_id = ? AND date = ? AND time = ? AND status = 'booked'");
    $stmt->execute([$user_id, $date, $time]);
    if ($stmt->fetch()) {
        $errors[] = "This time slot has already been booked.";
    }
 
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, visitor_name, visitor_email, date, time, status) VALUES (?, ?, ?, ?, ?, 'booked')");
        $stmt->execute([$user_id, $visitor_name, $visitor_email, $date, $time]);
 
        // Send confirmation emails
 
        $toUser = $user['email'];
        $subjectUser = "New Appointment Booked";
        $messageUser = "Hello " . $user['name'] . ",\n\n" .
            "You have a new appointment booked:\n" .
            "Visitor: $visitor_name\n" .
            "Email: $visitor_email\n" .
            "Date: $date\n" .
            "Time: $time\n\n" .
            "Please prepare accordingly.\n\nRegards,\nYour Scheduling App";
        $headersUser = "From: no-reply@yoursite.com";
 
        mail($toUser, $subjectUser, $messageUser, $headersUser);
 
        $toVisitor = $visitor_email;
        $subjectVisitor = "Appointment Confirmation";
        $messageVisitor = "Hello $visitor_name,\n\n" .
            "Your appointment with " . $user['name'] . " is confirmed:\n" .
            "Date: $date\n" .
            "Time: $time\n\n" .
            "Thank you for scheduling.\n\nRegards,\nYour Scheduling App";
        $headersVisitor = "From: no-reply@yoursite.com";
 
        mail($toVisitor, $subjectVisitor, $messageVisitor, $headersVisitor);
 
        $success = "Your appointment is booked successfully for $date at $time. Confirmation emails sent.";
    }
}
 
function generateTimeSlots($start, $end, $interval = 30) {
    $start = strtotime($start);
    $end = strtotime($end);
    $slots = [];
    for ($time = $start; $time < $end; $time += $interval * 60) {
        $slots[] = date('H:i', $time);
    }
    return $slots;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Book a Meeting with <?=htmlspecialchars($user['name'])?></title>
<style>
  body { font-family: Arial, sans-serif; background:#f7f9fc; margin:0; }
  .container {
    max-width: 480px;
    margin: 50px auto;
    background: white;
    padding: 30px 25px;
    border-radius: 8px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
  }
  h2 {
    color: #1976d2;
    margin-bottom: 20px;
    text-align: center;
  }
  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
  }
  input[type=text], input[type=email], select, input[type=date] {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 15px;
  }
  select {
    cursor: pointer;
  }
  button {
    width: 100%;
    padding: 12px;
    background: #1976d2;
    color: white;
    font-weight: 700;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background: #155a9a;
  }
  .errors {
    margin-bottom: 15px;
    color: #d32f2f;
    font-weight: 600;
  }
  .success {
    margin-bottom: 15px;
    color: #388e3c;
    font-weight: 600;
    text-align: center;
  }
</style>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const availabilities = <?= json_encode($availabilities); ?>;
    const dateInput = document.getElementById('date');
    const timeSelect = document.getElementById('time');
 
    function updateTimeSlots() {
      timeSelect.innerHTML = '<option value="">Select time slot</option>';
      const selectedDate = dateInput.value;
      if (!selectedDate) return;
 
      const dayName = new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long' });
 
      const dayAvail = availabilities.find(a => a.day_of_week === dayName);
      if (!dayAvail) {
        const option = document.createElement('option');
        option.textContent = 'No availability on this day';
        option.disabled = true;
        timeSelect.appendChild(option);
        return;
      }
 
      function generateTimeSlots(start, end, interval = 30) {
        const slots = [];
        let startTime = new Date('1970-01-01T' + start + ':00');
        const endTime = new Date('1970-01-01T' + end + ':00');
 
        while (startTime < endTime) {
          slots.push(startTime.toTimeString().slice(0,5));
          startTime = new Date(startTime.getTime() + interval * 60000);
        }
        return slots;
      }
 
      const slots = generateTimeSlots(dayAvail.start_time.slice(0,5), dayAvail.end_time.slice(0,5));
      if (slots.length === 0) {
        const option = document.createElement('option');
        option.textContent = 'No available time slots';
        option.disabled = true;
        timeSelect.appendChild(option);
        return;
      }
 
      slots.forEach(slot => {
        const option = document.createElement('option');
        option.value = slot;
        option.textContent = slot;
        timeSelect.appendChild(option);
      });
    }
 
    dateInput.addEventListener('change', updateTimeSlots);
  });
</script>
</head>
<body>
<div class="container">
  <h2>Book a Meeting with <?=htmlspecialchars($user['name'])?></h2>
 
  <?php if ($errors): ?>
    <div class="errors">
      <ul>
        <?php foreach($errors as $err): ?>
          <li><?=htmlspecialchars($err)?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php elseif ($success): ?>
    <div class="success"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
 
  <form method="POST" action="booking.php?user_id=<?=htmlspecialchars($user_id)?>" novalidate>
    <label for="visitor_name">Your Name</label>
    <input type="text" id="visitor_name" name="visitor_name" required value="<?=htmlspecialchars($_POST['visitor_name'] ?? '')?>" />
 
    <label for="visitor_email">Your Email</label>
    <input type="email" id="visitor_email" name="visitor_email" required value="<?=htmlspecialchars($_POST['visitor_email'] ?? '')?>" />
 
    <label for="date">Select Date</label>
    <input type="date" id="date" name="date" min="<?=date('Y-m-d')?>" required value="<?=htmlspecialchars($_POST['date'] ?? '')?>" />
 
    <label for="time">Select Time Slot</label>
    <select id="time" name="time" required>
      <option value="">Select date first</option>
    </select>
 
    <button type="submit">Book Appointment</button>
  </form>
</div>
</body>
</html>
