<?php
$conn = new mysqli("127.0.0.1", "root", "", "sharemyride");
if ($conn->connect_error) {
    die("<h2 style='color:red'>❌ Cannot connect: " . $conn->connect_error . "<br>Start XAMPP MySQL first!</h2>");
}
$conn->set_charset("utf8mb4");

// Get all tables
$tablesResult = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $tablesResult->fetch_array()) { $tables[] = $row[0]; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShareMyRide — Database Viewer</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #0d0d1a; color: #e0e0f0; }
.header { background: linear-gradient(135deg,#7c6fff,#6c63ff); padding: 20px 30px; display:flex; align-items:center; gap:14px; }
.header h1 { color:#fff; font-size:22px; }
.header .badge { background:rgba(255,255,255,.2); color:#fff; padding:4px 12px; border-radius:20px; font-size:13px; }
.sidebar { position:fixed; left:0; top:70px; width:200px; height:calc(100vh - 70px); background:#12122a; overflow-y:auto; padding:12px 0; border-right:1px solid #2a2a4a; }
.sidebar a { display:block; padding:9px 18px; color:#a0a0c0; text-decoration:none; font-size:13px; transition:.2s; }
.sidebar a:hover, .sidebar a.active { background:#1f1f3a; color:#7c6fff; border-right:3px solid #7c6fff; }
.sidebar .cnt { float:right; background:#7c6fff; color:#fff; border-radius:10px; padding:1px 7px; font-size:11px; }
.main { margin-left:200px; padding:24px; }
.table-card { background:#1a1a2e; border-radius:12px; margin-bottom:28px; overflow:hidden; border:1px solid #2a2a4a; }
.table-header { padding:14px 20px; background:#1f1f40; display:flex; align-items:center; justify-content:space-between; }
.table-header h2 { font-size:15px; color:#7c6fff; }
.table-header .meta { color:#888; font-size:13px; }
.table-header .row-count { background:#7c6fff22; border:1px solid #7c6fff55; color:#7c6fff; padding:3px 12px; border-radius:20px; font-size:12px; }
.scroll { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { background:#16163a; color:#a0a0ff; padding:10px 14px; text-align:left; font-weight:600; border-bottom:1px solid #2a2a4a; white-space:nowrap; }
td { padding:9px 14px; border-bottom:1px solid #1e1e3a; color:#ccc; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#1f1f38; }
.empty { padding:20px; text-align:center; color:#555; font-size:14px; }
.badge-status { padding:2px 10px; border-radius:10px; font-size:11px; font-weight:600; }
.badge-active   { background:#16502e; color:#4ade80; }
.badge-pending  { background:#3d2e00; color:#fbbf24; }
.badge-accepted { background:#1a3a5c; color:#60a5fa; }
.badge-paid     { background:#16502e; color:#4ade80; }
.badge-failed   { background:#4c1010; color:#f87171; }
.badge-completed{ background:#1a2e4c; color:#818cf8; }
.badge-cancelled{ background:#3a1a1a; color:#f87171; }
.badge-user     { background:#1a1a4c; color:#818cf8; }
.badge-admin    { background:#4c1a1a; color:#f87171; }
.null { color:#444; font-style:italic; }
.top-stats { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:14px; margin-bottom:26px; }
.stat-card { background:#1a1a2e; border-radius:10px; padding:16px; text-align:center; border:1px solid #2a2a4a; }
.stat-card .num { font-size:28px; font-weight:700; color:#7c6fff; }
.stat-card .lbl { font-size:12px; color:#888; margin-top:4px; }
.refresh-btn { background:#7c6fff; color:#fff; border:none; padding:8px 18px; border-radius:8px; cursor:pointer; font-size:13px; text-decoration:none; display:inline-block; }
</style>
</head>
<body>

<div class="header">
    <h1>🗄️ ShareMyRide — Live Database</h1>
    <?php
    $totalRows = 0;
    foreach ($tables as $t) {
        $r = $conn->query("SELECT COUNT(*) as c FROM `$t`")->fetch_assoc();
        $totalRows += $r['c'];
    }
    ?>
    <span class="badge"><?= count($tables) ?> Tables &nbsp;|&nbsp; <?= $totalRows ?> Total Rows</span>
    <a href="setup_database.php" class="refresh-btn" style="margin-left:auto">⚙️ Setup DB</a>
    &nbsp;
    <a href="login.php" class="refresh-btn" style="background:#34d399">🚀 Go to App</a>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <?php foreach ($tables as $t):
        $cnt = $conn->query("SELECT COUNT(*) as c FROM `$t`")->fetch_assoc()['c'];
    ?>
        <a href="#tbl-<?= $t ?>"><?= $t ?> <span class="cnt"><?= $cnt ?></span></a>
    <?php endforeach; ?>
</div>

<div class="main">

<!-- Top Stats -->
<div class="top-stats">
<?php
$stats = [
    'users'         => 'Users',
    'rides'         => 'Rides',
    'ride_requests' => 'Requests',
    'payments'      => 'Payments',
    'notifications' => 'Notifs',
    'reviews'       => 'Reviews',
];
foreach ($stats as $tbl => $label):
    if (in_array($tbl, $tables)):
        $c = $conn->query("SELECT COUNT(*) as c FROM `$tbl`")->fetch_assoc()['c'];
?>
    <div class="stat-card">
        <div class="num"><?= $c ?></div>
        <div class="lbl"><?= $label ?></div>
    </div>
<?php endif; endforeach; ?>
</div>

<!-- Tables -->
<?php foreach ($tables as $tableName):
    $result  = $conn->query("SELECT * FROM `$tableName` ORDER BY 1 DESC LIMIT 50");
    $rowCnt  = $conn->query("SELECT COUNT(*) as c FROM `$tableName`")->fetch_assoc()['c'];
    $fields  = $result->fetch_fields();
    $rows    = $result->fetch_all(MYSQLI_ASSOC);
?>
<div class="table-card" id="tbl-<?= $tableName ?>">
    <div class="table-header">
        <h2>📋 <?= htmlspecialchars($tableName) ?></h2>
        <div>
            <span class="meta"><?= count($fields) ?> columns</span> &nbsp;
            <span class="row-count"><?= $rowCnt ?> rows</span>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty">⚪ No data yet in this table.</div>
    <?php else: ?>
    <div class="scroll">
    <table>
        <thead>
            <tr>
                <?php foreach ($fields as $f): ?>
                    <th><?= htmlspecialchars($f->name) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $key => $val): ?>
                    <td>
                    <?php
                    if ($val === null) {
                        echo '<span class="null">NULL</span>';
                    } elseif ($key === 'status') {
                        echo "<span class='badge-status badge-$val'>$val</span>";
                    } elseif ($key === 'role') {
                        echo "<span class='badge-status badge-$val'>$val</span>";
                    } elseif ($key === 'password' || $key === 'razorpay_signature') {
                        echo '<span class="null">[hidden]</span>';
                    } elseif (strlen((string)$val) > 40) {
                        echo htmlspecialchars(substr($val, 0, 40)) . '…';
                    } else {
                        echo htmlspecialchars((string)$val);
                    }
                    ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

</div><!-- /main -->
</body>
</html>
