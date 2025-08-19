<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /HTML_PHP/Login.php");
    exit();
}

include('db.php');

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Date filter
$selected_date = $_GET['date'] ?? date('Y-m-d');

$history = [];

// If caretaker — can choose patient
if ($role === 'caretaker' && isset($_GET['patient_id'])) {
    $patient_id = intval($_GET['patient_id']);
} elseif ($role === 'patient') {
    $patient_id = $user_id;
} else {
    $patient_id = null;
}

if ($patient_id) {
    $stmt = $conn->prepare("
        SELECT mh.id, m.name AS medicine_name, mh.status, mh.status_time 
        FROM medicines_history mh
        JOIN medicines m ON mh.medicine_id = m.id
        WHERE mh.patient_id = ? AND DATE(mh.status_time) = ?
        ORDER BY mh.status_time DESC
    ");
    $stmt->bind_param("is", $patient_id, $selected_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

// Fetch patient list for caretakers
$patients = [];
if ($role === 'caretaker') {
    $pstmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'patient'");
    $pstmt->execute();
    $patients = $pstmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $pstmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Medicine History</title>
    <link rel="stylesheet" href="/CSS/Dashboard.css" />
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: center; }
        th { background-color: #f4f4f4; }
        .status-taken { color: green; font-weight: bold; }
        .status-missed { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Medicine History</h2>

    <!-- Date Filter -->
    <form method="GET">
        <label>Select Date:</label>
        <input type="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
        <?php if ($role === 'caretaker') : ?>
            <label>Patient:</label>
            <select name="patient_id">
                <?php foreach ($patients as $p) : ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($patient_id == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <button type="submit">View</button>
    </form>

    <?php if ($patient_id && !empty($history)) : ?>
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                        <td class="<?php echo strtolower($row['status']) === 'taken' ? 'status-taken' : 'status-missed'; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </td>
                        <td><?php echo date("h:i A", strtotime($row['status_time'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($patient_id) : ?>
        <p>No history found for this date.</p>
    <?php endif; ?>
</body>
</html>
