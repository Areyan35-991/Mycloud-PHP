<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_auth();

$files   = Storage::all();
$message = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard — <?= h(APP_NAME) ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:      #0f1117;
    --surface: #1a1d27;
    --surface2:#21253a;
    --border:  #2a2d3a;
    --accent:  #4f8ef7;
    --text:    #e8eaf0;
    --muted:   #6b7280;
    --success: #34d399;
    --danger:  #f87171;
    --warn:    #fbbf24;
    --radius:  12px;
    --font:    'SF Pro Display', -apple-system, 'Segoe UI', sans-serif;
    --mono:    'SF Mono', 'Fira Code', monospace;
  }

  body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* Grid bg */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background-image:
      linear-gradient(rgba(79,142,247,.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(79,142,247,.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none; z-index: 0;
  }

  /* ── Layout ── */
  .shell { position: relative; z-index: 1; display: flex; flex-direction: column; min-height: 100vh; }

  /* ── Nav ── */
  nav {
    background: rgba(26,29,39,.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 2rem;
    display: flex; align-items: center; gap: 1rem;
    height: 56px;
    position: sticky; top: 0; z-index: 100;
  }
  .nav-logo { display: flex; align-items: center; gap: .5rem; font-weight: 600; font-size: 1rem; }
  .nav-logo-icon { width: 28px; height: 28px; background: var(--accent); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
  .nav-spacer { flex: 1; }
  .nav-user { font-size: .8rem; color: var(--muted); }
  .btn-logout {
    font-family: var(--font); font-size: .8rem; color: var(--muted);
    background: none; border: 1px solid var(--border);
    border-radius: 6px; padding: .3rem .75rem; cursor: pointer;
    transition: color .15s, border-color .15s;
  }
  .btn-logout:hover { color: var(--danger); border-color: var(--danger); }

  /* ── Main ── */
  main { flex: 1; padding: 2rem; max-width: 1100px; margin: 0 auto; width: 100%; }

  /* ── Flash ── */
  .flash {
    border-radius: 8px; padding: .75rem 1rem; font-size: .875rem; margin-bottom: 1.5rem;
    animation: rise .3s ease both;
  }
  .flash.ok   { background: rgba(52,211,153,.1); border: 1px solid rgba(52,211,153,.3); color: var(--success); }
  .flash.err  { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: var(--danger); }

  @keyframes rise { from { opacity:0; transform: translateY(-8px); } to { opacity:1; transform:none; } }

  /* ── Upload zone ── */
  .upload-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    margin-bottom: 2rem;
  }
  .upload-card h2 { font-size: 1rem; font-weight: 600; margin-bottom: 1.25rem; }

  .dropzone {
    border: 2px dashed var(--border);
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    position: relative;
  }
  .dropzone.drag-over { border-color: var(--accent); background: rgba(79,142,247,.07); }
  .dropzone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
  .dropzone-icon { font-size: 2rem; margin-bottom: .5rem; }
  .dropzone-text { font-size: .9rem; color: var(--muted); }
  .dropzone-text strong { color: var(--accent); }

  .upload-progress { margin-top: 1rem; display: none; }
  .progress-bar-wrap { background: var(--border); border-radius: 99px; height: 6px; overflow: hidden; }
  .progress-bar { height: 100%; background: var(--accent); border-radius: 99px; width: 0; transition: width .2s; }
  .progress-label { font-size: .8rem; color: var(--muted); margin-top: .4rem; text-align: center; }

  .btn-upload {
    margin-top: 1rem;
    background: var(--accent); color: #fff;
    font-family: var(--font); font-size: .9rem; font-weight: 500;
    border: none; border-radius: 8px;
    padding: .65rem 1.5rem; cursor: pointer;
    transition: opacity .15s;
    display: none;
  }
  .btn-upload:hover { opacity: .85; }
  .btn-upload.visible { display: inline-block; }

  /* ── File table ── */
  .files-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .files-header {
    padding: 1.1rem 1.5rem;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border);
  }
  .files-header h2 { font-size: 1rem; font-weight: 600; }
  .file-count { font-size: .8rem; color: var(--muted); }

  table { width: 100%; border-collapse: collapse; }
  thead th {
    text-align: left; font-size: .75rem; color: var(--muted);
    text-transform: uppercase; letter-spacing: .06em;
    padding: .75rem 1.5rem; border-bottom: 1px solid var(--border);
    font-weight: 500;
  }
  tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: var(--surface2); }
  td { padding: .875rem 1.5rem; font-size: .9rem; vertical-align: middle; }

  .file-icon { font-size: 1.25rem; margin-right: .5rem; }
  .file-name { font-weight: 500; }
  .file-name a { color: var(--text); text-decoration: none; }
  .file-name a:hover { color: var(--accent); }
  .file-meta { font-size: .78rem; color: var(--muted); margin-top: 2px; }

  .badge {
    font-size: .7rem; border-radius: 4px; padding: .15rem .4rem;
    font-family: var(--mono);
  }
  .badge-img   { background: rgba(79,142,247,.15); color: var(--accent); }
  .badge-video { background: rgba(251,191,36,.15);  color: var(--warn); }
  .badge-doc   { background: rgba(52,211,153,.15);  color: var(--success); }
  .badge-other { background: var(--border); color: var(--muted); }

  .actions { display: flex; gap: .4rem; }
  .btn {
    font-family: var(--font); font-size: .78rem; border-radius: 6px;
    padding: .3rem .65rem; cursor: pointer; border: 1px solid var(--border);
    background: none; color: var(--text); text-decoration: none;
    display: inline-flex; align-items: center; gap: .25rem;
    transition: border-color .15s, color .15s, background .15s;
  }
  .btn:hover        { background: var(--surface2); border-color: var(--accent); color: var(--accent); }
  .btn-danger:hover { border-color: var(--danger); color: var(--danger); background: rgba(248,113,113,.08); }

  .empty-state { text-align: center; padding: 4rem 1rem; color: var(--muted); }
  .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; opacity: .5; }

  /* ── Share modal ── */
  .modal-backdrop {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.7); z-index: 200;
    align-items: center; justify-content: center;
  }
  .modal-backdrop.open { display: flex; }
  .modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 2rem; width: 100%; max-width: 480px;
    animation: rise .25s ease both;
  }
  .modal h3 { font-size: 1rem; font-weight: 600; margin-bottom: .5rem; }
  .modal p  { font-size: .85rem; color: var(--muted); margin-bottom: 1.25rem; }
  .copy-row {
    display: flex; gap: .5rem;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 8px; padding: .5rem;
  }
  .copy-row input {
    flex: 1; background: none; border: none; color: var(--text);
    font-family: var(--mono); font-size: .82rem; outline: none;
  }
  .btn-copy {
    background: var(--accent); color: #fff; border: none;
    border-radius: 6px; padding: .3rem .8rem;
    font-family: var(--font); font-size: .82rem; cursor: pointer;
  }
  .modal-close {
    margin-top: 1.25rem; background: none; border: 1px solid var(--border);
    color: var(--muted); border-radius: 6px; padding: .4rem 1rem;
    font-family: var(--font); font-size: .85rem; cursor: pointer;
  }
</style>
</head>
<body>
<div class="shell">

<!-- Nav -->
<nav>
  <div class="nav-logo">
    <div class="nav-logo-icon">☁</div>
    <?= h(APP_NAME) ?>
  </div>
  <div class="nav-spacer"></div>
  <span class="nav-user"><?= h($_SESSION['username'] ?? '') ?></span>
  <form method="POST" action="logout.php" style="display:inline">
    <?= csrf_field() ?>
    <button type="submit" class="btn-logout">Sign out</button>
  </form>
</nav>

<main>

  <?php if ($message): ?>
    <div class="flash <?= str_starts_with($message, 'Error') ? 'err' : 'ok' ?>">
      <?= h($message) ?>
    </div>
  <?php endif; ?>

  <!-- Upload -->
  <div class="upload-card">
    <h2>Upload a file</h2>
    <form id="upload-form" method="POST" action="upload.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="dropzone" id="dropzone">
        <input type="file" name="file" id="file-input" required>
        <div class="dropzone-icon">📂</div>
        <p class="dropzone-text"><strong>Click to browse</strong> or drag &amp; drop</p>
        <p class="dropzone-text" style="font-size:.78rem;margin-top:.25rem">
          Images, video, audio, PDF, archives — up to 500 MB
        </p>
      </div>
      <div class="upload-progress" id="upload-progress">
        <div class="progress-bar-wrap"><div class="progress-bar" id="progress-bar"></div></div>
        <p class="progress-label" id="progress-label">Uploading…</p>
      </div>
      <button type="submit" class="btn-upload" id="btn-upload">Upload</button>
    </form>
  </div>

  <!-- Files -->
  <div class="files-card">
    <div class="files-header">
      <h2>Your files</h2>
      <span class="file-count"><?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($files)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">🌤</div>
        <p>No files yet. Upload something!</p>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>Size</th>
          <th>Uploaded</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($files as $f): ?>
        <?php
          $mime    = $f['mime_type'];
          $isImage = str_starts_with($mime, 'image/');
          $isVideo = str_starts_with($mime, 'video/');
          $isAudio = str_starts_with($mime, 'audio/');
          $isPdf   = $mime === 'application/pdf';
          if ($isImage)      { $badge = 'badge-img';   $icon = '🖼'; $label = 'Image'; }
          elseif ($isVideo)  { $badge = 'badge-video'; $icon = '🎬'; $label = 'Video'; }
          elseif ($isAudio)  { $badge = 'badge-doc';   $icon = '🎵'; $label = 'Audio'; }
          elseif ($isPdf)    { $badge = 'badge-doc';   $icon = '📄'; $label = 'PDF';   }
          else               { $badge = 'badge-other'; $icon = '📦'; $label = explode('/', $mime)[1] ?? '?'; }
        ?>
        <tr>
          <td>
            <span class="file-icon"><?= $icon ?></span>
            <span class="file-name">
              <?php if ($isImage || $isVideo): ?>
                <a href="preview.php?id=<?= h($f['uuid']) ?>"><?= h($f['original_name']) ?></a>
              <?php else: ?>
                <?= h($f['original_name']) ?>
              <?php endif; ?>
            </span>
            <div class="file-meta"><?= h($f['uuid']) ?></div>
          </td>
          <td><span class="badge <?= $badge ?>"><?= h($label) ?></span></td>
          <td><?= h(Storage::humanSize((int)$f['size'])) ?></td>
          <td><?= h(substr($f['uploaded_at'], 0, 16)) ?></td>
          <td>
            <div class="actions">
              <a class="btn" href="download.php?id=<?= h($f['uuid']) ?>">⬇ Download</a>
              <?php if ($isImage || $isVideo): ?>
                <a class="btn" href="preview.php?id=<?= h($f['uuid']) ?>">👁 Preview</a>
              <?php endif; ?>
              <button class="btn" onclick="openShare('<?= h($f['uuid']) ?>', '<?= h(addslashes($f['original_name'])) ?>')">
                🔗 Share
              </button>
              <form method="POST" action="delete.php" style="display:inline"
                    onsubmit="return confirm('Delete <?= h(addslashes($f['original_name'])) ?>?')">
                <?= csrf_field() ?>
                <input type="hidden" name="uuid" value="<?= h($f['uuid']) ?>">
                <button type="submit" class="btn btn-danger">🗑 Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</main>
</div>

<!-- Share modal -->
<div class="modal-backdrop" id="share-modal">
  <div class="modal">
    <h3>Share link</h3>
    <p id="share-filename"></p>
    <div class="copy-row">
      <input type="text" id="share-url" readonly>
      <button class="btn-copy" onclick="copyShareUrl()">Copy</button>
    </div>
    <br>
    <button class="modal-close" onclick="closeShare()">Close</button>
  </div>
</div>

<script>
// ── Dropzone ──
const dropzone  = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');
const btnUpload = document.getElementById('btn-upload');

fileInput.addEventListener('change', () => {
  if (fileInput.files.length > 0) btnUpload.classList.add('visible');
});

['dragover','dragenter'].forEach(e => dropzone.addEventListener(e, ev => {
  ev.preventDefault(); dropzone.classList.add('drag-over');
}));
['dragleave','drop'].forEach(e => dropzone.addEventListener(e, () => {
  dropzone.classList.remove('drag-over');
}));
dropzone.addEventListener('drop', ev => {
  ev.preventDefault();
  if (ev.dataTransfer.files.length) {
    fileInput.files = ev.dataTransfer.files;
    btnUpload.classList.add('visible');
  }
});

// ── XHR upload with progress ──
document.getElementById('upload-form').addEventListener('submit', function(e) {
  e.preventDefault();
  if (!fileInput.files.length) return;

  const data = new FormData(this);
  const xhr  = new XMLHttpRequest();
  const prog = document.getElementById('upload-progress');
  const bar  = document.getElementById('progress-bar');
  const lbl  = document.getElementById('progress-label');

  prog.style.display = 'block';
  btnUpload.disabled = true;

  xhr.upload.onprogress = ev => {
    if (ev.lengthComputable) {
      const pct = Math.round(ev.loaded / ev.total * 100);
      bar.style.width = pct + '%';
      lbl.textContent = pct + '% — ' + formatBytes(ev.loaded) + ' / ' + formatBytes(ev.total);
    }
  };

  xhr.onload = () => {
    if (xhr.status === 200 || xhr.status === 302) {
      window.location.href = 'dashboard.php';
    } else {
      lbl.textContent = 'Upload failed. Please try again.';
      btnUpload.disabled = false;
    }
  };

  xhr.onerror = () => { lbl.textContent = 'Network error.'; btnUpload.disabled = false; };
  xhr.open('POST', 'upload.php');
  xhr.send(data);
});

function formatBytes(b) {
  const units = ['B','KB','MB','GB'];
  let i = 0;
  while (b >= 1024 && i < 3) { b /= 1024; i++; }
  return b.toFixed(1) + ' ' + units[i];
}

// ── Share modal ──
async function openShare(uuid, name) {
  document.getElementById('share-filename').textContent = name;
  document.getElementById('share-url').value = 'Generating link…';
  document.getElementById('share-modal').classList.add('open');

  const fd = new FormData();
  fd.append('uuid', uuid);
  fd.append('csrf_token', '<?= csrf_token() ?>');

  const res  = await fetch('share_create.php', { method: 'POST', body: fd });
  const data = await res.json();
  document.getElementById('share-url').value = data.url || 'Error';
}

function closeShare() {
  document.getElementById('share-modal').classList.remove('open');
}

function copyShareUrl() {
  const el = document.getElementById('share-url');
  el.select();
  navigator.clipboard.writeText(el.value).catch(() => document.execCommand('copy'));
}

document.getElementById('share-modal').addEventListener('click', function(e) {
  if (e.target === this) closeShare();
});
</script>
</body>
</html>
