<?php
/**
 * CCFM gallery admin — data store + image pipeline.
 * Manifest lives outside the web root; images live in the served docroot.
 * All uploads are re-encoded through GD (defeats disguised-payload uploads).
 */

require_once __DIR__ . '/auth.php';

const IMG_DIR    = '/home/ccfmltd/public_html/assets/img/gallery';
const THUMB_DIR  = IMG_DIR . '/thumbs';
const IMG_WEB    = '/assets/img/gallery';         // public URL prefix
const MANIFEST   = GALLERY_DATA_DIR . '/manifest.json';
const CATEGORIES = ['aviation', 'maritime', 'logistics', 'industrial', 'commercial'];
const CAT_LABELS = [
    'aviation'   => 'Aviation',
    'maritime'   => 'Maritime',
    'logistics'  => 'Warehousing & Logistics',
    'industrial' => 'Industrial',
    'commercial' => 'Commercial & Residential',
];
const MAX_UPLOAD = 10485760; // 10 MB
const MAX_EDGE   = 2000;     // px, longest edge of stored full image
const THUMB_EDGE = 700;      // px, longest edge of thumbnail
const CAP_MAX    = 160;      // chars

/** Load manifest sorted by order. Returns array of entries. */
function manifest_load() {
    if (!is_file(MANIFEST)) {
        return [];
    }
    $j = json_decode((string) file_get_contents(MANIFEST), true);
    if (!is_array($j)) {
        return [];
    }
    usort($j, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $j;
}

/** Atomically persist the manifest, renumbering `order` to match array order. */
function manifest_save(array $items) {
    $items = array_values($items);
    foreach ($items as $i => &$it) {
        $it['order'] = $i;
    }
    unset($it);
    if (!is_dir(GALLERY_DATA_DIR)) {
        @mkdir(GALLERY_DATA_DIR, 0750, true);
    }
    $tmp = MANIFEST . '.tmp';
    file_put_contents($tmp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    @chmod($tmp, 0644);
    rename($tmp, MANIFEST);
}

function valid_category($c) {
    return in_array($c, CATEGORIES, true);
}

function clean_text($s, $max = CAP_MAX) {
    $s = trim(preg_replace('/\s+/', ' ', (string) $s));
    if (mb_strlen($s) > $max) {
        $s = mb_substr($s, 0, $max);
    }
    return $s;
}

function ensure_dirs() {
    foreach ([IMG_DIR, THUMB_DIR] as $d) {
        if (!is_dir($d)) {
            @mkdir($d, 0755, true);
        }
    }
}

/**
 * Validate + store an uploaded image.
 * @return array [bool ok, array|string entry-or-error]
 */
function store_upload(array $file, string $category, string $caption, string $alt) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'No file received, or the upload failed.'];
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return [false, 'Invalid upload.'];
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > MAX_UPLOAD) {
        return [false, 'File is empty or larger than 10 MB.'];
    }

    $mime     = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $decoders = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png'  => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
    ];
    if (!isset($decoders[$mime])) {
        return [false, 'Only JPEG, PNG or WebP images are allowed.'];
    }

    $src = @$decoders[$mime]($file['tmp_name']);
    if (!$src) {
        return [false, 'That image could not be read (it may be corrupt).'];
    }

    // Honour EXIF orientation for JPEGs so portrait photos aren't sideways.
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        switch ($exif['Orientation'] ?? 1) {
            case 3: $src = imagerotate($src, 180, 0); break;
            case 6: $src = imagerotate($src, -90, 0); break;
            case 8: $src = imagerotate($src,  90, 0); break;
        }
    }

    ensure_dirs();
    $id    = bin2hex(random_bytes(8));
    $full  = IMG_DIR . '/' . $id . '.jpg';
    $thumb = THUMB_DIR . '/' . $id . '.jpg';

    if (!save_resized($src, $full, MAX_EDGE, 86)) {
        imagedestroy($src);
        return [false, 'Could not save the processed image.'];
    }
    save_resized($src, $thumb, THUMB_EDGE, 82);
    imagedestroy($src);
    @chmod($full, 0644);
    @chmod($thumb, 0644);

    $caption = clean_text($caption);
    $entry = [
        'id'       => $id,
        'file'     => IMG_WEB . '/' . $id . '.jpg',
        'thumb'    => IMG_WEB . '/thumbs/' . $id . '.jpg',
        'category' => valid_category($category) ? $category : 'commercial',
        'caption'  => $caption,
        'alt'      => clean_text($alt !== '' ? $alt : $caption),
        'order'    => PHP_INT_MAX,
    ];
    $items   = manifest_load();
    $items[] = $entry;
    manifest_save($items);
    return [true, $entry];
}

/** Re-encode $src into a JPEG at $destPath, downscaled to $maxEdge, flattened on white. */
function save_resized($src, string $destPath, int $maxEdge, int $quality) {
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1.0, $maxEdge / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = imagejpeg($dst, $destPath, $quality);
    imagedestroy($dst);
    return $ok;
}

function store_update(string $id, string $category, string $caption, string $alt) {
    $items = manifest_load();
    foreach ($items as &$it) {
        if (($it['id'] ?? '') === $id) {
            if (valid_category($category)) {
                $it['category'] = $category;
            }
            $it['caption'] = clean_text($caption);
            $it['alt']     = clean_text($alt !== '' ? $alt : $caption);
        }
    }
    unset($it);
    manifest_save($items);
}

function store_delete(string $id) {
    $items = manifest_load();
    $kept  = [];
    foreach ($items as $it) {
        if (($it['id'] ?? '') === $id) {
            // basename() guards against any path trickery in the manifest.
            @unlink(IMG_DIR . '/' . basename($it['file'] ?? ''));
            @unlink(THUMB_DIR . '/' . basename($it['thumb'] ?? ''));
        } else {
            $kept[] = $it;
        }
    }
    manifest_save($kept);
}

/** Reorder by an ordered list of ids; any not listed keep their relative order at the end. */
function store_reorder(array $ids) {
    $items = manifest_load();
    $byId  = [];
    foreach ($items as $it) {
        $byId[$it['id']] = $it;
    }
    $new = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $new[] = $byId[$id];
            unset($byId[$id]);
        }
    }
    foreach ($byId as $it) {
        $new[] = $it;
    }
    manifest_save($new);
}
