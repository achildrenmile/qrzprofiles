<?php
// OE8YML/OE8JOTA QSL Card — multi-tenant, HTML page with embedded SVG + download button

$stations = [
    'oe8yml' => [
        'call'  => 'OE8YML',
        'op'    => 'Michael',
        'loc'   => 'JN66TO',
        'desc'  => 'Carinthia, Austria',
        'sig'   => 'https://achildrenmile.github.io/qrzprofiles/oe8yml-sig.png',
        'photo' => 'https://oeradio.at/wp-content/uploads/2025/06/oe8yml_Michael_png_orig.jpg',
    ],
    'oe8jota' => [
        'call'  => 'OE8JOTA',
        'op'    => 'Pfadfindergruppe Porcia',
        'loc'   => 'JN66RS',
        'desc'  => 'Carinthia, Austria',
        'sig'   => '',
        'photo' => '',
    ],
    'oe8cni' => [
        'call'  => 'OE8CNI',
        'op'    => 'OE8CNI',
        'loc'   => 'JN67',
        'desc'  => 'Maria Rain, Kärnten',
        'sig'   => '',
        'photo' => '',
    ],
];

$s_param = isset($_GET['s']) ? strtolower(trim($_GET['s'])) : 'oe8yml';
if (!array_key_exists($s_param, $stations)) $s_param = 'oe8yml';
$st = $stations[$s_param];
$light  = ($s_param === 'oe8jota');
$kaernt = ($s_param === 'oe8cni');

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

$rst_ft  = ['FT8','FT4','FT2','WSPR','JS8','MSK144'];
$rst_cw  = ['CW'];
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

$filename = 'QSL-' . $st['call'] . '-' . $call . ($date ? '-'.$date : '') . '.svg';

// Color palette
if ($kaernt) {
    $c_bg1    = '#ffffff'; $c_bg2    = '#f1f5f9';
    $c_border = '#e2e8f0'; $c_acc    = '#1e40af';
    $c_sub    = '#64748b'; $c_sep    = '#e2e8f0';
    $c_box_bg = '#f8fafc'; $c_box_bd = '#e2e8f0';
    $c_lbl    = '#64748b'; $c_val    = '#1e293b';
    $c_footer = '#94a3b8'; $c_page   = '#fafafa';
    $c_btn    = '#2563eb'; $c_btn2bg = '#f1f5f9';
    $c_btn2bd = '#e2e8f0'; $c_btn2t  = '#2563eb';
    $c_hint   = '#64748b';
} elseif ($light) {
    $c_bg1    = '#ffffff';  $c_bg2    = '#f3e8f7';
    $c_border = '#d4bde0';  $c_acc    = '#7b2d8e';
    $c_sub    = '#9a6aaa';  $c_sep    = '#e8d5f0';
    $c_box_bg = '#faf5fc';  $c_box_bd = '#dcc8eb';
    $c_lbl    = '#9a6aaa';  $c_val    = '#3d1f52';
    $c_footer = '#c4aed0';  $c_page   = '#fafafa';
    $c_btn    = '#7b2d8e';  $c_btn2bg = '#f3e8f7';
    $c_btn2bd = '#d4bde0';  $c_btn2t  = '#7b2d8e';
    $c_hint   = '#9a6aaa';
} else {
    $c_bg1    = '#1e293b';  $c_bg2    = '#0f172a';
    $c_border = '#334155';  $c_acc    = '#3b82f6';
    $c_sub    = '#475569';  $c_sep    = '#334155';
    $c_box_bg = '#0f172a';  $c_box_bd = '#334155';
    $c_lbl    = '#475569';  $c_val    = '#f1f5f9';
    $c_footer = '#334155';  $c_page   = '#0f172a';
    $c_btn    = '#3b82f6';  $c_btn2bg = '#1e293b';
    $c_btn2bd = '#334155';  $c_btn2t  = '#94a3b8';
    $c_hint   = '#475569';
}

// Build SVG string
ob_start(); ?>
<svg id="qsl" width="590" height="410" viewBox="0 0 590 410" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="<?= $c_bg1 ?>"/><stop offset="100%" stop-color="<?= $c_bg2 ?>"/>
    </linearGradient>
    <linearGradient id="acc" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="<?= $c_acc ?>" stop-opacity="0"/>
      <stop offset="50%" stop-color="<?= $c_acc ?>" stop-opacity="0.6"/>
      <stop offset="100%" stop-color="<?= $c_acc ?>" stop-opacity="0"/>
    </linearGradient>
  </defs>
  <rect width="590" height="410" fill="url(#bg)" rx="10"/>
  <rect x="6" y="6" width="578" height="398" fill="none" stroke="<?= $c_border ?>" stroke-width="1.5" rx="8"/>
  <rect x="6" y="6" width="578" height="3" fill="url(#acc)" rx="2"/>
  <g opacity="0.07" stroke="<?= $c_acc ?>" stroke-width="1.2" fill="none">
    <path d="M480,20 Q510,40 480,60 Q510,80 480,100"/>
    <path d="M500,15 Q535,40 500,65 Q535,90 500,115"/>
    <path d="M520,10 Q560,40 520,70 Q560,100 520,130"/>
    <path d="M540,5 Q585,40 540,75 Q585,110 540,145"/>
  </g>
  <text x="295" y="48" text-anchor="middle" font-family="'Courier New',monospace" font-size="11" fill="<?= $c_sub ?>" letter-spacing="4">CONFIRMING QSO WITH</text>
  <text x="295" y="<?= 48 + 20 + $call_size ?>" text-anchor="middle" font-family="'Courier New',monospace" font-size="<?= $call_size ?>" font-weight="bold" fill="<?= $c_acc ?>" letter-spacing="6"><?= $call ?></text>
  <rect x="40" y="155" width="510" height="1" fill="<?= $c_sep ?>"/>
  <?php foreach ($fields as $i => $f):
      $bx = $start_x + $i * ($box_w + $gap); ?>
  <rect x="<?= $bx ?>" y="170" width="<?= $box_w ?>" height="64" fill="<?= $c_box_bg ?>" rx="6" stroke="<?= $c_box_bd ?>" stroke-width="1"/>
  <text x="<?= $bx + $box_w/2 ?>" y="191" text-anchor="middle" font-family="'Courier New',monospace" font-size="9.5" fill="<?= $c_lbl ?>" letter-spacing="2"><?= $f[0] ?></text>
  <text x="<?= $bx + $box_w/2 ?>" y="218" text-anchor="middle" font-family="'Courier New',monospace" font-size="17" font-weight="bold" fill="<?= $c_val ?>"><?= $f[1] ?></text>
  <?php endforeach; ?>
  <rect x="40" y="252" width="510" height="1" fill="<?= $c_sep ?>"/>
<?php if ($kaernt): ?>
  <!-- OE8CNI — centered, no photo -->
  <text x="295" y="280" text-anchor="middle" font-family="'Courier New',monospace" font-size="11" fill="<?= $c_sub ?>" letter-spacing="4">73 DE</text>
  <text x="295" y="325" text-anchor="middle" font-family="'Courier New',monospace" font-size="44" font-weight="bold" fill="<?= $c_acc ?>" letter-spacing="8"><?= htmlspecialchars($st['call'], ENT_XML1) ?></text>
  <text x="295" y="360" text-anchor="middle" font-family="system-ui,sans-serif" font-size="12" fill="<?= $c_sub ?>"><?= htmlspecialchars($st['loc'], ENT_XML1) ?> · <?= htmlspecialchars($st['desc'], ENT_XML1) ?></text>
<?php elseif ($st['sig']): ?>
  <?php if ($st['photo']): ?>
  <clipPath id="pc"><rect x="46" y="262" width="88" height="110" rx="6"/></clipPath>
  <image href="<?= htmlspecialchars($st['photo'], ENT_XML1) ?>" x="46" y="262" width="88" height="110" preserveAspectRatio="xMidYMid slice" clip-path="url(#pc)"/>
  <rect x="46" y="262" width="88" height="110" fill="none" stroke="<?= $c_border ?>" stroke-width="1" rx="6"/>
  <?php endif; ?>
  <text x="295" y="272" text-anchor="middle" font-family="'Courier New',monospace" font-size="11" fill="<?= $c_sub ?>" letter-spacing="4">73 DE</text>
  <text x="295" y="312" text-anchor="middle" font-family="'Courier New',monospace" font-size="40" font-weight="bold" fill="<?= $c_val ?>" letter-spacing="8"><?= htmlspecialchars($st['call'], ENT_XML1) ?></text>
  <image href="<?= htmlspecialchars($st['sig'], ENT_XML1) ?>" x="187" y="313" width="180" height="40" preserveAspectRatio="xMidYMid meet"/>
  <text x="295" y="373" text-anchor="middle" font-family="system-ui,sans-serif" font-size="11" fill="<?= $c_sub ?>"><?= htmlspecialchars($st['op'], ENT_XML1) ?> · <?= htmlspecialchars($st['loc'], ENT_XML1) ?> · <?= htmlspecialchars($st['desc'], ENT_XML1) ?></text>
<?php else: ?>
  <!-- JOTA-JOTI logo -->
  <clipPath id="jc"><rect x="46" y="267" width="100" height="100" rx="8"/></clipPath>
  <image href="https://cdn-bio.qrz.com/a/oe8jota/JOTA_JOTI_logo.jpg?p=dccdc2ae899f8caa8ad0fb703ded547a" x="46" y="267" width="100" height="100" preserveAspectRatio="xMidYMid meet" clip-path="url(#jc)"/>
  <text x="355" y="281" text-anchor="middle" font-family="'Courier New',monospace" font-size="11" fill="<?= $c_sub ?>" letter-spacing="4">73 DE</text>
  <text x="355" y="322" text-anchor="middle" font-family="'Courier New',monospace" font-size="34" font-weight="bold" fill="<?= $c_acc ?>" letter-spacing="6"><?= htmlspecialchars($st['call'], ENT_XML1) ?></text>
  <text x="355" y="348" text-anchor="middle" font-family="system-ui,sans-serif" font-size="11" fill="<?= $c_sub ?>"><?= htmlspecialchars($st['op'], ENT_XML1) ?> · <?= htmlspecialchars($st['loc'], ENT_XML1) ?></text>
  <text x="355" y="368" text-anchor="middle" font-family="system-ui,sans-serif" font-size="11" fill="<?= $c_sub ?>"><?= htmlspecialchars($st['desc'], ENT_XML1) ?></text>
<?php endif; ?>
  <text x="295" y="399" text-anchor="middle" font-family="system-ui,sans-serif" font-size="10" fill="<?= $c_footer ?>" letter-spacing="1">QSL · wavelog.oeradio.at · oeradio.at</text>
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
<title>QSL &mdash; <?= htmlspecialchars($st['call']) ?> / <?= htmlspecialchars($call) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:<?= $c_page ?>;color:<?= ($light || $kaernt) ? '#2d2d2d' : '#fff1f2' ?>;font-family:system-ui,sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem;gap:1.2rem}
svg{max-width:100%;height:auto;border-radius:10px;<?= $light ? 'box-shadow:0 4px 24px rgba(123,45,142,.12)' : ($kaernt ? 'box-shadow:0 4px 24px rgba(30,64,175,.1)' : '') ?>}
.btns{display:flex;gap:10px;flex-wrap:wrap;justify-content:center}
button,a.btn{background:<?= $c_btn ?>;color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:.9rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
a.btn.pr{background:<?= $c_btn2bg ?>;border:1px solid <?= $c_btn2bd ?>;color:<?= $c_btn2t ?>}
.hint{color:<?= $c_hint ?>;font-size:.75rem}
</style>
</head>
<body>
<?= $svg ?>
<div class="btns">
  <button onclick="dlSvg()">&#11015; Download SVG</button>
  <button onclick="window.print()"><svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:4px"><path d="M18 3H6v4H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2v3h12v-3h2a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2V3zm-2 0v4H8V3h8zm2 14H6v-4h12v4zm2-6a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>Print</button>
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
