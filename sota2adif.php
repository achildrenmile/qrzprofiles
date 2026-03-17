<?php
// SOTA CSV → ADIF Converter
// Accepts activator and chaser exports from sota.org

function parse_date(string $d): string {
    $p = explode('/', trim($d));
    return count($p) === 3 ? $p[2].$p[1].$p[0] : '';
}

function parse_time(string $t): string {
    return str_replace(':', '', trim($t));
}

function freq_to_band(string $freq): string {
    $f = (float) preg_replace('/[^0-9.]/', '', strtolower($freq));
    if ($f >= 1.8   && $f <= 2.0)    return '160M';
    if ($f >= 3.5   && $f <= 4.0)    return '80M';
    if ($f >= 7.0   && $f <= 7.3)    return '40M';
    if ($f >= 10.1  && $f <= 10.15)  return '30M';
    if ($f >= 14.0  && $f <= 14.35)  return '20M';
    if ($f >= 18.0  && $f <= 18.2)   return '17M';
    if ($f >= 21.0  && $f <= 21.45)  return '15M';
    if ($f >= 24.8  && $f <= 25.0)   return '12M';
    if ($f >= 28.0  && $f <= 29.7)   return '10M';
    if ($f >= 50.0  && $f <= 54.0)   return '6M';
    if ($f >= 70.0  && $f <= 72.0)   return '4M';
    if ($f >= 144.0 && $f <= 148.0)  return '2M';
    if ($f >= 420.0 && $f <= 450.0)  return '70CM';
    if ($f >= 1240  && $f <= 1300)   return '23CM';
    return strtoupper($freq);
}

function parse_rst(string $raw): array {
    $raw = trim($raw, " \t\"'");
    if ($raw === '') return ['', ''];

    // Labeled: s59 r57 or r47 s59 (any order)
    if (preg_match('/\bs(\d{2,3})/i', $raw, $sm) && preg_match('/\br(\d{2,3})/i', $raw, $rm))
        return [$sm[1], $rm[1]];

    // Two bare numbers
    preg_match_all('/\b(\d{2,3})\b/', $raw, $m);
    if (count($m[1]) >= 2) return [$m[1][0], $m[1][1]];
    if (count($m[1]) === 1) return [$m[1][0], $m[1][0]];

    return ['', ''];
}

function adif_field(string $tag, string $val): string {
    return $val !== '' ? "<{$tag}:".strlen($val).">{$val}" : '';
}

function adif_record(array $fields): string {
    $parts = [];
    foreach ($fields as $tag => $val)
        if ($val !== '') $parts[] = adif_field($tag, $val);
    return implode(' ', $parts) . " <EOR>\n";
}

function parse_csv_file(string $content): array {
    $records = [];
    $lines = preg_split('/\r?\n/', trim($content));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $row = str_getcsv($line);
        if (count($row) < 8 || trim($row[0]) !== 'V2') continue;

        $my_call = trim($row[1]);
        $col2    = trim($row[2]); // summit (activator) or empty (chaser)
        $date    = parse_date($row[3]);
        $time_on = parse_time($row[4]);
        $band    = freq_to_band($row[5]);
        $mode    = strtoupper(trim($row[6]));
        $call    = trim($row[7]);
        $col8    = isset($row[8]) ? trim($row[8]) : '';
        $rst_raw = isset($row[9]) ? trim($row[9]) : '';

        [$sent, $rcvd] = parse_rst($rst_raw);

        // Detect type: activator has summit in col2, chaser has it in col8
        $is_activator = $col2 !== '' && preg_match('/^[A-Z0-9]+\/[A-Z0-9]+-\d+$/i', $col2);

        $rec = [
            'CALL'             => $call,
            'QSO_DATE'         => $date,
            'TIME_ON'          => $time_on,
            'BAND'             => $band,
            'MODE'             => $mode,
            'RST_SENT'         => $sent ?: '59',
            'RST_RCVD'         => $rcvd ?: '59',
            'STATION_CALLSIGN' => $my_call,
        ];

        if ($is_activator) {
            $rec['MY_SIG']      = 'SOTA';
            $rec['MY_SIG_INFO'] = $col2;
        } else {
            $rec['SIG']      = 'SOTA';
            $rec['SIG_INFO'] = $col8;
        }

        $records[] = $rec;
    }
    return $records;
}

// ── Handle upload ────────────────────────────────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sota'])) {
    $all_records = [];
    $files = $_FILES['sota'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $content = file_get_contents($files['tmp_name'][$i]);
        if ($content === false) continue;
        $all_records = array_merge($all_records, parse_csv_file($content));
    }

    if (empty($all_records)) {
        $error = 'No valid SOTA QSOs found in the uploaded file(s).';
    } else {
        // Sort by date + time
        usort($all_records, fn($a,$b) =>
            strcmp($a['QSO_DATE'].$a['TIME_ON'], $b['QSO_DATE'].$b['TIME_ON'])
        );

        // Remove duplicates (same CALL + DATE + TIME + BAND + MODE)
        $seen = [];
        $unique = [];
        foreach ($all_records as $r) {
            $key = $r['CALL'].$r['QSO_DATE'].$r['TIME_ON'].$r['BAND'].$r['MODE'];
            if (!isset($seen[$key])) { $seen[$key] = true; $unique[] = $r; }
        }

        $adif  = "SOTA QSO log — converted by oeradio.at/sota2adif\n";
        $adif .= "<ADIF_VER:5>3.1.4 <PROGRAMID:17>oeradio.at/sota2adif <EOH>\n\n";
        foreach ($unique as $r) $adif .= adif_record($r);

        $filename = 'SOTA-' . date('Ymd') . '.adi';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: ' . strlen($adif));
        header('Cache-Control: no-store');
        echo $adif;
        exit;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SOTA → ADIF Converter · oeradio.at</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#f1f5f9;font-family:system-ui,sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:2rem;width:100%;max-width:520px}
h1{font-size:1.25rem;font-weight:700;margin-bottom:.3rem;color:#f1f5f9}
.sub{color:#64748b;font-size:.85rem;margin-bottom:1.5rem}
.drop{border:2px dashed #334155;border-radius:8px;padding:2rem;text-align:center;cursor:pointer;transition:border-color .2s;margin-bottom:1rem;position:relative}
.drop:hover,.drop.over{border-color:#3b82f6}
.drop input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.drop-icon{font-size:2rem;margin-bottom:.5rem;color:#475569}
.drop-label{color:#94a3b8;font-size:.9rem}
.drop-label strong{color:#3b82f6}
.file-list{list-style:none;margin-bottom:1rem;display:flex;flex-direction:column;gap:.3rem}
.file-list li{background:#0f172a;border:1px solid #334155;border-radius:6px;padding:.4rem .75rem;font-size:.8rem;color:#94a3b8;display:flex;justify-content:space-between}
.file-list li span{color:#f1f5f9}
button[type=submit]{width:100%;background:#3b82f6;color:#fff;border:none;border-radius:8px;padding:.75rem;font-size:1rem;font-weight:600;cursor:pointer}
button[type=submit]:disabled{opacity:.4;cursor:default}
.error{background:#450a0a;border:1px solid #7f1d1d;color:#fca5a5;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem}
.info{color:#475569;font-size:.75rem;margin-top:1rem;text-align:center}
.badge{display:inline-block;background:#0f2336;border:1px solid #1e3a5f;color:#7dd3fc;border-radius:4px;padding:1px 6px;font-size:.7rem;font-family:'Courier New',monospace}
</style>
</head>
<body>
<div class="card">
  <h1>SOTA → ADIF Converter</h1>
  <p class="sub">Upload one or multiple SOTA.org CSV exports (activator &amp; chaser) — get a single ADIF back.</p>

  <?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="form">
    <div class="drop" id="drop">
      <input type="file" name="sota[]" id="files" multiple accept=".csv,.txt">
      <div class="drop-icon">📂</div>
      <div class="drop-label">Drop files here or <strong>browse</strong></div>
      <div style="color:#475569;font-size:.75rem;margin-top:.3rem">Activator &amp; chaser CSV files from sota.org</div>
    </div>
    <ul class="file-list" id="file-list"></ul>
    <button type="submit" id="btn" disabled>Convert &amp; Download ADIF</button>
  </form>

  <p class="info">
    RST format auto-detected &nbsp;·&nbsp;
    <span class="badge">s59 r57</span>
    <span class="badge">59 59</span>
    <span class="badge">r47 s59</span>
    all supported<br>
    Activator &amp; chaser type detected automatically
  </p>
</div>

<script>
const input = document.getElementById('files');
const list  = document.getElementById('file-list');
const btn   = document.getElementById('btn');
const drop  = document.getElementById('drop');

function updateList() {
  list.innerHTML = '';
  if (!input.files.length) { btn.disabled = true; return; }
  [...input.files].forEach(f => {
    const li = document.createElement('li');
    const kb = (f.size/1024).toFixed(1);
    li.innerHTML = `<span>${f.name}</span><span>${kb} KB</span>`;
    list.appendChild(li);
  });
  btn.disabled = false;
}

input.addEventListener('change', updateList);

drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('over'); });
drop.addEventListener('dragleave', () => drop.classList.remove('over'));
drop.addEventListener('drop', e => {
  e.preventDefault();
  drop.classList.remove('over');
  const dt = new DataTransfer();
  [...e.dataTransfer.files].forEach(f => dt.items.add(f));
  input.files = dt.files;
  updateList();
});
</script>
</body>
</html>
