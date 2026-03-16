<?php
// OE8YML QSL Card Generator — outputs SVG
$call  = strtoupper(preg_replace('/[^A-Z0-9\/]/', '', $_GET['call'] ?? ''));
$date  = preg_replace('/[^0-9]/', '', $_GET['date'] ?? '');
$band  = htmlspecialchars(strtolower($_GET['band'] ?? ''), ENT_XML1);
$mode  = htmlspecialchars(strtoupper($_GET['mode'] ?? ''), ENT_XML1);
$time  = preg_replace('/[^0-9]/', '', $_GET['time'] ?? '');

if (!$call) { http_response_code(400); exit('Missing call'); }

// Format date
$date_fmt = $date;
if (strlen($date) === 8)
    $date_fmt = substr($date,6,2).'.'.substr($date,4,2).'.'.substr($date,0,4);

// Format time
$time_fmt = '';
if (strlen($time) >= 4) $time_fmt = substr($time,0,2).':'.substr($time,2,2).' UTC';

// RST defaults by mode
$rst_modes_ft = ['FT8','FT4','FT2','WSPR','JS8','MSK144'];
$rst_modes_cw = ['CW'];
if (in_array($mode, $rst_modes_ft))       $rst = '-10 dB';
elseif (in_array($mode, $rst_modes_cw))   $rst = '599';
else                                       $rst = '59';

// Shorten long callsigns for display
$call_size = strlen($call) > 8 ? 42 : (strlen($call) > 6 ? 52 : 62);

header('Content-Type: image/svg+xml');
header('Content-Disposition: attachment; filename="QSL-OE8YML-' . $call . ($date ? '-'.$date : '') . '.svg"');
header('Cache-Control: no-store');
?>
<svg width="590" height="410" viewBox="0 0 590 410" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#1e293b"/>
      <stop offset="100%" stop-color="#0f172a"/>
    </linearGradient>
    <linearGradient id="accent" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#3b82f6" stop-opacity="0"/>
      <stop offset="50%" stop-color="#3b82f6" stop-opacity="0.6"/>
      <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
    </linearGradient>
  </defs>

  <!-- Background -->
  <rect width="590" height="410" fill="url(#bg)" rx="10"/>

  <!-- Outer border -->
  <rect x="6" y="6" width="578" height="398" fill="none" stroke="#334155" stroke-width="1.5" rx="8"/>

  <!-- Top accent line -->
  <rect x="6" y="6" width="578" height="3" fill="url(#accent)" rx="2"/>

  <!-- Signal wave decoration (top right) -->
  <g opacity="0.07" stroke="#3b82f6" stroke-width="1.2" fill="none">
    <path d="M480,20 Q510,40 480,60 Q510,80 480,100"/>
    <path d="M500,15 Q535,40 500,65 Q535,90 500,115"/>
    <path d="M520,10 Q560,40 520,70 Q560,100 520,130"/>
    <path d="M540,5 Q585,40 540,75 Q585,110 540,145"/>
  </g>

  <!-- "CONFIRMING QSO WITH" -->
  <text x="295" y="48" text-anchor="middle"
    font-family="'Courier New',monospace" font-size="11"
    fill="#475569" letter-spacing="4">CONFIRMING QSO WITH</text>

  <!-- Their callsign -->
  <text x="295" y="<?= 48 + 20 + $call_size ?>" text-anchor="middle"
    font-family="'Courier New',monospace" font-size="<?= $call_size ?>"
    font-weight="bold" fill="#3b82f6" letter-spacing="6"><?= $call ?></text>

  <!-- Divider -->
  <rect x="40" y="155" width="510" height="1" fill="#334155"/>

  <!-- QSO details boxes -->
  <?php
  $fields = [
      ['DATE', $date_fmt ?: '—'],
      ['BAND', $band ?: '—'],
      ['MODE', $mode ?: '—'],
      ['RST',  $rst],
  ];
  if ($time_fmt) {
      array_splice($fields, 1, 0, [['TIME', $time_fmt]]);
  }
  $count = count($fields);
  $box_w = 100;
  $total_w = $count * $box_w + ($count - 1) * 12;
  $start_x = (590 - $total_w) / 2;
  foreach ($fields as $i => $f):
      $bx = $start_x + $i * ($box_w + 12);
  ?>
  <rect x="<?= $bx ?>" y="170" width="<?= $box_w ?>" height="64" fill="#0f172a" rx="6" stroke="#334155" stroke-width="1"/>
  <text x="<?= $bx + $box_w/2 ?>" y="191" text-anchor="middle"
    font-family="'Courier New',monospace" font-size="9.5"
    fill="#475569" letter-spacing="2"><?= $f[0] ?></text>
  <text x="<?= $bx + $box_w/2 ?>" y="218" text-anchor="middle"
    font-family="'Courier New',monospace" font-size="17"
    font-weight="bold" fill="#f1f5f9"><?= $f[1] ?></text>
  <?php endforeach; ?>

  <!-- Divider 2 -->
  <rect x="40" y="252" width="510" height="1" fill="#1e293b"/>

  <!-- DE label -->
  <text x="295" y="285" text-anchor="middle"
    font-family="'Courier New',monospace" font-size="11"
    fill="#475569" letter-spacing="4">73 DE</text>

  <!-- OE8YML callsign -->
  <text x="295" y="330" text-anchor="middle"
    font-family="'Courier New',monospace" font-size="46"
    font-weight="bold" fill="#f1f5f9" letter-spacing="8">OE8YML</text>

  <!-- Subtitle -->
  <text x="295" y="355" text-anchor="middle"
    font-family="system-ui,sans-serif" font-size="12"
    fill="#475569">Michael · JN66TO · Carinthia, Austria</text>

  <!-- Bottom bar -->
  <rect x="6" y="387" width="578" height="17" fill="#0f172a" rx="0"/>
  <rect x="6" y="401" width="578" height="3" fill="#0f172a" rx="0"/>
  <text x="295" y="399" text-anchor="middle"
    font-family="system-ui,sans-serif" font-size="10"
    fill="#334155" letter-spacing="1">QSL · wavelog.oeradio.at · oeradio.at</text>

  <!-- Bottom border fix -->
  <rect x="6" y="395" width="578" height="9" fill="none" stroke="#334155" stroke-width="0"/>
</svg>
