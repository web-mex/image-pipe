<?php
// index.php - lokale Mini-UI für ImageMagick (Folder->Folder)
declare(strict_types=1);

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function listFiles(string $dir): array {
  if (!is_dir($dir)) return [];
  $files = glob($dir . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE);
  return $files ? array_map('basename', $files) : [];
}

// Projektbasierte Ordner (sicherer für Webserver)
$projectRoot = __DIR__;
$defaultInput  = $projectRoot . DIRECTORY_SEPARATOR . 'input';
$defaultOutput = $projectRoot . DIRECTORY_SEPARATOR . 'output';

$log = [];
$ran = false;
$uploadedFiles = [];

$input  = $_POST['input']  ?? $defaultInput;
$output = $_POST['output'] ?? $defaultOutput;
$maxEdge = (int)($_POST['maxEdge'] ?? 1600);
$quality = (int)($_POST['quality'] ?? 85);
$format  = $_POST['format'] ?? 'webp'; // webp|jpg|both

// Grenzen, damit nix völlig kaputt geht
$maxEdge = max(100, min($maxEdge, 20000));
$quality = max(1, min($quality, 100));

// Datei-Upload verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['uploads']['name'][0])) {
  $ran = true;
  
  // Output-Ordner sicherstellen
  if (!is_dir($output)) {
    if (!mkdir($output, 0777, true)) {
      $log[] = "❌ Output-Ordner konnte nicht erstellt werden: $output";
    }
  }
  
  if (!is_dir($input)) {
    if (!mkdir($input, 0777, true)) {
      $log[] = "❌ Input-Ordner konnte nicht erstellt werden: $input";
    }
  }
  
  // Upload verarbeiten
  if (empty($log)) {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
    $maxFileSize  = 50 * 1024 * 1024; // 50MB
    $uploadCount  = 0;
    
    foreach ($_FILES['uploads']['tmp_name'] as $idx => $tmp) {
      if (empty($tmp)) continue;
      
      $filename = basename($_FILES['uploads']['name'][$idx]);
      $fileExt  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
      $fileMime = mime_content_type($tmp);
      $fileSize = $_FILES['uploads']['size'][$idx];
      
      // Validierung
      if (!in_array($fileExt, $allowedExts)) {
        $log[] = "❌ Dateityp nicht erlaubt: $filename (nur JPG, PNG, WebP)";
        continue;
      }
      if (!in_array($fileMime, $allowedMimes)) {
        $log[] = "❌ MIME-Type ungültig: $filename";
        continue;
      }
      if ($fileSize > $maxFileSize) {
        $log[] = "❌ Datei zu groß: $filename (max 50MB)";
        continue;
      }
      
      $dest = $input . DIRECTORY_SEPARATOR . $filename;
      if (move_uploaded_file($tmp, $dest)) {
        $uploadCount++;
        $uploadedFiles[] = $filename;
      } else {
        $log[] = "❌ Upload fehlgeschlagen: $filename";
      }
    }
    
    if ($uploadCount > 0) {
      $log[] = "✅ $uploadCount Datei(en) hochgeladen.";
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES['uploads']['name'][0])) {
  $ran = true;

  if ($input === '' || $output === '') {
    $log[] = "❌ Bitte Input- und Output-Ordner angeben.";
  } elseif (!is_dir($input)) {
    $log[] = "❌ Input-Ordner existiert nicht: $input";
  } else {
    if (!is_dir($output)) {
      if (!mkdir($output, 0777, true)) {
        $log[] = "❌ Output-Ordner konnte nicht erstellt werden: $output";
      }
    }
  }

  // Prüfen ob magick oder convert verfügbar ist
  if (empty($log)) {
    $imageMagickCmd = null;
    
    if (stripos(PHP_OS, 'WIN') === 0) {
      // Windows: prüfe magick oder convert
      @exec('where magick', $out, $rc);
      if ($rc === 0) $imageMagickCmd = 'magick';
      else {
        @exec('where convert', $out, $rc);
        if ($rc === 0) $imageMagickCmd = 'convert';
      }
    } else {
      // Unix/Linux: prüfe magick oder convert
      @exec('command -v magick', $out, $rc);
      if ($rc === 0) $imageMagickCmd = 'magick';
      else {
        @exec('command -v convert', $out, $rc);
        if ($rc === 0) $imageMagickCmd = 'convert';
      }
    }
    
    if (!$imageMagickCmd) {
      $log[] = "❌ ImageMagick nicht gefunden. Installiere: sudo apt install imagemagick (Linux) oder brew install imagemagick (Mac)";
    }
  }

  if (empty($log)) {
    $files = glob(rtrim($input, "/\\") . DIRECTORY_SEPARATOR . "*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE);
    if (!$files) {
      $log[] = "⚠️ Keine JPG/PNG Dateien gefunden im Ordner.";
    } else {
      $log[] = "✅ Gefunden: " . count($files) . " Datei(en).";

      foreach ($files as $i => $file) {
        $base = pathinfo($file, PATHINFO_FILENAME);

        // -resize 1600x1600> (wichtig: > darf nicht vom Shell interpretiert werden)
        // daher in Quotes mit escaped '>' -> wir verwenden ^> auf Windows, \> auf Unix
        $isWin = stripos(PHP_OS, 'WIN') === 0;
        $gt = $isWin ? '^>' : '\>';

        $in  = escapeshellarg($file);
        $cmd = $imageMagickCmd ?? 'magick';

        if ($format === 'jpg' || $format === 'both') {
          $outJ = rtrim($output, "/\\") . DIRECTORY_SEPARATOR . $base . ".jpg";
          $outJ = escapeshellarg($outJ);
          $cmdJ = "$cmd $in -resize {$maxEdge}x{$maxEdge}{$gt} -strip -quality $quality $outJ";
          @exec($cmdJ . " 2>&1", $oJ, $rcJ);
          if ($rcJ !== 0) $log[] = "❌ JPG Fehler: $file\n" . implode("\n", $oJ);
        }

        if ($format === 'webp' || $format === 'both') {
          $outW = rtrim($output, "/\\") . DIRECTORY_SEPARATOR . $base . ".webp";
          $outW = escapeshellarg($outW);
          $cmdW = "$cmd $in -resize {$maxEdge}x{$maxEdge}{$gt} -strip -quality $quality $outW";
          @exec($cmdW . " 2>&1", $oW, $rcW);
          if ($rcW !== 0) $log[] = "❌ WebP Fehler: $file\n" . implode("\n", $oW);
        }

        if (($i+1) % 25 === 0) $log[] = "… Fortschritt: " . ($i+1) . "/" . count($files);
      }

      $log[] = "🎉 Fertig.";
    }
  }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ImageMagick Mini-UI</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;max-width:900px;margin:24px auto;padding:0 16px;}
    .card{border:1px solid #ddd;border-radius:12px;padding:16px;box-shadow:0 2px 10px rgba(0,0,0,.04);margin-bottom:16px;}
    label{display:block;margin:10px 0 6px;font-weight:600;}
    input,select{width:100%;padding:10px;border:1px solid #ccc;border-radius:10px;box-sizing:border-box;}
    input[type="file"]{padding:8px;}
    button{margin-top:14px;padding:10px 14px;border:0;border-radius:10px;cursor:pointer;background:#0066cc;color:#fff;font-weight:600;}
    button:hover{background:#0052a3;}
    pre{white-space:pre-wrap;background:#0b1020;color:#e8ecff;padding:12px;border-radius:12px;max-height:400px;overflow:auto;}
    .hint{color:#555;font-size:14px;margin-top:4px}
    h2{margin-top:0;color:#333}
    ul{list-style:disc;}
    li{margin:4px 0;}
  </style>
</head>
<body>
  <h1>ImageMagick Web-Mex UI</h1>
  
  <div class="card">
    <h2>📤 Dateien hochladen</h2>
    <form method="post" enctype="multipart/form-data">
      <label>Bilder auswählen (JPG, PNG, WebP - max 50MB pro Datei)</label>
      <input type="file" name="uploads[]" multiple accept=".jpg,.jpeg,.png,.webp,image/*" required>
      <button type="submit">Hochladen</button>
    </form>
  </div>

  <div class="card">
    <h2>📁 Input-Ordner</h2>
    <div class="file-list">
      <?php
        $inputFiles = listFiles($input);
        if (empty($inputFiles)) {
          echo '<p style="color:#999;">Keine Dateien vorhanden</p>';
        } else {
          echo '<ul style="margin:0;padding-left:20px;">';
          foreach ($inputFiles as $file) {
            echo '<li>' . h($file) . '</li>';
          }
          echo '</ul>';
          echo '<p style="color:#666;font-size:12px;">Gesamt: ' . count($inputFiles) . ' Datei(en)</p>';
        }
      ?>
    </div>
  </div>

  <div class="card">
    <h2>📁 Output-Ordner</h2>
    <div class="file-list">
      <?php
        $outputFiles = listFiles($output);
        if (empty($outputFiles)) {
          echo '<p style="color:#999;">Keine Dateien vorhanden</p>';
        } else {
          echo '<ul style="margin:0;padding-left:20px;">';
          foreach ($outputFiles as $file) {
            echo '<li>' . h($file) . '</li>';
          }
          echo '</ul>';
          echo '<p style="color:#666;font-size:12px;">Gesamt: ' . count($outputFiles) . ' Datei(en)</p>';
        }
      ?>
    </div>
  </div>

  <div class="card">
    <h2>⚙️ Batch-Verarbeitung</h2>
    <form method="post">
      <label>Input-Ordner</label>
      <input name="input" value="<?=h($input)?>" placeholder="<?=h($defaultInput)?>">
      <div class="hint">Standard: <?=h($defaultInput)?></div>

      <label>Output-Ordner</label>
      <input name="output" value="<?=h($output)?>" placeholder="<?=h($defaultOutput)?>">
      <div class="hint">Standard: <?=h($defaultOutput)?></div>

      <label>Maximale Kantenlänge (px)</label>
      <input name="maxEdge" type="number" value="<?=h((string)$maxEdge)?>" min="100" max="20000">

      <label>Qualität (1–100)</label>
      <input name="quality" type="number" value="<?=h((string)$quality)?>" min="1" max="100">

      <label>Ausgabeformat</label>
      <select name="format">
        <option value="webp" <?= $format==='webp'?'selected':'' ?>>WebP</option>
        <option value="jpg"  <?= $format==='jpg'?'selected':'' ?>>JPG</option>
        <option value="both" <?= $format==='both'?'selected':'' ?>>Beides</option>
      </select>

      <button type="submit">Start</button>
    </form>
  </div>

  <?php if ($ran): ?>
    <div class="card">
      <h2>Log</h2>
      <pre><?=h(implode("\n\n", $log))?></pre>
    </div>
  <?php endif; ?>
</body>
</html>
