<?php
// /stats.php - live referrer gauge from raw access logs (bhstats1, Nov 11 2026)
// sanitized production extract: hosts -> example.com, everything else verbatim
if (php_sapi_name() === 'cli') { exit; }
$CACHE = __DIR__ . '/.stats-cache.json';
$TTL = 600;
$data = null;
if (is_file($CACHE) && (time() - filemtime($CACHE)) < $TTL) { $data = json_decode(file_get_contents($CACHE), true); }
if (!is_array($data)) {
    $home = dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
    $cands = array_merge(glob($home . '/logs/*'), glob($home . '/access-logs/*'), glob(__DIR__ . '/logs/*'));
    $logs = array();
    foreach ($cands as $f) { if (is_file($f) && substr(basename($f),0,5)!=='error' && basename($f)!=='error_log' && strpos($f, 'example.com') !== false) { $logs[] = $f; } }
    $refs = array(); $paths = array(); $total = 0; $today = 0; $todayStr = date('d/M/Y');
$readTail = function($f, $take) {
    if (substr($f, -3) === '.gz') {
        $zh = gzopen($f, 'rb'); if (!$zh) { return ''; }
        $buf = ''; while (!gzeof($zh)) { $buf .= gzread($zh, 262144); }
        gzclose($zh);
        return $buf;
    }
    $fh = fopen($f, 'r'); if (!$fh) { return ''; }
    fseek($fh, max(0, filesize($f) - $take)); fgets($fh);
    $buf = stream_get_contents($fh); fclose($fh);
    return $buf;
};
    $budget = 8000000;
    rsort($logs);
    foreach ($logs as $f) {
        $sz = filesize($f); if (!$sz) { continue; }
        $take = min($sz, 3000000); $budget -= $take; if ($budget < 0 && $total > 0) { break; }
        $buf = $readTail($f, $take);
        foreach (explode("\n", $buf) as $line) {
            if (strpos($line, 'example.com') === false && strpos($line, '" 200 ') === false && strpos($line, '" 304 ') === false) { }
            if (trim($line) === '') { continue; }
            $total++;
            if (strpos($line, '[' . $todayStr . ':') !== false) { $today++; }
            if (preg_match('/^(\S+) \S+ \S+ \[[^\]]+\] "([A-Z]+) (\S+)/', $line, $m)) { $u = parse_url($m[3]); $p = ($u && isset($u['path'])) ? $u['path'] : $m[3]; if (isset($paths[$p])) { $paths[$p]++; } else { $paths[$p] = 1; } }
            if (preg_match('/"([^"]*)" "[^"]+"\s*$/', rtrim($line), $m2)) {
                $r = $m2[1];
                if ($r === '' || $r === '-') { $k = 'direct / none'; }
                else { $pu = parse_url($r); $k = isset($pu['host']) ? strtolower(preg_replace('/^www\./','',$pu['host'])) : $r; if (strpos($k,'example.com') !== false) { $k = 'internal'; } }
                if (isset($refs[$k])) { $refs[$k]++; } else { $refs[$k] = 1; }
            }
        }
    }
    arsort($refs); arsort($paths);
    $data = array('refs' => array_slice($refs, 0, 14, true), 'paths' => array_slice($paths, 0, 10, true), 'total' => $total, 'today' => $today, 'logs' => count($logs), 'ts' => time());
    file_put_contents($CACHE, json_encode($data));
}
$days = max(0, (int)ceil((strtotime('2026-11-24T12:00:00') - time()) / 86400));
$chans = array('coderlegion.com' => 'Coder Legion', 'dev.to' => 'dev.to', 'news.ycombinator.com' => 'Hacker News', 'reddit.com' => 'Reddit', 'google.' => 'Google', 'bing.com' => 'Bing', 'chatgpt.com' => 'ChatGPT', 'perplexity.ai' => 'Perplexity', 'linkedin.com' => 'LinkedIn', 'facebook.com' => 'Facebook', 'twitter.com' => 'X / Twitter', 'x.com' => 'X / Twitter');
$chanHits = array();
foreach ($data['refs'] as $k => $n) { foreach ($chans as $needle => $label) { if (strpos($k, $needle) !== false) { $chanHits[$label] = ($chanHits[$label] ?? 0) + $n; break; } } }
$maxRef = max(1, $data['refs'] ? max($data['refs']) : 1);
function h($s){ return htmlspecialchars($s, ENT_QUOTES); }
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>The Cockpit - Live Referral Stats | Bryan Hamilton</title>
<meta name="description" content="Live referrer breakdown for example.com, parsed from raw access logs on every visit - which channels actually send readers.">
<link rel="canonical" href="https://example.com/stats.php">
<meta property="og:type" content="website"><meta property="og:title" content="The Cockpit - Live Referral Stats"><meta property="og:description" content="Which channels actually send readers to example.com - parsed from raw access logs, no analytics script."><meta property="og:url" content="https://example.com/stats.php"><meta property="og:image" content="https://example.com/og.php?a=stats"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="The Cockpit - Live Referral Stats"><meta name="twitter:description" content="Raw access logs, parsed live. No analytics script."><meta name="twitter:image" content="https://example.com/og.php?a=stats">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"The Cockpit - Live Referral Stats","url":"https://example.com/stats.php","description":"Live referrer breakdown for example.com parsed from raw access logs.","author":{"@type":"Person","name":"Bryan Hamilton","url":"https://example.com/"},"isPartOf":{"@type":"WebSite","name":"Bryan Hamilton - Full-Stack Web Engineer","url":"https://example.com/"}}</script>
</head><body style="margin:0;background:#16181d;color:#e9edf1;font-family:ui-monospace,'SF Mono',Menlo,monospace">
<div style="max-width:820px;margin:0 auto;padding:36px 20px">
<div style="font-size:11px;letter-spacing:3px;color:#9aa3ae">EXAMPLE.COM</div>
<h1 style="font-size:30px;margin:8px 0 4px;color:#00d2ff">THE COCKPIT</h1>
<div style="color:#9aa3ae;font-size:13px;margin-bottom:26px">Referrers parsed live from raw access logs. No analytics script, no third party.</div>
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:28px">
<div style="flex:1;min-width:150px;background:#1d2027;border:1px solid #2b2f39;border-radius:10px;padding:16px"><div style="font-size:11px;color:#9aa3ae;letter-spacing:2px">PARSED LINES</div><div style="font-size:28px;color:#f26a21" class="bh-cnt" data-to="<?= (int)$data['total'] ?>"><?= number_format((int)$data['total']) ?></div></div>
<div style="flex:1;min-width:150px;background:#1d2027;border:1px solid #2b2f39;border-radius:10px;padding:16px"><div style="font-size:11px;color:#9aa3ae;letter-spacing:2px">TODAY</div><div style="font-size:28px;color:#00d2ff"><?= number_format((int)$data['today']) ?></div></div>
<div style="flex:1;min-width:150px;background:#1d2027;border:1px solid #2b2f39;border-radius:10px;padding:16px"><div style="font-size:11px;color:#9aa3ae;letter-spacing:2px">CL VERDICT IN</div><div style="font-size:28px;color:#f26a21"><?= $days ?> DAYS</div></div>
</div>
<?php if (empty($data['refs'])): ?>
<div style="background:#1d2027;border:1px solid #2b2f39;border-radius:10px;padding:22px;color:#ffb454">No raw access logs found. Enable them in cPanel &gt; Metrics &gt; Raw Access (checked home: <?= h(dirname($_SERVER['DOCUMENT_ROOT'] ?? __DIR__)) ?>). This page lights up as soon as logs exist.</div>
<?php else: ?>
<h2 style="font-size:15px;letter-spacing:2px;color:#9aa3ae;margin:0 0 12px">REFERRERS</h2>
<div style="background:#1d2027;border:1px solid #2b2f39;border-radius:10px;padding:8px 16px;margin-bottom:26px">
<?php foreach ($data['refs'] as $k => $n): ?>
<div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #2b2f39"><span style="width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px"><?= h($k) ?></span><span style="flex:1;height:10px;background:#16181d;border-radius:5px;overflow:hidden"><span style="display:block;height:10px;width:<?= round(100*$n/$maxRef) ?>%;background:linear-gradient(90deg,#00d2ff,#0e7490);border-radius:5px"></span></span><span style="width:64px;text-align:right;color:#00d2ff;font-size:13px"><?= number_format($n) ?></span></div>
<?php endforeach; ?>
</div>
<h2 style="font-size:15px;letter-spacing:2px;color:#9aa3ae;margin:0 0 12px">TOP ENTRY PAGES</h2>
<div style="background:#1d2027;border:1px solid #2b2f39;border-radius:10px;padding:8px 16px;margin-bottom:26px">
<?php foreach ($data['paths'] as $p => $n): ?>
<div style="display:flex;gap:10px;padding:7px 0;border-bottom:1px solid #2b2f39;font-size:13px"><span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#e9edf1"><?= h($p) ?></span><span style="color:#9aa3ae"><?= number_format($n) ?></span></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div style="color:#9aa3ae;font-size:12px">Snapshot taken <?= h(date('M j, g:ia', $data['ts'])) ?> - cached 10 min. Log files seen: <?= (int)$data['logs'] ?>. <a href="/" style="color:#00d2ff">example.com</a></div>
</div><style id="bhglow1">
/* bhglow1 - Cockpit glow-up */
body{background:radial-gradient(1200px 500px at 50% -100px,rgba(0,210,255,0.08),transparent 70%),#16181d !important}
h1{animation:bhglow-pulse 3.2s ease-in-out infinite}
@keyframes bhglow-pulse{0%,100%{text-shadow:0 0 14px rgba(0,210,255,0.55),0 0 40px rgba(0,210,255,0.2)}50%{text-shadow:0 0 24px rgba(0,210,255,0.85),0 0 64px rgba(0,210,255,0.35)}}
body>div>div{transition:transform .25s,border-color .25s,box-shadow .25s}
body>div>div:hover{transform:translateY(-2px);border-color:rgba(0,210,255,0.6) !important;box-shadow:0 6px 22px rgba(0,0,0,0.35),0 0 18px rgba(0,210,255,0.12)}
body>div>div:first-of-type{position:relative}
span[style*="linear-gradient(90deg,#00d2ff,#0e7490)"]{box-shadow:0 0 10px rgba(34,211,238,0.55);position:relative;overflow:hidden;transition:width 1.2s cubic-bezier(.2,.8,.2,1)}
span[style*="linear-gradient(90deg,#00d2ff,#0e7490)"]::after{content:"";position:absolute;top:0;bottom:0;width:40%;left:-60%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.45),transparent);animation:bhglow-sheen 2.6s ease-in-out infinite}
@keyframes bhglow-sheen{0%{left:-60%}55%,100%{left:130%}}
.bhglow-grow{width:0% !important}
div[style*="border-bottom:1px solid #2b2f39"]{transition:background .2s;border-radius:6px}
div[style*="border-bottom:1px solid #2b2f39"]:hover{background:rgba(0,210,255,0.05)}
div[style*="border-bottom:1px solid #2b2f39"]:hover span:first-child{color:#fff !important}
.bh-cnt,.bhglow-num{text-shadow:0 0 18px rgba(242,106,33,0.35)}
a[href="/"]:hover{text-shadow:0 0 12px rgba(0,210,255,0.8)}
@media (prefers-reduced-motion: reduce){h1{animation:none}span[style*="linear-gradient"]::after{display:none}}
</style>
<script>
/* bhglow1-tag - bar grow-in + count-up (self-contained) */
(function(){
  'use strict';
  if (window.__BHGLOW1) return; window.__BHGLOW1 = true;
  var RM = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fills = document.querySelectorAll('span[style*="linear-gradient(90deg,#00d2ff,#0e7490)"]');
  if (!RM && fills.length) {
    for (var i = 0; i < fills.length; i++) {
      var f = fills[i];
      var w = f.style.width;
      f.setAttribute('data-w', w);
      f.style.width = '0%';
    }
    requestAnimationFrame(function(){ requestAnimationFrame(function(){
      for (var j = 0; j < fills.length; j++) { fills[j].style.width = fills[j].getAttribute('data-w'); }
    }); });
  }
  function countUp(el) {
    var to = parseInt(el.getAttribute('data-to'), 10);
    if (!to || RM) return;
    var t0 = null, dur = 1400;
    function step(t) {
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur);
      var e = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(to * e).toLocaleString('en-US');
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var cnt = document.querySelector('.bh-cnt');
  if (cnt) countUp(cnt);
  var labs = document.querySelectorAll('div[style*="letter-spacing:2px"]');
  for (var k = 0; k < labs.length; k++) {
    if (labs[k].textContent.trim() === 'TODAY') {
      var num = labs[k].nextElementSibling;
      if (num) {
        num.className = (num.className || '') + ' bhglow-num';
        var v = parseInt(num.textContent.replace(/[^0-9]/g, ''), 10);
        if (v && !RM) {
          var t1 = null;
          function step2(t) {
            if (!t1) t1 = t;
            var p2 = Math.min(1, (t - t1) / 1100);
            var e2 = 1 - Math.pow(1 - p2, 3);
            num.textContent = Math.round(v * e2).toLocaleString('en-US');
            if (p2 < 1) requestAnimationFrame(step2);
          }
          requestAnimationFrame(step2);
        }
      }
    }
  }
})();
</script>
</body></html>
