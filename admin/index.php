<?php
/** Gallery admin dashboard: upload + manage (edit / delete / drag-reorder). */
require __DIR__ . '/inc/store.php';
require_login();

$items = manifest_load();
$flashOk  = isset($_GET['ok']);
$flashErr = isset($_GET['err']) ? (string) $_GET['err'] : '';

$title = 'Gallery manager';
require __DIR__ . '/inc/head.php';
?>
<header class="admin-topbar">
  <div class="admin-topbar__inner">
    <a class="brand" href="/"><img class="brand__logo" src="/assets/img/logo.svg" alt="CCFM" width="150" height="35"></a>
    <span class="admin-topbar__title">Gallery manager</span>
    <div class="admin-topbar__actions">
      <a class="btn btn--outline btn--sm" href="/gallery" target="_blank" rel="noopener">View gallery ↗</a>
      <form method="post" action="/admin/logout.php" class="inline-form">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--sm">Sign out</button>
      </form>
    </div>
  </div>
</header>

<main class="admin-main container">
  <?php if ($flashOk): ?><div class="alert alert--ok">Saved.</div><?php endif; ?>
  <?php if ($flashErr !== ''): ?><div class="alert alert--err"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

  <!-- Upload -->
  <section class="admin-card">
    <h2>Add a photo</h2>
    <form method="post" action="/admin/upload.php" enctype="multipart/form-data" class="upload-form">
      <?= csrf_field() ?>
      <div class="field">
        <label for="image">Image file <span class="req">*</span></label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" required>
        <p class="form-note">JPEG, PNG or WebP, up to 10 MB. Large images are resized automatically.</p>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="category">Sector <span class="req">*</span></label>
          <select id="category" name="category" required>
            <?php foreach (CAT_LABELS as $slug => $label): ?>
              <option value="<?= $slug ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="caption">Caption</label>
          <input type="text" id="caption" name="caption" maxlength="160" placeholder="e.g. Terminal facility management">
        </div>
      </div>
      <div class="field">
        <label for="alt">Alt text (for accessibility &amp; SEO)</label>
        <input type="text" id="alt" name="alt" maxlength="160" placeholder="Describes the photo; defaults to the caption if left blank">
      </div>
      <button type="submit" class="btn btn--accent">Upload photo</button>
    </form>
  </section>

  <!-- Manage -->
  <section class="admin-card">
    <div class="admin-card__head">
      <h2>Photos <span class="count">(<?= count($items) ?>)</span></h2>
      <p class="form-note">Drag a tile to reorder. Changes to order save automatically.</p>
    </div>

    <?php if (!$items): ?>
      <p class="empty">No photos yet — upload one above.</p>
    <?php else: ?>
      <ul class="tile-list" id="tile-list" data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
        <?php foreach ($items as $it): ?>
          <li class="tile" draggable="true" data-id="<?= htmlspecialchars($it['id'], ENT_QUOTES) ?>">
            <div class="tile__handle" title="Drag to reorder" aria-hidden="true">⋮⋮</div>
            <img class="tile__thumb" src="<?= htmlspecialchars($it['thumb'] ?: $it['file'], ENT_QUOTES) ?>" alt="" width="700" height="525" loading="lazy">
            <form method="post" action="/admin/update.php" class="tile__form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= htmlspecialchars($it['id'], ENT_QUOTES) ?>">
              <label>Sector
                <select name="category">
                  <?php foreach (CAT_LABELS as $slug => $label): ?>
                    <option value="<?= $slug ?>"<?= ($it['category'] ?? '') === $slug ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Caption
                <input type="text" name="caption" maxlength="160" value="<?= htmlspecialchars($it['caption'] ?? '', ENT_QUOTES) ?>">
              </label>
              <label>Alt text
                <input type="text" name="alt" maxlength="160" value="<?= htmlspecialchars($it['alt'] ?? '', ENT_QUOTES) ?>">
              </label>
              <div class="tile__actions">
                <button type="submit" class="btn btn--sm">Save</button>
              </div>
            </form>
            <form method="post" action="/admin/delete.php" class="tile__delete" onsubmit="return confirm('Delete this photo? This cannot be undone.');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= htmlspecialchars($it['id'], ENT_QUOTES) ?>">
              <button type="submit" class="btn btn--sm btn--danger" aria-label="Delete photo">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
