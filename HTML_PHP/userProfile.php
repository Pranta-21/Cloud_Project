<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /HTML_PHP/Login.php");
    exit();
}

include('db.php'); // Your DB connection

$user_id = $_SESSION['user_id'];

// Fetch user data from DB
$stmt = $conn->prepare("SELECT username, email, phone, role, registered_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();

// Default profile image
$profile_img = '/images/profile.jpeg';
?>

<!DOCTYPE html>
<html>
<head>
  <title>My Profile - HealthBuddy</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" />
  <style>
    body {
      background: url('/images/sliderimg.jpg') center center/cover no-repeat;
      position: relative;
      min-height: 100vh;
    }
    body::after {
      position: fixed;
      content: "";
      top: 0; left: 0;
      height: 100%; width: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: -1;
    }
    .container-style {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 30%;
      background-color: #fff;
      box-shadow: 0px 16px 32px rgba(5, 88, 177, 0.9);
      border-radius: 10px;
      padding: 20px;
    }
    .close-btn {
      position: absolute;
      right: 20px;
      top: 20px;
      border: 2px solid #810909;
      color: #971f1f;
      font-size: 15px;
      cursor: pointer;
      border-radius: 10px;
      padding: 0 5px;
      text-decoration: none;
    }
    .heading-color {
      margin: 0;
      padding: 0;
    }
    .rowcontainer {
      display: flex;
      gap: 30px;
      align-items: center;
      margin-top: 20px;
    }
    .profileimg {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ddd;
    }
    .panel-heading {
      margin-top: 30px;
      background-color: #6fc4eb;
      padding: 10px 15px;
      font-weight: bold;
      border-radius: 4px;
    }
  </style>
</head>
<body>
  <div class="container-style">
    <h2 class="heading-color">My Profile</h2>
    <a href="/HTML_PHP/patient_dashboard.php" class="close-btn">Back</a>
    <div class="rowcontainer">
      <div>
        <img src="<?php echo $profile_img; ?>" alt="Profile Picture" class="profileimg" />
      </div>
      <div>
        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
        <p><?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
      </div>
    </div>

    <div class="panel-heading">Account Information</div>
    <div class="panel-body">
      <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
      <p><strong>Email Address:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
      <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
      <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
      <p><strong>Registered At:</strong> <?php echo htmlspecialchars($user['registered_at']); ?></p>
    </div>
  </div>
</body>
</html>
