<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caretaker') {
    header("Location: /HTML_PHP/Login.php");
    exit();
}

include('db.php');

// Fetch all patients for dropdown
$patients = [];
$pstmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'patient'");
$pstmt->execute();
$result = $pstmt->get_result();
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

$success = $error = "";
$uploaded_medicines = [];
$selected_patient_id = "";

// ===== Helpers for audio saving =====
function hb_ext_from_mime($mime) {
    $map = [
        'audio/webm'   => 'webm',
        'audio/ogg'    => 'ogg',
        'audio/mpeg'   => 'mp3',
        'audio/mp3'    => 'mp3',
        'audio/wav'    => 'wav',
        'audio/x-wav'  => 'wav',
        'audio/mp4'    => 'm4a',
        'audio/aac'    => 'aac',
        'audio/3gpp'   => '3gp',
        'audio/3gpp2'  => '3g2',
        // some browsers report generic binary for webm/opus
        'application/octet-stream' => 'webm'
    ];
    return $map[strtolower($mime)] ?? 'webm';
}
function hb_is_allowed_mime($mime) {
    $allowed = [
        'audio/webm','audio/ogg','audio/mpeg','audio/mp3',
        'audio/wav','audio/x-wav','audio/mp4','audio/aac',
        'audio/3gpp','audio/3gpp2','application/octet-stream'
    ];
    return in_array(strtolower($mime), $allowed, true);
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_form'])) {
    $patient_id = $_POST['patient_id'];
    $selected_patient_id = $patient_id;
    $name = $_POST['name'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $meal_timing = $_POST['meal_timing'];
    $intake_time = $_POST['intake_time'];

    $image_path = '';
    $audio_path = ''; // NEW

    // ===== Image upload (unchanged) =====
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $safe_filename = uniqid('med_') . '.' . $file_ext;
        $image_path = $upload_dir . $safe_filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $error = "Failed to upload image.";
        }
    }

    // ===== Audio: either a chosen file OR a recorded base64 =====
    if (!$error) {
        // 1) If caretakers selected an audio file:
        if (isset($_FILES['audio']) && $_FILES['audio']['error'] === 0) {
            $audio_dir = 'uploads/audio/';
            if (!is_dir($audio_dir)) mkdir($audio_dir, 0777, true);

            // Validate MIME using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['audio']['tmp_name']);
            if (!hb_is_allowed_mime($mime)) {
                $error = "Unsupported audio type.";
            } else {
                $ext = hb_ext_from_mime($mime);
                $safe_audio = uniqid('voice_') . '.' . $ext;
                $dest = $audio_dir . $safe_audio;
                if (move_uploaded_file($_FILES['audio']['tmp_name'], $dest)) {
                    $audio_path = $dest;
                } else {
                    $error = "Failed to upload audio file.";
                }
            }
        }
    }

    if (!$error) {
        // 2) If they recorded audio in browser (base64 data URL)
        if (empty($audio_path) && !empty($_POST['audio_b64'])) {
            $audio_dir = 'uploads/audio/';
            if (!is_dir($audio_dir)) mkdir($audio_dir, 0777, true);

            $dataUrl = $_POST['audio_b64'];
            $mime = 'audio/webm'; // default fallback
            if (strpos($dataUrl, 'data:') === 0 && strpos($dataUrl, ';base64,') !== false) {
                // Expected format: data:<mime>;base64,<payload>
                [$meta, $b64] = explode(',', $dataUrl, 2);
                $mime = str_replace(['data:', ';base64'], '', explode(';', $meta)[0]);
            } else {
                // If for some reason only raw base64 was sent along with a mime
                if (!empty($_POST['audio_mime'])) $mime = $_POST['audio_mime'];
                $b64 = $dataUrl;
            }

            if (!hb_is_allowed_mime($mime)) {
                $error = "Unsupported recorded audio type.";
            } else {
                // size guard (~rough)
                $approxBytes = (int)(strlen($b64) * 3 / 4);
                if ($approxBytes > 15 * 1024 * 1024) { // 15MB
                    $error = "Recorded audio too large (max 15MB).";
                } else {
                    $ext = hb_ext_from_mime($mime);
                    $safe_audio = uniqid('voice_') . '.' . $ext;
                    $dest = $audio_dir . $safe_audio;

                    $decoded = base64_decode($b64);
                    if ($decoded === false || file_put_contents($dest, $decoded) === false) {
                        $error = "Failed to save recorded audio.";
                    } else {
                        $audio_path = $dest;
                    }
                }
            }
        }
    }

    if (!$error) {
        // INSERT includes audio_path (make sure you ran the ALTER TABLE above)
        $stmt = $conn->prepare("INSERT INTO medicines (patient_id, name, dosage, frequency, meal_timing, intake_time, image_path, audio_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $patient_id, $name, $dosage, $frequency, $meal_timing, $intake_time, $image_path, $audio_path);
        if ($stmt->execute()) {
            $success = "✅ Medicine uploaded successfully!";
        } else {
            $error = "❌ Database error: " . $conn->error;
        }
    }
}

// Fetch Medicines (show all)
if (!empty($_POST['view_patient_id']) || !empty($_GET['patient_id']) || $selected_patient_id) {
    $patient_id = $_POST['view_patient_id'] ?? $_GET['patient_id'] ?? $selected_patient_id;
    $selected_patient_id = $patient_id;
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE patient_id = ? ORDER BY intake_time DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uploaded_medicines[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Caretaker Dashboard - HealthBuddy</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    :root{
        --primary:#42a5f5;
        --secondary:#66bb6a;
        --accent:#00796b;
        --bg:#eef6f6;
        --card:#ffffff;
        --text:#263238;
        --muted:#6b7a85;
        --ring: 0 10px 30px rgba(0,0,0,.08);
        --radius:18px;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
        margin:0;
        font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
        color:var(--text);
        background:
          radial-gradient(1200px 600px at 100% -10%, rgba(102,187,106,.15), transparent 60%),
          radial-gradient(800px 400px at -10% 110%, rgba(66,165,245,.18), transparent 60%),
          linear-gradient(135deg,#f7fbfb,#f0fbf4 40%, #eef7ff);
    }

    /* Navbar */
    .nav{
        position:sticky; top:0; z-index:50;
        display:flex; align-items:center; justify-content:space-between;
        padding:12px 20px;
        background:linear-gradient(90deg, var(--primary), var(--secondary));
        color:#fff;
        box-shadow:0 6px 18px rgba(0,0,0,.15);
    }
    .brand{display:flex; align-items:center; gap:10px; font-weight:700; letter-spacing:.2px}
    .brand img{height:36px; width:auto}
    .nav-links{display:flex; align-items:center; gap:14px; list-style:none; margin:0; padding:0}
    .nav-links li{padding:8px 12px; border-radius:999px; cursor:pointer; transition:.2s}
    .nav-links li:hover{background:rgba(255,255,255,.18)}
    .nav-links li.active{background:rgba(255,255,255,.28)}
    .hamburger{display:none; background:transparent; border:0; color:#fff; font-size:26px; cursor:pointer}

    /* Mobile menu */
    @media (max-width: 820px){
        .hamburger{display:block}
        .nav-links{display:none}
        .nav.open .nav-links{
            display:flex; flex-direction:column; gap:8px;
            position:absolute; left:0; right:0; top:60px;
            background:linear-gradient(90deg, var(--primary), var(--secondary));
            padding:12px; border-bottom-left-radius:16px; border-bottom-right-radius:16px;
        }
    }

    /* Layout */
    .container{max-width:1100px; margin:20px auto; padding:0 16px}

    .card{
        background:var(--card);
        border-radius:var(--radius);
        box-shadow:var(--ring);
        padding:20px;
        margin-bottom:20px;
        backdrop-filter: blur(6px);
        animation:fadeIn .35s ease;
    }
    .card h2{
        margin:0 0 14px;
        color:var(--accent);
        font-size:clamp(1.1rem, 2.3vw, 1.4rem);
        letter-spacing:.2px;
    }

    /* Alerts */
    .alert{
        display:flex; align-items:center; justify-content:space-between;
        gap:10px;
        padding:10px 12px; border-radius:12px; margin:10px 0 16px;
        font-weight:600;
    }
    .alert.success{background:#e7f6ee; color:#1b5e20; border:1px solid #c7ebd6}
    .alert.error{background:#fdecea; color:#b71c1c; border:1px solid #f6c8c4}
    .alert button{background:transparent; border:0; font-size:18px; cursor:pointer; color:inherit}

    /* Form */
    form .grid{
        display:grid; gap:12px;
        grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
        margin-top:6px;
    }
    label{font-weight:600; font-size:.92rem; color:#295; display:block; margin-top:6px}
    input[type="text"], input[type="datetime-local"], select, input[type="file"]{
        width:100%; padding:10px 12px; border-radius:12px; border:1px solid #d5e2e8; outline:none;
        background:#fafcfd; color:var(--text);
        transition:border .2s, box-shadow .2s;
    }
    input:focus, select:focus{
        border-color:#9ad0ff; box-shadow:0 0 0 4px rgba(66,165,245,.15)
    }
    .actions{display:flex; gap:10px; flex-wrap:wrap; margin-top:10px}
    .btn{
        padding:10px 14px; border:0; border-radius:999px; cursor:pointer; font-weight:700;
        background:linear-gradient(90deg, var(--primary), var(--secondary));
        color:#fff; box-shadow:0 6px 14px rgba(66,165,245,.25);
        transition: transform .1s ease, filter .25s ease;
    }
    .btn:active{transform:translateY(1px)}
    .btn.outline{
        background:#fff; color:var(--accent); border:2px solid #b7e0c2; box-shadow:none
    }

    /* File preview */
    .preview{
        display:flex; align-items:center; gap:12px; margin-top:8px;
        color:var(--muted); font-size:.9rem
    }
    .preview img{width:56px; height:56px; object-fit:cover; border-radius:12px; border:1px solid #e6eef2}

    /* Voice UI */
    .voice-box{
        border:1px dashed #cfe7d9; background:#f6fff9; border-radius:14px; padding:12px;
    }
    .voice-controls{display:flex; gap:8px; flex-wrap:wrap; align-items:center}
    .btn-rec{background:#ff5252}
    .btn-stop{background:#ff9800}
    .btn-clear{background:#9e9e9e}
    .rec-dot{display:inline-block; width:10px; height:10px; border-radius:50%; background:#ff5252; margin-right:6px; animation:pulse 1s infinite}
    @keyframes pulse{0%{opacity:.4} 50%{opacity:1} 100%{opacity:.4}}
    .timer{font-weight:800; color:#c62828}
    audio{width:100%; max-width:420px; margin-top:6px}

    /* Table */
    table{width:100%; border-collapse:collapse; overflow:hidden; border-radius:14px}
    thead th{
        background:linear-gradient(90deg, var(--primary), var(--secondary));
        color:#fff; text-align:left; font-weight:700; padding:12px;
        letter-spacing:.2px; font-size:.92rem
    }
    tbody td{padding:12px; border-bottom:1px solid #eef3f5; vertical-align:top}
    tbody tr:hover{background:#f8fbfd}

    .pill{display:inline-block; padding:6px 10px; border-radius:999px; font-weight:700; font-size:.8rem}
    .pill.meal{background:#eaf7ff; color:#0d47a1}
    .badge{display:inline-block; padding:6px 10px; border-radius:999px; font-weight:800; font-size:.78rem}
    .badge.taken{background:#e8f5e9; color:#1b5e20}
    .badge.missed{background:#ffebee; color:#b71c1c}
    .badge.default{background:#eef3f5; color:#455a64}

    .img-thumb{width:54px; height:54px; border-radius:10px; object-fit:cover; border:1px solid #e0eaef; cursor:pointer}

    /* Responsive: transform table to cards */
    @media (max-width: 820px){
        table, thead, tbody, th, td, tr{display:block; width:100%}
        thead{display:none}
        tbody tr{
            background:#fff; border-radius:16px; box-shadow:var(--ring);
            margin:0 0 14px; padding:10px 10px 6px;
        }
        tbody td{
            display:flex; justify-content:space-between; align-items:flex-start;
            gap:14px; border-bottom:1px dashed #eef3f5;
            padding:10px 6px;
        }
        tbody td:last-child{border-bottom:0}
        tbody td::before{
            content: attr(data-label);
            font-weight:700; color:var(--accent); min-width:120px; flex-shrink:0;
        }
        .img-thumb{width:64px; height:64px}
    }

    /* Simple fade */
    @keyframes fadeIn{from{opacity:0; transform:translateY(8px)} to{opacity:1; transform:none}}

    /* Modal for image */
    .modal{position:fixed; inset:0; background:rgba(0,0,0,.6); display:none; align-items:center; justify-content:center; padding:16px; z-index:60}
    .modal.open{display:flex}
    .modal img{max-width:92vw; max-height:82vh; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,.35)}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="nav">
    <div class="brand"><img src="/images/logo1.png" alt=""><span>HealthyBuddy</span></div>
    <button class="hamburger" id="hamburger" aria-label="Toggle Menu">☰</button>
    <ul class="nav-links" id="navLinks">
        <li class="active" data-target="upload">Upload Medicines</li>
        <li data-target="medicines">Medicines for Patient</li>
        <li onclick="window.location.href='/HTML_PHP/Logout.php'">Logout</li>
    </ul>
</nav>

<div class="container">
    <!-- Upload Section -->
    <section id="upload" class="card">
        <h2>📤 Upload Medicine</h2>

        <?php if ($success): ?>
            <div class="alert success" id="successAlert">
                <span><?php echo $success; ?></span>
                <button onclick="this.closest('.alert').remove()">✕</button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error" id="errorAlert">
                <span><?php echo $error; ?></span>
                <button onclick="this.closest('.alert').remove()">✕</button>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="upload_form" value="1">
            <!-- Hidden fields to carry recorded audio as base64 (if used) -->
            <input type="hidden" name="audio_b64" id="audio_b64">
            <input type="hidden" name="audio_mime" id="audio_mime">

            <div class="grid">
                <div>
                    <label>Choose Patient</label>
                    <select name="patient_id" required>
                        <option value="">— Select Patient —</option>
                        <?php foreach ($patients as $pat): ?>
                            <option value="<?php echo $pat['id']; ?>" <?php if ($selected_patient_id == $pat['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($pat['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Medicine Name</label>
                    <input type="text" name="name" placeholder="e.g. Paracetamol" required>
                </div>

                <div>
                    <label>Dosage</label>
                    <select name="dosage" required>
                        <option value="1 tablet">1 tablet</option>
                        <option value="2 tablets">2 tablets</option>
                        <option value="5 ml">5 ml</option>
                        <option value="10 ml">10 ml</option>
                        <option value="Half tablet">Half tablet</option>
                    </select>
                </div>

                <div>
                    <label>Frequency</label>
                    <select name="frequency" required>
                        <option value="Once a day">Once a day</option>
                        <option value="Twice a day">Twice a day</option>
                        <option value="Thrice a day">Thrice a day</option>
                        <option value="Every 6 hours">Every 6 hours</option>
                        <option value="As needed">As needed</option>
                    </select>
                </div>

                <div>
                    <label>Meal Timing</label>
                    <select name="meal_timing" required>
                        <option value="Before meal">Before meal</option>
                        <option value="After meal">After meal</option>
                        <option value="With meal">With meal</option>
                        <option value="Empty stomach">Empty stomach</option>
                    </select>
                </div>

                <div>
                    <label>Intake Time</label>
                    <input type="datetime-local" name="intake_time" required>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label>Medicine Image (optional)</label>
                    <input type="file" name="image" id="imageInput" accept="image/*">
                    <div class="preview" id="preview" style="display:none;">
                        <img id="previewImg" alt="Preview">
                        <span id="previewName"></span>
                    </div>
                </div>

                <!-- ===== VOICE NOTE (NEW) ===== -->
                <div style="grid-column: 1 / -1;">
                    <label>Voice Note (optional)</label>
                    <div class="voice-box">
                        <div class="voice-controls">
                            <button type="button" class="btn btn-rec" id="recBtn">● Record</button>
                            <button type="button" class="btn btn-stop" id="stopBtn" disabled>■ Stop</button>
                            <button type="button" class="btn btn-clear" id="clearVoiceBtn" disabled>✕ Clear</button>
                            <span id="recState" style="font-weight:700; color:#37474f;"></span>
                            <span class="timer" id="timer" style="display:none;">00:00</span>
                        </div>

                        <!-- Live or recorded playback -->
                        <audio id="audioPlayback" controls style="display:none;"></audio>

                        <!-- Fallback: upload an audio file instead (mobile can open mic with capture) -->
                        <div style="margin-top:10px; color:var(--muted); font-size:.9rem">
                            Or upload audio file:
                        </div>
                        <input type="file" name="audio" id="audioFile" accept="audio/*" capture>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Upload Medicine</button>
                <button class="btn outline" type="button" onclick="document.getElementById('uploadForm').reset(); hidePreview(); resetVoice();">Reset</button>
            </div>
        </form>
    </section>

    <!-- Medicines Section -->
    <section id="medicines" class="card" style="display:none;">
        <h2>💊 Medicines for Selected Patient</h2>
        <form method="post" class="actions" style="margin-top:0">
            <select name="view_patient_id" required style="max-width:340px;">
                <option value="">— Select Patient —</option>
                <?php foreach ($patients as $pat): ?>
                    <option value="<?php echo $pat['id']; ?>" <?php if ($selected_patient_id == $pat['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($pat['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">View Medicines</button>
        </form>

        <?php if (!empty($uploaded_medicines)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Meal Timing</th>
                        <th>Intake Time</th>
                        <th>Status</th>
                        <th>Status Time</th>
                        <th>Image</th>
                        <th>Voice</th> <!-- NEW -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploaded_medicines as $med): ?>
                        <?php
                            $statusText = !empty($med['status']) ? $med['status'] : 'Not Updated';
                            $statusClass = 'default';
                            $s = strtolower(trim($statusText));
                            if (strpos($s, 'taken') !== false) $statusClass = 'taken';
                            else if (strpos($s, 'miss') !== false) $statusClass = 'missed';
                            else if (strpos($s, 'pend') !== false) $statusClass = 'pending';
                        ?>
                        <tr>
                            <td data-label="Name"><?php echo htmlspecialchars($med['name']); ?></td>
                            <td data-label="Dosage"><?php echo htmlspecialchars($med['dosage']); ?></td>
                            <td data-label="Frequency"><?php echo htmlspecialchars($med['frequency']); ?></td>
                            <td data-label="Meal Timing"><span class="pill meal"><?php echo htmlspecialchars($med['meal_timing']); ?></span></td>
                            <td data-label="Intake Time"><?php echo date("Y-m-d H:i", strtotime($med['intake_time'])); ?></td>
                            <td data-label="Status"><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span></td>
                            <td data-label="Status Time">
                                <?php echo !empty($med['status_time']) ? date("Y-m-d H:i", strtotime($med['status_time'])) : "—"; ?>
                            </td>
                            <td data-label="Image">
                                <?php if (!empty($med['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($med['image_path']); ?>" class="img-thumb" alt="Medicine Image" onclick="openModal(this.src)">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td data-label="Voice">
                                <?php if (!empty($med['audio_path']) && file_exists($med['audio_path'])): ?>
                                    <audio controls preload="none" style="max-width:180px;">
                                        <source src="<?php echo htmlspecialchars($med['audio_path']); ?>">
                                        Your browser can’t play this audio.
                                    </audio>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:var(--muted); font-weight:600; margin-top:12px;">No medicines found for this patient.</p>
        <?php endif; ?>
    </section>
</div>

<!-- Image Modal -->
<div class="modal" id="imgModal" onclick="closeModal(event)">
    <img id="modalImg" src="" alt="Preview">
</div>

<script>
    // Section switching + navbar active state
    const nav = document.getElementById('nav');
    const links = document.getElementById('navLinks');
    const hamburger = document.getElementById('hamburger');
    const sectionEls = { upload: document.getElementById('upload'), medicines: document.getElementById('medicines') };

    hamburger.addEventListener('click', () => nav.classList.toggle('open'));

    links.querySelectorAll('li[data-target]').forEach(li => {
        li.addEventListener('click', () => {
            const id = li.getAttribute('data-target');
            Object.keys(sectionEls).forEach(k => sectionEls[k].style.display = (k === id ? 'block' : 'none'));
            links.querySelectorAll('li').forEach(n => n.classList.remove('active'));
            li.classList.add('active');
            nav.classList.remove('open');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Image preview for file input
    const imgInput = document.getElementById('imageInput');
    function hidePreview(){
        const box = document.getElementById('preview');
        box.style.display = 'none';
        document.getElementById('previewImg').src = '';
        document.getElementById('previewName').textContent = '';
    }
    if (imgInput){
        imgInput.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file){ hidePreview(); return; }
            const url = URL.createObjectURL(file);
            document.getElementById('previewImg').src = url;
            document.getElementById('previewName').textContent = file.name;
            document.getElementById('preview').style.display = 'flex';
        });
    }

    // Lightbox modal
    function openModal(src){
        document.getElementById('modalImg').src = src;
        document.getElementById('imgModal').classList.add('open');
    }
    function closeModal(e){
        if (e.target.id === 'imgModal') {
            document.getElementById('imgModal').classList.remove('open');
            document.getElementById('modalImg').src = '';
        }
    }

    // ===== Voice Recorder (MediaRecorder -> base64 -> hidden input) =====
    const recBtn = document.getElementById('recBtn');
    const stopBtn = document.getElementById('stopBtn');
    const clearVoiceBtn = document.getElementById('clearVoiceBtn');
    const recState = document.getElementById('recState');
    const timerEl = document.getElementById('timer');
    const audioPlayback = document.getElementById('audioPlayback');
    const audioFileInput = document.getElementById('audioFile');

    const audioB64 = document.getElementById('audio_b64');
    const audioMime = document.getElementById('audio_mime');

    let mediaRecorder = null;
    let chunks = [];
    let timer = null;
    let seconds = 0;

    function fmt(n){ return n < 10 ? '0' + n : n; }
    function startTimer(){
        seconds = 0;
        timerEl.style.display = 'inline-block';
        timerEl.textContent = '00:00';
        timer = setInterval(() => {
            seconds++;
            const mm = Math.floor(seconds / 60);
            const ss = seconds % 60;
            timerEl.textContent = `${fmt(mm)}:${fmt(ss)}`;
        }, 1000);
    }
    function stopTimer(){
        clearInterval(timer); timer = null; seconds = 0;
        timerEl.style.display = 'none';
    }

    function resetVoice() {
        // Stop any recording
        try { mediaRecorder && mediaRecorder.state !== 'inactive' && mediaRecorder.stop(); } catch (e) {}
        chunks = [];
        // Clear outputs
        audioPlayback.src = '';
        audioPlayback.style.display = 'none';
        recState.textContent = '';
        stopTimer();
        // Clear hidden b64 inputs
        audioB64.value = '';
        audioMime.value = '';
        // Clear file input fallback
        if (audioFileInput) audioFileInput.value = '';
        // Buttons state
        recBtn.disabled = false;
        stopBtn.disabled = true;
        clearVoiceBtn.disabled = true;
    }

    clearVoiceBtn.addEventListener('click', resetVoice);

    if (!navigator.mediaDevices || !window.MediaRecorder) {
        // No recorder available
        recBtn.disabled = true;
        stopBtn.disabled = true;
        recState.textContent = 'Recording not supported on this device. Use file upload below.';
    } else {
        recBtn.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                chunks = [];
                mediaRecorder.ondataavailable = e => { if (e.data && e.data.size > 0) chunks.push(e.data); };
                mediaRecorder.onstart = () => {
                    recState.innerHTML = '<span class="rec-dot"></span>Recording…';
                    recBtn.disabled = true;
                    stopBtn.disabled = false;
                    clearVoiceBtn.disabled = true;
                    startTimer();
                };
                mediaRecorder.onstop = async () => {
                    stopTimer();
                    recState.textContent = 'Recorded';
                    stopBtn.disabled = true;
                    clearVoiceBtn.disabled = false;

                    const blob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    const url = URL.createObjectURL(blob);
                    audioPlayback.src = url;
                    audioPlayback.style.display = 'block';

                    // Convert to base64 data URL and stash in hidden inputs
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        audioB64.value = reader.result; // data:<mime>;base64,<payload>
                        audioMime.value = blob.type || 'audio/webm';
                    };
                    reader.readAsDataURL(blob);

                    // stop mic tracks
                    stream.getTracks().forEach(t => t.stop());
                };
                mediaRecorder.start();
            } catch (err) {
                recState.textContent = 'Microphone permission denied.';
                console.error(err);
            }
        });

        stopBtn.addEventListener('click', () => {
            try { mediaRecorder && mediaRecorder.stop(); } catch (e) {}
        });
    }
</script>

</body>
</html>
