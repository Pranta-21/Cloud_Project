<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require 'db.php';

$patient_id  = (int) $_SESSION['user_id'];
$medicine_id = isset($_POST['medicine_id']) ? (int) $_POST['medicine_id'] : 0;
$status      = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$medicine_id || !in_array($status, ['Taken', 'Missed'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

/* Optional (recommended): set your app TZ so CURDATE()/NOW() align with PHP
   date_default_timezone_set('UTC'); // or your region
*/

// Verify medicine belongs to this patient
$stmt = $conn->prepare('SELECT id FROM medicines WHERE id = ? AND patient_id = ?');
$stmt->bind_param('ii', $medicine_id, $patient_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Medicine not found']);
    exit();
}
$stmt->close();

// Enforce max 2 TAKEN per day
if ($status === 'Taken') {
    $stmt = $conn->prepare('
        SELECT COUNT(*)
        FROM medicines_history
        WHERE medicine_id = ? AND patient_id = ? AND status = "Taken" AND DATE(status_time) = CURDATE()
    ');
    $stmt->bind_param('ii', $medicine_id, $patient_id);
    $stmt->execute();
    $stmt->bind_result($taken_count_today);
    $stmt->fetch();
    $stmt->close();

    if ($taken_count_today >= 2) {
        echo json_encode(['success' => false, 'message' => 'Already marked Taken twice today.']);
        exit();
    }
}

// Insert history record
$stmt = $conn->prepare('
    INSERT INTO medicines_history (medicine_id, patient_id, status, status_time)
    VALUES (?, ?, ?, NOW())
');
$stmt->bind_param('iis', $medicine_id, $patient_id, $status);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'DB error (history)']);
    exit();
}
$stmt->close();

// Update latest status on medicines (for quick display)
$stmt = $conn->prepare('UPDATE medicines SET status = ?, status_time = NOW() WHERE id = ? AND patient_id = ?');
$stmt->bind_param('sii', $status, $medicine_id, $patient_id);
$stmt->execute();
$stmt->close();

// Get fresh Taken count for today
$stmt = $conn->prepare('
    SELECT COUNT(*)
    FROM medicines_history
    WHERE medicine_id = ? AND patient_id = ? AND status = "Taken" AND DATE(status_time) = CURDATE()
');
$stmt->bind_param('ii', $medicine_id, $patient_id);
$stmt->execute();
$stmt->bind_result($new_taken_count);
$stmt->fetch();
$stmt->close();

// Get last status + time (from medicines)
$stmt = $conn->prepare('SELECT status, status_time FROM medicines WHERE id = ?');
$stmt->bind_param('i', $medicine_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$update_time = $row && !empty($row['status_time']) ? date('h:i A', strtotime($row['status_time'])) : '';

echo json_encode([
    'success'          => true,
    'new_status'       => $row ? $row['status'] : $status,
    'update_time'      => $update_time,
    'new_taken_count'  => (int)$new_taken_count
]);
