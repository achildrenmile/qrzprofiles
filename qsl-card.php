<?php
// OE8YML QSL Card — HTML page with embedded SVG + download button
$call  = strtoupper(preg_replace('/[^A-Z0-9\/]/', '', $_GET['call'] ?? ''));
$date  = preg_replace('/[^0-9]/', '', $_GET['date'] ?? '');
$band  = htmlspecialchars(strtolower($_GET['band'] ?? ''), ENT_XML1);
$mode  = htmlspecialchars(strtoupper($_GET['mode'] ?? ''), ENT_XML1);
$time  = preg_replace('/[^0-9]/', '', $_GET['time'] ?? '');

if (!$call) { http_response_code(400); exit('Missing call'); }

$date_fmt = $date;
if (strlen($date) === 8)
    $date_fmt = substr($date,6,2).'.'.substr($date,4,2).'.'.substr($date,2,2);

$time_fmt = '';
if (strlen($time) >= 4) $time_fmt = substr($time,0,2).':'.substr($time,2,2).' UTC';

$rst_s_raw = htmlspecialchars($_GET['rst_s'] ?? '', ENT_XML1);
$rst_r_raw = htmlspecialchars($_GET['rst_r'] ?? '', ENT_XML1);

$rst_ft = ['FT8','FT4','FT2','WSPR','JS8','MSK144'];
$rst_cw = ['CW'];
$rst_def = in_array($mode, $rst_ft) ? '-10 dB' : (in_array($mode, $rst_cw) ? '599' : '59');

$rst_s = $rst_s_raw ?: $rst_def;
$rst_r = $rst_r_raw ?: $rst_def;

$call_size = strlen($call) > 8 ? 42 : (strlen($call) > 6 ? 52 : 62);

$fields = [
    ['DATE',     $date_fmt ?: '—'],
    ['BAND',     $band ?: '—'],
    ['MODE',     $mode ?: '—'],
    ['RST SENT', $rst_s],
    ['RST RCVD', $rst_r],
];
if ($time_fmt) array_splice($fields, 1, 0, [['TIME', $time_fmt]]);
$count   = count($fields);
$box_w   = $count >= 5 ? 88 : 100;
$gap     = $count >= 5 ? 10 : 12;
$total_w = $count * $box_w + ($count - 1) * $gap;
$start_x = (590 - $total_w) / 2;

$filename = 'QSL-OE8YML-' . $call . ($date ? '-'.$date : '') . '.svg';

// Build SVG string
ob_start(); ?>
<svg id="qsl" width="590" height="410" viewBox="0 0 590 410" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#1e293b"/><stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
    <linearGradient id="acc" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#3b82f6" stop-opacity="0"/>
      <stop offset="50%" stop-color="#3b82f6" stop-opacity="0.6"/>
      <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
    </linearGradient>
  </defs>
  <rect width="590" height="410" fill="url(#bg)" rx="10"/>
  <rect x="6" y="6" width="578" height="398" fill="none" stroke="#334155" stroke-width="1.5" rx="8"/>
  <rect x="6" y="6" width="578" height="3" fill="url(#acc)" rx="2"/>
  <g opacity="0.07" stroke="#3b82f6" stroke-width="1.2" fill="none">
    <path d="M480,20 Q510,40 480,60 Q510,80 480,100"/>
    <path d="M500,15 Q535,40 500,65 Q535,90 500,115"/>
    <path d="M520,10 Q560,40 520,70 Q560,100 520,130"/>
    <path d="M540,5 Q585,40 540,75 Q585,110 540,145"/>
  </g>
  <text x="295" y="48" text-anchor="middle" font-family="'Courier New',monospace" font-size="11" fill="#475569" letter-spacing="4">CONFIRMING QSO WITH</text>
  <text x="295" y="<?= 48 + 20 + $call_size ?>" text-anchor="middle" font-family="'Courier New',monospace" font-size="<?= $call_size ?>" font-weight="bold" fill="#3b82f6" letter-spacing="6"><?= $call ?></text>
  <rect x="40" y="155" width="510" height="1" fill="#334155"/>
  <?php foreach ($fields as $i => $f):
      $bx = $start_x + $i * ($box_w + $gap); ?>
  <rect x="<?= $bx ?>" y="170" width="<?= $box_w ?>" height="64" fill="#0f172a" rx="6" stroke="#334155" stroke-width="1"/>
  <text x="<?= $bx + $box_w/2 ?>" y="191" text-anchor="middle" font-family="'Courier New',monospace" font-size="9.5" fill="#475569" letter-spacing="2"><?= $f[0] ?></text>
  <text x="<?= $bx + $box_w/2 ?>" y="218" text-anchor="middle" font-family="'Courier New',monospace" font-size="17" font-weight="bold" fill="#f1f5f9"><?= $f[1] ?></text>
  <?php endforeach; ?>
  <rect x="40" y="252" width="510" height="1" fill="#1e293b"/>
  <text x="295" y="285" text-anchor="middle" font-family="'Courier New',monospace" font-size="11" fill="#475569" letter-spacing="4">73 DE</text>
  <text x="295" y="330" text-anchor="middle" font-family="'Courier New',monospace" font-size="46" font-weight="bold" fill="#f1f5f9" letter-spacing="8">OE8YML</text>
  <text x="295" y="355" text-anchor="middle" font-family="system-ui,sans-serif" font-size="12" fill="#475569">Michael · JN66TO · Carinthia, Austria</text>
  <rect x="6" y="387" width="578" height="17" fill="#0f172a"/>
  <text x="295" y="399" text-anchor="middle" font-family="system-ui,sans-serif" font-size="10" fill="#334155" letter-spacing="1">QSL · wavelog.oeradio.at · oeradio.at</text>
</svg>
<?php
$svg = trim(ob_get_clean());
$svg_b64 = base64_encode($svg);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>QSL — OE8YML / <?= htmlspecialchars($call) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#f1f5f9;font-family:system-ui,sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem;gap:1.2rem}
svg{max-width:100%;height:auto;border-radius:10px}
.btns{display:flex;gap:10px;flex-wrap:wrap;justify-content:center}
button,a.btn{background:#3b82f6;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:.9rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
a.btn.pr{background:#1e293b;border:1px solid #334155;color:#94a3b8}
.hint{color:#475569;font-size:.75rem}
</style>
</head>
<body>
<?= $svg ?>
<div class="btns">
  <button onclick="dlSvg()">&#11015; Download SVG</button>
  <button onclick="window.print()">&#128438; Print</button>
</div>
<div class="hint">QSL card for <?= htmlspecialchars($call) ?> · <?= htmlspecialchars($date_fmt) ?> · <?= htmlspecialchars($band) ?> · <?= htmlspecialchars($mode) ?></div>
<script>
function dlSvg(){
  var a=document.createElement('a');
  a.href='data:image/svg+xml;base64,<?= $svg_b64 ?>';
  a.download='<?= addslashes($filename) ?>';
  a.click();
}
</script>
</body>
</html>
