<?php
// OE8YML/OE8JOTA Log Search — multi-tenant, server-side, no JS required
// Place at https://oeradio.at/logsearch.php

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: ALLOWALL');
header('Access-Control-Allow-Origin: *');

$stations = [
    'oe8yml' => [
        'station_id' => 1,
        'call'       => 'OE8YML',
        'op'         => 'Michael',
        'loc'        => 'JN66TO',
        'desc'       => 'Carinthia, Austria',
        'sig'        => 'https://achildrenmile.github.io/qrzprofiles/oe8yml-sig.png',
        'map'        => 'https://achildrenmile.github.io/qrzprofiles/qso-map.png',
        'index_url'  => 'https://achildrenmile.github.io/qrzprofiles/wavelog-index.json',
        'stats_url'  => 'https://achildrenmile.github.io/qrzprofiles/wavelog-stats.json',
        'index_cache'=> 'oe8yml_wavelog_index.json',
        'stats_cache'=> 'oe8yml_wavelog_stats.json',
    ],
    'oe8jota' => [
        'station_id' => 12,
        'call'       => 'OE8JOTA',
        'op'         => 'Pfadfindergruppe Porcia',
        'loc'        => 'JN66RS',
        'desc'       => 'Carinthia, Austria',
        'sig'        => '',
        'map'        => 'https://achildrenmile.github.io/qrzprofiles/qso-map-oe8jota.png',
        'index_url'  => 'https://achildrenmile.github.io/qrzprofiles/wavelog-index-oe8jota.json',
        'stats_url'  => 'https://achildrenmile.github.io/qrzprofiles/wavelog-stats-oe8jota.json',
        'index_cache'=> 'oe8jota_wavelog_index.json',
        'stats_cache'=> 'oe8jota_wavelog_stats.json',
        'theme'      => 'light',
    ],
];

$s_param = isset($_GET['s']) ? strtolower(trim($_GET['s'])) : 'oe8yml';
if (!array_key_exists($s_param, $stations)) $s_param = 'oe8yml';
$st = $stations[$s_param];

define('CACHE_TTL', 1800);

function fetch_cached($url, $cache_file) {
    $cache_path = sys_get_temp_dir() . '/' . $cache_file;
    if (file_exists($cache_path) && (time() - filemtime($cache_path)) < CACHE_TTL) {
        $data = file_get_contents($cache_path);
        if ($data) return json_decode($data, true);
    }
    $ctx = stream_context_create(['http' => [
        'timeout' => 15,
        'header' => "User-Agent: OE8YML-LogSearch/1.0\r\n",
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data) {
        file_put_contents($cache_path, $data);
        return json_decode($data, true);
    }
    return null;
}

$q = isset($_GET['call']) ? strtoupper(trim($_GET['call'])) : '';
$q = preg_replace('/[^A-Z0-9\/]/', '', $q);

// Always load stats for header
$stats = fetch_cached($st['stats_url'], $st['stats_cache']);

$results = [];
$error = '';

if ($q !== '') {
    $idx = fetch_cached($st['index_url'], $st['index_cache']);
    if ($idx === null) {
        $error = 'Index nicht verfügbar.';
    } else {
        foreach (($idx['q'] ?? []) as $call => $qsos) {
            if (strpos($call, $q) !== false) {
                $results[$call] = $qsos;
            }
        }
        ksort($results);
    }
}

function fmt_date($d) {
    if (strlen($d) === 8)
        return substr($d,6,2).'.'.substr($d,4,2).'.'.substr($d,2,2);
    return htmlspecialchars($d);
}
function fmt_time($t) {
    return strlen($t) >= 4 ? substr($t,0,2).':'.substr($t,2,2) : htmlspecialchars($t);
}
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Log Search &mdash; <?= htmlspecialchars($st['call']) ?></title>
<?php $light = ($st['theme'] ?? '') === 'light'; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0}
<?php if ($light): ?>
body{font-family:system-ui,-apple-system,sans-serif;background:#fafafa;color:#2d2d2d;padding:0.75rem;font-size:0.85rem}
.card{background:#fff;border:1px solid #ece6f0;border-radius:8px;overflow:hidden;margin-bottom:0.75rem}
.ch{padding:.35rem .6rem;background:#f3e8f7;font-size:.68rem;font-weight:600;color:#7b2d8e;letter-spacing:.05em;text-transform:uppercase}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:.3rem .5rem;color:#9a6aaa;font-size:.68rem;font-weight:500;border-bottom:1px solid #ece6f0;text-transform:uppercase;letter-spacing:.05em}
td{padding:.3rem .5rem;font-size:.8rem;border-bottom:1px solid #f5f0f8}
td:first-child{color:#2d2d2d;font-weight:700;font-family:'Courier New',monospace}
td:not(:first-child){color:#6a6a6a}
.sr{display:flex;gap:8px;margin-bottom:0.5rem}
input[name=call]{flex:1;background:#fff;border:1px solid #d4bde0;border-radius:8px;padding:8px 12px;color:#2d2d2d;font-size:0.85rem;outline:none}
button{background:#7b2d8e;border:none;border-radius:8px;padding:8px 16px;color:#fff;font-weight:600;cursor:pointer;white-space:nowrap}
.st{color:#9a6aaa;font-size:0.7rem;margin-bottom:0.5rem}
.upd{text-align:right;color:#c4aed0;font-size:0.65rem;margin-top:0.3rem}
a{color:#7b2d8e}
<?php else: ?>
body{font-family:system-ui,-apple-system,sans-serif;background:#0f172a;color:#f1f5f9;padding:0.75rem;font-size:0.85rem}
.card{background:#1e293b;border:1px solid #334155;border-radius:8px;overflow:hidden;margin-bottom:0.75rem}
.ch{padding:.35rem .6rem;background:#1a2744;font-size:.68rem;font-weight:600;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:.3rem .5rem;color:#64748b;font-size:.68rem;font-weight:500;border-bottom:1px solid #334155;text-transform:uppercase;letter-spacing:.05em}
td{padding:.3rem .5rem;font-size:.8rem;border-bottom:1px solid #1a2744}
td:first-child{color:#e2e8f0;font-weight:700;font-family:'Courier New',monospace}
td:not(:first-child){color:#94a3b8}
.sr{display:flex;gap:8px;margin-bottom:0.5rem}
input[name=call]{flex:1;background:#1e293b;border:1px solid #334155;border-radius:8px;padding:8px 12px;color:#f1f5f9;font-size:0.85rem;outline:none}
button{background:#3b82f6;border:none;border-radius:8px;padding:8px 16px;color:#fff;font-weight:600;cursor:pointer;white-space:nowrap}
.st{color:#64748b;font-size:0.7rem;margin-bottom:0.5rem}
.upd{text-align:right;color:#334155;font-size:0.65rem;margin-top:0.3rem}
<?php endif; ?>
</style>
</head>
<body>

<?php if ($st['map']): ?>
<div class="card" style="margin-bottom:0.75rem">
<div class="ch">QSO Map &mdash; <?= htmlspecialchars($st['call']) ?></div>
<img src="<?= htmlspecialchars($st['map']) ?>" style="width:100%;display:block">
</div>
<?php endif; ?>

<?php if ($stats): ?>
<div class="card">
<div class="ch">Letzte QSOs</div>
<table>
<thead><tr><th>Datum</th><th>UTC</th><th>Call</th><th>Band</th><th>Mode</th></tr></thead>
<tbody>
<?php foreach (($stats['recent'] ?? []) as $i => $q2):
    $bg = ($i % 2 === 0) ? ($light ? ' style="background:#f5f0fb"' : ' style="background:#172032"') : '';
?>
<tr<?= $bg ?>><td style="font-family:inherit;<?= $light ? 'color:#7b2d8e' : 'color:#94a3b8' ?>;font-weight:400"><?= fmt_date($q2['d']) ?></td><td><?= fmt_time($q2['t']) ?></td><td><?= htmlspecialchars($q2['c']) ?></td><td><?= htmlspecialchars($q2['b']) ?></td><td><?= htmlspecialchars($q2['m']) ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="upd">aktualisiert <?= htmlspecialchars($stats['updated'] ?? '') ?> &bull; <a href="https://wavelog.oeradio.at" style="color:#334155">Wavelog</a></div>
<?php endif; ?>

<div style="margin-top:0.75rem">
<form class="sr" method="get" action="">
  <input type="hidden" name="s" value="<?= htmlspecialchars($s_param) ?>">
  <input name="call" type="text" placeholder="Callsign suchen..." value="<?= htmlspecialchars($q) ?>" autocomplete="off" spellcheck="false" autofocus>
  <button type="submit">Suchen</button>
</form>

<?php if ($q === ''): ?>
<div class="st">Callsign eingeben und Enter drücken</div>
<?php elseif ($error): ?>
<div class="st"><?= htmlspecialchars($error) ?></div>
<?php elseif (empty($results)): ?>
<div class="st">Kein QSO mit <strong style="color:#f1f5f9"><?= htmlspecialchars($q) ?></strong> gefunden.</div>
<?php else: ?>
<div class="st"><?= count($results) === 1 ? '1 Callsign' : count($results).' Callsigns' ?> für <strong style="color:#f1f5f9"><?= htmlspecialchars($q) ?></strong></div>
<div class="card">
<div class="ch"><?= count($results) === 1 ? '1 Callsign' : count($results).' Callsigns' ?> für <?= htmlspecialchars($q) ?></div>
<table>
<thead><tr><th>Call</th><th>Datum</th><th>Band</th><th>Mode</th><th></th></tr></thead>
<tbody>
<?php foreach ($results as $call => $qsos): ?>
<?php foreach ($qsos as $e): ?>
<?php $qsl = 'https://oeradio.at/qsl-card.php?s='.urlencode($s_param).'&call='.urlencode($call).'&date='.urlencode($e[0]).'&band='.urlencode($e[1]).'&mode='.urlencode($e[2]).'&rst_s='.urlencode($e[3] ?? '').'&rst_r='.urlencode($e[4] ?? ''); ?>
<tr><td><?= htmlspecialchars($call) ?></td><td><?= fmt_date($e[0]) ?></td><td><?= htmlspecialchars($e[1]) ?></td><td><?= htmlspecialchars($e[2]) ?></td><td><a href="<?= $qsl ?>" target="_blank" style="color:#3b82f6;font-size:.68rem;border:1px solid #1e3a5f;padding:2px 7px;border-radius:4px;white-space:nowrap;text-decoration:none">QSL ↓</a></td></tr>
<?php endforeach; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</body>
</html>
