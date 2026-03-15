<?php
// OE8YML Log Search — server-side, no JS required
// Queries wavelog-index.json (GitHub Pages) or Wavelog API directly
// Place at https://oeradio.at/logsearch.php

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: ALLOWALL');
header('Access-Control-Allow-Origin: *');

define('INDEX_URL', 'https://achildrenmile.github.io/qrzprofiles/wavelog-index.json');
define('CACHE_FILE', sys_get_temp_dir() . '/oe8yml_wavelog_index.json');
define('CACHE_TTL', 1800); // 30 minutes

function load_index() {
    // Use cache if fresh
    if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
        $data = file_get_contents(CACHE_FILE);
        if ($data) return json_decode($data, true);
    }
    // Fetch fresh
    $ctx = stream_context_create(['http' => [
        'timeout' => 15,
        'header' => "User-Agent: OE8YML-LogSearch/1.0\r\n",
    ]]);
    $data = @file_get_contents(INDEX_URL, false, $ctx);
    if ($data) {
        file_put_contents(CACHE_FILE, $data);
        return json_decode($data, true);
    }
    return null;
}

$q = isset($_GET['call']) ? strtoupper(trim($_GET['call'])) : '';
$q = preg_replace('/[^A-Z0-9\/]/', '', $q); // sanitize

$results = [];
$total = 0;
$unique_calls = 0;
$updated = '';
$error = '';

if ($q !== '') {
    $idx = load_index();
    if ($idx === null) {
        $error = 'Index nicht verfügbar.';
    } else {
        $total = (int)($idx['t'] ?? 0);
        $unique_calls = count($idx['q'] ?? []);
        $updated = htmlspecialchars($idx['u'] ?? '');
        foreach (($idx['q'] ?? []) as $call => $qsos) {
            if (strpos($call, $q) !== false) {
                $results[$call] = $qsos;
            }
        }
        ksort($results);
    }
}

function fmt_date($d) {
    if (strlen($d) === 8) {
        return substr($d,6,2).'.'.substr($d,4,2).'.'.substr($d,2,2);
    }
    return htmlspecialchars($d);
}
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Log Search — OE8YML</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#0f172a;color:#f1f5f9;padding:0.75rem;font-size:0.85rem}
.sr{display:flex;gap:8px;margin-bottom:0.5rem}
input[name=call]{flex:1;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:8px 12px;color:#f1f5f9;font-size:0.85rem;outline:none}
button{background:#3b82f6;border:none;border-radius:8px;padding:8px 16px;color:#fff;font-weight:600;cursor:pointer;white-space:nowrap}
.st{color:#64748b;font-size:0.7rem;margin-bottom:0.5rem;min-height:1rem}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:.3rem .6rem;color:#64748b;font-size:.68rem;font-weight:500;border-bottom:1px solid #334155;text-transform:uppercase;letter-spacing:.05em}
td{padding:.35rem .6rem;font-size:.82rem;border-bottom:1px solid #1a2744}
td:first-child{color:#e2e8f0;font-weight:700;font-family:'Courier New',monospace}
td:not(:first-child){color:#94a3b8}
.card{background:#1e293b;border:1px solid #334155;border-radius:8px;overflow:hidden}
.ch{padding:.35rem .6rem;background:#1a2744;font-size:.68rem;font-weight:600;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase}
</style>
</head>
<body>
<form class="sr" method="get" action="">
  <input name="call" type="text" placeholder="Callsign suchen..." value="<?= htmlspecialchars($q) ?>" autocomplete="off" spellcheck="false" autofocus>
  <button type="submit">Suchen</button>
</form>

<?php if ($q === ''): ?>
<div class="st">Callsign eingeben und Enter drücken</div>
<?php elseif ($error): ?>
<div class="st"><?= htmlspecialchars($error) ?></div>
<?php elseif (empty($results)): ?>
<div class="st">Kein QSO mit <strong style="color:#f1f5f9"><?= htmlspecialchars($q) ?></strong> gefunden.</div>
<?php if ($updated): ?><div class="st">Stand: <?= $updated ?> &mdash; <?= number_format($total) ?> QSOs, <?= number_format($unique_calls) ?> Calls</div><?php endif; ?>
<?php else: ?>
<div class="st">
<?= count($results) ?> <?= count($results) === 1 ? 'Callsign' : 'Callsigns' ?> für <strong style="color:#f1f5f9"><?= htmlspecialchars($q) ?></strong>
<?php if ($updated): ?>&mdash; Stand: <?= $updated ?><?php endif; ?>
</div>
<div class="card">
<div class="ch"><?= count($results) === 1 ? '1 Callsign' : count($results).' Callsigns' ?> für <?= htmlspecialchars($q) ?></div>
<table>
<thead><tr><th>Call</th><th>Datum</th><th>Band</th><th>Mode</th></tr></thead>
<tbody>
<?php foreach ($results as $call => $qsos): ?>
<?php foreach ($qsos as $e): ?>
<tr><td><?= htmlspecialchars($call) ?></td><td><?= fmt_date($e[0]) ?></td><td><?= htmlspecialchars($e[1]) ?></td><td><?= htmlspecialchars($e[2]) ?></td></tr>
<?php endforeach; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</body>
</html>
