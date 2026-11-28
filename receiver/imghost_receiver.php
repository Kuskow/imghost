<?php
/**
 * ImgHost External Receiver v1.0.1 by Kuskow
 */

ini_set('display_errors', 0);
error_reporting(0);

define('CONFIG_FILE', __DIR__ . '/imghost_config.php');
define('IMGHOST_RECEIVER_ACTIVE', true);
define('LIMITS_DIR', sys_get_temp_dir() . '/kuskow_imghost_limits');
define('TICKET_TTL', 86400);
define('DIM_MARGIN', 5);

function silent_reject() { http_response_code(403); exit; }

function load_imghost_config() {
    if (!file_exists(CONFIG_FILE)) return null;
    $cfg = @include CONFIG_FILE;
    return is_array($cfg) ? $cfg : null;
}

/**
 * Resize (if larger than $max_dim), strip EXIF/Display P3/polyglots via Imagick, and save clean JPEG.
 */
function save_clean_jpeg($tmp, $dst, $max_dim, $max_bytes) {
    if (!extension_loaded('imagick') || !class_exists('Imagick')) return false;

    $info = @getimagesize($tmp);
    $is_ok_jpeg = ($info !== false && $info[2] === IMAGETYPE_JPEG && $info[0] <= $max_dim && $info[1] <= $max_dim && filesize($tmp) <= $max_bytes);

    try {
        $img = new Imagick($tmp);
        if ($img->getNumberImages() > 1) {
            $img = $img->coalesceImages();
            $img->setIteratorIndex(0);
            $img = $img->getImage();
        }
        if ($img->getImageWidth() > 12000 || $img->getImageHeight() > 12000) { $img->clear(); return false; }

        $img->autoOrient();
        if (!empty($img->getImageProfiles('icc', true))) {
            $img->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        }
        if ($img->getImageAlphaChannel()) {
            $img->setImageBackgroundColor('#FFFFFF');
            $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        }
        $img->stripImage();
        $img->setImageFormat('jpeg');

        if ($img->getImageWidth() > $max_dim || $img->getImageHeight() > $max_dim) {
            $img->resizeImage($max_dim, $max_dim, Imagick::FILTER_LANCZOS, 1, true);
            $is_ok_jpeg = false;
        }
        if ($is_ok_jpeg) {
            $ok = $img->writeImage($dst);
            $img->clear();
            return (bool) $ok;
        }

        $img->setSamplingFactors(['1x1', '1x1', '1x1']);
        for ($q = 92; $q >= 60; $q -= 6) {
            $img->setImageCompressionQuality($q);
            $blob = $img->getImageBlob();
            if (strlen($blob) <= $max_bytes || $q <= 62) {
                $img->clear();
                return @file_put_contents($dst, $blob, LOCK_EX) !== false;
            }
        }
        $img->clear();
    } catch (Throwable $e) {}

    return false;
}

$action = $_POST['action'] ?? '';

if ($action === 'status') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'         => true,
        'configured' => (load_imghost_config() !== null),
        'imagick'    => (extension_loaded('imagick') && class_exists('Imagick')),
    ]);
    exit;
}

if ($action === 'pair') {
    header('Content-Type: application/json; charset=utf-8');
    if (file_exists(CONFIG_FILE)) {
        http_response_code(403);
        echo json_encode(['paired' => false, 'error' => 'imghost_config.php already exists.']);
        exit;
    }

    // Require Imagick extension on the image hosting server before pairing
    if (!extension_loaded('imagick') || !class_exists('Imagick')) {
        http_response_code(400);
        echo json_encode([
            'paired' => false,
            'error'  => 'PHP extension "imagick" (ImageMagick) is required on the image hosting server, but it is currently disabled. Please enable "imagick" in your hosting control panel (PHP extensions) or ask your hosting provider to activate it free of charge, then try pairing again.'
        ]);
        exit;
    }

    $secret    = trim((string) ($_POST['secret'] ?? ''));
    $forum_url = trim((string) ($_POST['forum_url'] ?? ''));
    if (strlen($secret) < 32 || !filter_var($forum_url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['paired' => false, 'error' => 'Invalid secret key or forum URL.']);
        exit;
    }
    $p = parse_url($forum_url);
    $origin = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . (!empty($p['port']) ? ':' . $p['port'] : '');
    $cfg = [
        'secret'       => $secret,
        'forum_origin' => $origin,
        'max_daily'    => max(1, min(10000, (int) ($_POST['max_daily'] ?? 500))),
        'max_dim'      => max(256, min(4096, (int) ($_POST['max_dim'] ?? 1024))),
        'max_kb'       => max(100, min(10240, (int) ($_POST['max_kb'] ?? 1536))),
        'paired_at'    => date('c'),
    ];
    $code = "<?php\nif (!defined('IMGHOST_RECEIVER_ACTIVE')) { http_response_code(403); exit; }\nreturn " . var_export($cfg, true) . ";\n";
    if (@file_put_contents(CONFIG_FILE, $code, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['paired' => false, 'error' => 'Cannot write imghost_config.php.']);
        exit;
    }
    @chmod(CONFIG_FILE, 0600);
    echo json_encode(['paired' => true]);
    exit;
}

$cfg = load_imghost_config();
if ($cfg === null) silent_reject();

header('Access-Control-Allow-Origin: ' . $cfg['forum_origin']);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') silent_reject();

// 1. Verify HMAC ticket
$parts = explode(':', (string) ($_POST['ticket'] ?? ''));
if (count($parts) !== 3) silent_reject();
$user_id   = (int) $parts[0];
$timestamp = (int) $parts[1];
if ($user_id <= 1 || $timestamp <= 0 || (time() - $timestamp) > TICKET_TTL || ($timestamp - time()) > 300) silent_reject();
if (!hash_equals(hash_hmac('sha256', $user_id . ':' . $timestamp, $cfg['secret']), $parts[2])) silent_reject();

// 2. Collect candidate files (ignore broken or >25MB files)
if (empty($_FILES['images']['tmp_name']) || !is_array($_FILES['images']['tmp_name'])) silent_reject();
$files = [];
foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
    $err  = (int) ($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
    $size = (int) ($_FILES['images']['size'][$i] ?? 0);
    if (!empty($tmp) && is_uploaded_file($tmp) && $err === UPLOAD_ERR_OK && $size > 0 && $size <= 26214400) {
        $files[] = ['tmp' => $tmp, 'name' => $_FILES['images']['name'][$i] ?? 'image.jpg'];
    }
}
if (empty($files)) { header('Content-Type: application/json'); echo json_encode(['links' => []]); exit; }

// 3. Enforce daily per-user quota
if (!is_dir(LIMITS_DIR)) @mkdir(LIMITS_DIR, 0755, true);
$today = date('Ymd');
if (mt_rand(1, 20) === 1) {
    foreach (glob(LIMITS_DIR . '/u_*_*.cnt') as $old) {
        if (strpos($old, '_' . $today . '.cnt') === false) @unlink($old);
    }
}
$fp = @fopen(LIMITS_DIR . '/u_' . $user_id . '_' . $today . '.cnt', 'c+');
if ($fp) {
    flock($fp, LOCK_EX);
    $cur = (int) stream_get_contents($fp);
    $rem = (int) $cfg['max_daily'] - $cur;
    if ($rem <= 0) { flock($fp, LOCK_UN); fclose($fp); silent_reject(); }
    if (count($files) > $rem) $files = array_slice($files, 0, $rem);
    ftruncate($fp, 0); rewind($fp); fwrite($fp, (string) ($cur + count($files))); fflush($fp);
    flock($fp, LOCK_UN); fclose($fp);
}

// 4. Process & save valid files
$sub_dir = date('Ym') . '/';
if (!is_dir(__DIR__ . '/' . $sub_dir)) {
    if (!@mkdir(__DIR__ . '/' . $sub_dir, 0755, true)) { http_response_code(500); exit; }
    @file_put_contents(__DIR__ . '/' . $sub_dir . 'index.html', '<html />');
}

$max_bytes = (int) $cfg['max_kb'] * 1024;
$max_dim   = (int) $cfg['max_dim'] + DIM_MARGIN;
$base_url  = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://')
           . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

$links = [];
foreach ($files as $f) {
    $name = preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ_\-\.]/u', '', pathinfo($f['name'], PATHINFO_FILENAME)) ?: 'img';
    $rel  = $sub_dir . base_convert(str_replace('.', '', microtime(true)), 10, 36) . '_' . $name . '.jpg';
    if (save_clean_jpeg($f['tmp'], __DIR__ . '/' . $rel, $max_dim, $max_bytes)) {
        @chmod(__DIR__ . '/' . $rel, 0644);
        $links[] = $base_url . $rel;
    }
}

header('Content-Type: application/json');
echo json_encode(['links' => $links]);

