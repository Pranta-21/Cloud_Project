<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: /HTML_PHP/Login.php");
    exit();
}

include('db.php');

$patient_id = $_SESSION['user_id'];

/* Optional (recommended) to align PHP with DB:
   date_default_timezone_set('UTC'); // or your region
*/

// Fetch today's medicines
$sql = "SELECT * FROM medicines WHERE patient_id = ? AND DATE(intake_time) = CURDATE() ORDER BY intake_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$medicines = [];

while ($row = $result->fetch_assoc()) {
    // Count how many times Taken today from history (PERSISTENT)
    $count_stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM medicines_history
        WHERE medicine_id = ?
          AND patient_id = ?
          AND status = 'Taken'
          AND DATE(status_time) = CURDATE()
    ");
    $count_stmt->bind_param("ii", $row['id'], $patient_id);
    $count_stmt->execute();
    $count_stmt->bind_result($taken_count);
    $count_stmt->fetch();
    $count_stmt->close();

    $row['taken_count'] = (int)$taken_count;

    // Latest status/time for display (kept in medicines by update_status.php)
    $row['last_status'] = !empty($row['status']) ? $row['status'] : 'Not Updated';
    $row['last_status_time'] = !empty($row['status_time']) ? $row['status_time'] : null;

    $medicines[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Patient Dashboard - HealthBuddy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
  <style>
    :root{
      --bg:#0f172a; --card:#111827; --muted:#94a3b8; --text:#e5e7eb;
      --accent:#22d3ee; --green:#22c55e; --green-dark:#16a34a;
      --red:#ef4444; --red-dark:#dc2626; --ring:rgba(34,211,238,.25);
    }
    *{box-sizing:border-box}
    body{
      margin:0; font-family: system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
      background:
        radial-gradient(1200px 400px at -10% -10%, rgba(34,211,238,0.06), transparent 40%),
        radial-gradient(1000px 500px at 110% 10%, rgba(34,197,94,0.06), transparent 40%),
        var(--bg);
      color:var(--text); min-height:100vh;
    }
    .topbar{position:sticky;top:0;z-index:50;background:rgba(15,23,42,.8);backdrop-filter:blur(8px);
      border-bottom:1px solid rgba(148,163,184,.1);padding:10px 16px;display:flex;align-items:center;justify-content:space-between}
    .brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:.3px}
    .brand img{height:28px;width:28px;object-fit:contain}
    .brand span{color:#fff}
    .nav{display:flex;gap:14px;align-items:center}
    .nav a{color:var(--text);text-decoration:none;font-weight:600;padding:8px 12px;border-radius:10px}
    .nav a:hover{background:rgba(148,163,184,.12)}
    .pill{padding:6px 10px;font-size:12px;border-radius:999px;background:rgba(148,163,184,.15);color:var(--muted)}
    .wrap{max-width:1100px;margin:0 auto;padding:18px 16px 32px}
    .headline{display:flex;align-items:center;gap:14px;margin:16px 6px 10px}
    .headline img{width:48px;height:48px;object-fit:contain;opacity:.9}
    .headline h2{margin:0;font-size:clamp(20px,2.8vw,28px)}
    .sub{color:var(--muted);margin:6px 6px 18px;font-size:14px}
    .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px}
    .card{grid-column:span 12;background:linear-gradient(180deg,rgba(255,255,255,.02),rgba(255,255,255,0)),var(--card);
      border:1px solid rgba(148,163,184,.12);border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.25);
      overflow:hidden;display:flex;gap:18px;align-items:stretch;padding:14px;transition:.15s}
    .card:hover{transform:translateY(-2px);box-shadow:0 16px 36px rgba(0,0,0,.32);border-color:rgba(34,211,238,.25)}
    .left{width:140px;min-width:140px;display:flex;flex-direction:column;align-items:center;gap:10px}
    .medicine-image{width:120px;height:120px;object-fit:contain;border-radius:12px;border:1px solid rgba(148,163,184,.2);background:rgba(148,163,184,.06)}
    .status-line{display:flex;gap:8px;align-items:center;font-size:14px}
    .status-cell{font-weight:700}
    .status-taken{color:var(--green)}
    .status-missed{color:var(--red)}
    .status-time{color:var(--muted);font-size:12px}
    .right{flex:1;display:flex;flex-direction:column;gap:10px;padding-top:2px}
    .drug{font-size:clamp(18px,2.4vw,22px);margin:2px 0 0}
    .metaGrid{display:grid;gap:10px;grid-template-columns:repeat(6,1fr)}
    .metaItem{grid-column:span 2;background:rgba(148,163,184,.08);border:1px dashed rgba(148,163,184,.18);border-radius:12px;padding:10px 12px;min-height:54px}
    .metaItem h5{margin:0 0 6px;font-size:12px;color:var(--muted);font-weight:700;letter-spacing:.4px;text-transform:uppercase}
    .metaItem p{margin:0;font-weight:600}
    .voice-panel{background:rgba(148,163,184,.08);border:1px dashed rgba(148,163,184,.18);border-radius:12px;padding:12px;margin-top:6px}
    .voice-panel h5{margin:0 0 6px;font-size:12px;color:var(--muted);font-weight:700;letter-spacing:.4px;text-transform:uppercase}
    .voice-panel audio{width:100%;margin-top:6px}
    .voice-download{display:inline-flex;gap:6px;align-items:center;margin-top:8px;font-weight:600;color:var(--text);text-decoration:none;
      padding:6px 10px;border-radius:10px;background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.18)}
    .voice-download:hover{background:rgba(148,163,184,.14)}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}
    .status-btn{padding:11px 16px;border:none;border-radius:12px;cursor:pointer;font-size:15px;font-weight:700;color:#fff;
      transition:background-color .25s,transform .15s,box-shadow .2s;box-shadow:0 6px 16px rgba(0,0,0,.25);outline:none}
    .status-btn:focus-visible{box-shadow:0 0 0 4px var(--ring)}
    .btnTaken{background:var(--green)}
    .btnTaken:hover:not(:disabled){background:var(--green-dark);transform:translateY(-1px) scale(1.02)}
    .btnMissed{background:var(--red)}
    .btnMissed:hover:not(:disabled){background:var(--red-dark);transform:translateY(-1px) scale(1.02)}
    .status-btn:active:not(:disabled){transform:translateY(0) scale(.98)}
    .status-btn:disabled{background:rgba(148,163,184,.35);color:rgba(229,231,235,.75);cursor:not-allowed!important;box-shadow:none}
    .empty{text-align:center;padding:40px 12px;color:var(--muted);border:1px dashed rgba(148,163,184,.25);border-radius:14px;background:rgba(148,163,184,.04);margin:16px 6px}
    @media (max-width:920px){.left{width:120px;min-width:120px}.medicine-image{width:100px;height:100px}.metaItem{grid-column:span 3}}
    @media (max-width:640px){.card{flex-direction:column;padding:14px}.left{width:100%;min-width:0;flex-direction:row;gap:14px}
      .medicine-image{width:90px;height:90px}.metaGrid{grid-template-columns:repeat(2,1fr)}.metaItem{grid-column:span 1}.actions{width:100%}
      .status-btn{flex:1}.nav a{padding:8px 10px}.headline img{width:40px;height:40px}}
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <img src="/images/logo1.png" alt="HealthyBuddy Logo" />
      <span>HealthyBuddy</span>
      <span class="pill">Patient</span>
    </div>
    <nav class="nav">
      <a href="/HTML_PHP/patient_dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
      <a href="/HTML_PHP/Logout.php"><i class="fa fa-sign-out"></i> Logout</a>
      <a href="/HTML_PHP/userProfile.php"><i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
    </nav>
  </header>

  <main class="wrap">
    <div class="headline">
      <img src="/images/images.png" alt="Pills" />
      <h2>Today's Medicines</h2>
    </div>
    <p class="sub">Mark each medicine as <strong>Taken</strong> or <strong>Missed</strong>. After you mark <strong>Taken twice</strong> today, both buttons lock automatically—even after refresh.</p>

    <?php if (empty($medicines)) : ?>
      <div class="empty">
        <p><i class="fa fa-info-circle"></i> No medicines scheduled for today.</p>
      </div>
    <?php else : ?>
      <section class="grid">
        <?php foreach ($medicines as $m) :
          $disabledPair = ($m['taken_count'] >= 2);
          $statusClass = strtolower($m['last_status']) === 'taken' ? 'status-taken' :
                         (strtolower($m['last_status']) === 'missed' ? 'status-missed' : '');
        ?>
          <article class="card">
            <div class="left">
              <?php if (!empty($m['image_path']) && file_exists($m['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($m['image_path']); ?>" alt="Medicine Image" class="medicine-image">
              <?php else: ?>
                <img src="/images/aspirin.jpeg" alt="Medicine Image" class="medicine-image">
              <?php endif; ?>

              <div class="status-line">
                <span style="color:var(--muted)">Status:</span>
                <span class="status-cell <?php echo $statusClass; ?>">
                  <?php echo htmlspecialchars($m['last_status']); ?>
                </span>
              </div>
              <small class="status-time">
                <?php echo !empty($m['last_status_time']) ? date("h:i A", strtotime($m['last_status_time'])) : ''; ?>
              </small>
            </div>

            <div class="right">
              <h3 class="drug"><span style="color: var(--accent);">Drug:</span> <?php echo htmlspecialchars($m['name']); ?></h3>

              <div class="metaGrid">
                <div class="metaItem">
                  <h5>Meal Timing</h5>
                  <p><?php echo htmlspecialchars($m['meal_timing']); ?></p>
                </div>
                <div class="metaItem">
                  <h5>Frequency</h5>
                  <p><?php echo htmlspecialchars($m['frequency']); ?></p>
                </div>
                <div class="metaItem">
                  <h5>Dosage</h5>
                  <p><?php echo htmlspecialchars($m['dosage']); ?></p>
                </div>
                <div class="metaItem">
                  <h5>Scheduled Time</h5>
                  <p><?php echo date("h:i A", strtotime($m['intake_time'])); ?></p>
                </div>
                <div class="metaItem">
                  <h5>Today Taken</h5>
                  <p class="today-taken" data-id="<?php echo $m['id']; ?>"><?php echo (int)$m['taken_count']; ?> / 2</p>
                </div>
                <div class="metaItem">
                  <h5>Last Update</h5>
                  <p class="last-update" data-id="<?php echo $m['id']; ?>">
                    <?php echo !empty($m['last_status_time']) ? date("h:i A", strtotime($m['last_status_time'])) : '—'; ?>
                  </p>
                </div>
              </div>

              <!-- Caregiver Voice Note (NEW) -->
              <?php if (!empty($m['audio_path']) && file_exists($m['audio_path'])): ?>
                <div class="voice-panel">
                  <h5><i class="fa fa-volume-up" aria-hidden="true"></i> Caregiver Voice Note</h5>
                  <audio controls preload="none">
                    <source src="<?php echo htmlspecialchars($m['audio_path']); ?>">
                    Your browser can’t play this audio.
                  </audio>
                  <a class="voice-download" href="<?php echo htmlspecialchars($m['audio_path']); ?>" download>
                    <i class="fa fa-download" aria-hidden="true"></i> Download
                  </a>
                </div>
              <?php endif; ?>

              <div class="actions">
                <button
                  class="status-btn btnTaken"
                  data-id="<?php echo $m['id']; ?>"
                  data-status="Taken"
                  data-taken-count="<?php echo (int)$m['taken_count'];?>"
                  <?php echo $disabledPair ? 'disabled' : ''; ?>
                >
                  Taken
                </button>
                <button
                  class="status-btn btnMissed"
                  data-id="<?php echo $m['id']; ?>"
                  data-status="Missed"
                  data-taken-count="<?php echo (int)$m['taken_count'];?>"
                  <?php echo $disabledPair ? 'disabled' : ''; ?>
                >
                  Missed
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <script>
    // Keep a local mirror initialized from server-rendered counts
    const takenCount = {};

    function disableBothButtons(cardEl) {
      cardEl.querySelectorAll('.status-btn').forEach(b => b.disabled = true);
    }

    // Initialize disabling on load (server already disabled via PHP, this is just belt & suspenders)
    document.querySelectorAll('.status-btn').forEach(btn => {
      const medicineId = btn.dataset.id;
      const initialTaken = parseInt(btn.dataset.takenCount || "0", 10);
      if (!takenCount[medicineId]) takenCount[medicineId] = initialTaken;

      if (takenCount[medicineId] >= 2) {
        disableBothButtons(btn.closest('.card'));
      }
    });

    // Wire up clicks
    document.querySelectorAll('.status-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const card = btn.closest('.card');
        const medicineId = btn.dataset.id;
        const status = btn.dataset.status;

        // Hard stop if already at 2 (extra safety)
        if ((takenCount[medicineId] || 0) >= 2) {
          disableBothButtons(card);
          return;
        }

        fetch('update_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `medicine_id=${encodeURIComponent(medicineId)}&status=${encodeURIComponent(status)}`
        })
        .then(res => res.json())
        .then(data => {
          if (!data || !data.success) {
            alert((data && data.message) ? data.message : 'Failed to update');
            return;
          }

          // Update status text + styling
          const statusCell = card.querySelector('.status-cell');
          statusCell.textContent = data.new_status || status;
          statusCell.classList.remove('status-taken', 'status-missed');
          if ((data.new_status || status) === 'Taken') statusCell.classList.add('status-taken');
          if ((data.new_status || status) === 'Missed') statusCell.classList.add('status-missed');

          // Update last update time
          const statusTimeEl = card.querySelector('.status-time');
          if (statusTimeEl) statusTimeEl.textContent = data.update_time || '';

          // Update the "Today Taken" box using authoritative server count
          takenCount[medicineId] = parseInt(data.new_taken_count || "0", 10);
          const takenBox = card.querySelector('.today-taken');
          if (takenBox) takenBox.textContent = (takenCount[medicineId]) + ' / 2';

          const lastUpdateP = card.querySelector('.last-update');
          if (lastUpdateP) lastUpdateP.textContent = data.update_time || '—';

          // If reached 2, lock both buttons
          if (takenCount[medicineId] >= 2) {
            disableBothButtons(card);
          }
        })
        .catch(err => alert('Error: ' + err));
      });
    });
  </script>
</body>
</html>
