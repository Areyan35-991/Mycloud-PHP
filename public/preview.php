<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_auth();

$uuid = trim($_GET['id'] ?? '');
if ($uuid === '') {
    redirect('/dashboard.php');
}

try {
    $file = Storage::findByUuid($uuid);
} catch (RuntimeException) {
    abort(404, 'File not found.');
}

$mime    = $file['mime_type'];
$isImage = str_starts_with($mime, 'image/');
$isVideo = str_starts_with($mime, 'video/');
$isAudio = str_starts_with($mime, 'audio/');

if (!$isImage && !$isVideo && !$isAudio) {
    // Not previewable — force download instead
    redirect('/download.php?id=' . urlencode($uuid));
}

$src = 'download.php?id=' . urlencode($uuid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($file['original_name']) ?> — <?= h(APP_NAME) ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #0a0b0f; --surface: #1a1d27; --border: #2a2d3a;
    --accent: #4f8ef7; --text: #e8eaf0; --muted: #6b7280;
    --font: 'SF Pro Display', -apple-system, 'Segoe UI', sans-serif;
  }
  body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }

  nav {
    background: rgba(26,29,39,.9); backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 1.5rem; height: 52px;
    display: flex; align-items: center; gap: 1rem;
    position: sticky; top: 0; z-index: 10;
  }
  .back { color: var(--accent); text-decoration: none; font-size: .9rem; }
  .back:hover { text-decoration: underline; }
  .nav-title { font-size: .9rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px; }
  .nav-spacer { flex: 1; }
  .btn-dl {
    background: var(--accent); color: #fff; text-decoration: none;
    border-radius: 7px; padding: .35rem .9rem; font-size: .85rem;
    font-family: var(--font); white-space: nowrap;
  }

  .preview-area {
    flex: 1; display: flex; align-items: center; justify-content: center;
    padding: 2rem;
  }

  img.preview {
    max-width: 100%; max-height: calc(100vh - 120px);
    border-radius: 10px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
    animation: pop .3s cubic-bezier(.22,1,.36,1);
  }
  video.preview, audio.preview {
    max-width: 100%; width: 900px;
    border-radius: 10px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
  }

  @keyframes pop { from { opacity:0; transform: scale(.97); } to { opacity:1; transform:none; } }

  .meta {
    text-align: center; padding: .75rem 1.5rem 1.5rem;
    font-size: .8rem; color: var(--muted);
  }
</style>
</head>
<body>

<nav>
  <a class="back" href="dashboard.php">← Back</a>
  <span class="nav-title"><?= h($file['original_name']) ?></span>
  <div class="nav-spacer"></div>
  <a class="btn-dl" href="<?= h($src) ?>&download=1" download="<?= h($file['original_name']) ?>">
    ⬇ Download
  </a>
</nav>

<div class="preview-area">
  <?php if ($isImage): ?>
    <img class="preview" src="<?= h($src) ?>" alt="<?= h($file['original_name']) ?>">
  <?php elseif ($isVideo): ?>
    <video class="preview" controls autoplay muted playsinline>
      <source src="<?= h($src) ?>" type="<?= h($mime) ?>">
      Your browser does not support this video format.
    </video>
  <?php elseif ($isAudio): ?>
    <audio class="preview" controls autoplay>
      <source src="<?= h($src) ?>" type="<?= h($mime) ?>">
    </audio>
  <?php endif; ?>
</div>

<div class="meta">
  <?= h($file['original_name']) ?> &middot;
  <?= h(Storage::humanSize((int)$file['size'])) ?> &middot;
  <?= h($mime) ?> &middot;
  Uploaded <?= h(substr($file['uploaded_at'], 0, 16)) ?>
</div>

</body>
</html>
