<?php
/**
 * ImgHost External Receiver v1.0.0 by Kuskow
 *
 * - Trust-On-First-Use (TOFU) pairing via imghost_config.php
 * - HMAC-SHA256 ticket verification (no reverse HTTP call needed)
 * - Strict firewall: file size, pixel dimensions, JPEG type, NO EXIF metadata (silent 403 drop on violation)
 * - Daily per-user upload quota
 */

ini_set('display_errors', 0);
error_reporting(0);

define('CONFIG_FILE', __DIR__ . '/imghost_config.php');
define('IMGHOST_RECEIVER_ACTIVE', true);
define('LIMITS_DIR', sys_get_temp_dir() . '/kuskow_imghost_limits');
define('TICKET_TTL', 86400); // 24 hours ticket validity (matches long forum post sessions)
define('DIM_MARGIN', 5);     // +5px rounding allowance for HTML5 Canvas

// Helper: silent drop without response body
function silent_reject()
{
    http_response_code(403);
    exit;
}

// Helper: check if binary JPEG contains EXIF/APP1 marker (0xFFE1)
function has_exif_metadata($file_path)
{
    $fp = @fopen($file_path, 'rb');
    if (!$fp) {
        return true;
    }
    $header = fread($fp, 65536); // Read first 64 KB
    fclose($fp);

    if ($header === false || strlen($header) < 4) {
        return true;
    }

    // Must start with JPEG SOI marker 0xFFD8
    if (substr($header, 0, 2) !== "\xFF\xD8") {
        return true;
    }

    // Check for APP1 (EXIF / XMP) marker 0xFFE1
    if (strpos($header, "\xFF\xE1") !== false) {
        return true;
    }

    return false;
}

// Helper: load config if exists
function load_imghost_config()
{
    if (!file_exists(CONFIG_FILE)) {
        return null;
    }
    $cfg = @include CONFIG_FILE;
    return is_array($cfg) ? $cfg : null;
}

$action = $_POST['action'] ?? '';

// --- MODE 1: STATUS CHECK ---
if ($action === 'status') {
    $cfg = load_imghost_config();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'configured' => ($cfg !== null)]);
    exit;
}

// --- MODE 2: TRUST-ON-FIRST-USE PAIRING FROM PHPBB ACP ---
if ($action === 'pair') {
    header('Content-Type: application/json');

    // Strictly forbid overwriting if config file already exists
    if (file_exists(CONFIG_FILE)) {
        http_response_code(403);
        echo json_encode([
            'paired' => false,
            'error'  => 'Configuration file imghost_config.php already exists on receiver.'
        ]);
        exit;
    }

    $secret    = trim((string) ($_POST['secret'] ?? ''));
    $forum_url = trim((string) ($_POST['forum_url'] ?? ''));
    $max_daily = max(1, min(10000, (int) ($_POST['max_daily'] ?? 500)));
    $max_dim   = max(256, min(4096, (int) ($_POST['max_dim'] ?? 1024)));
    $max_kb    = max(100, min(10240, (int) ($_POST['max_kb'] ?? 1536)));

    if (strlen($secret) < 32 || !filter_var($forum_url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode([
            'paired' => false,
            'error'  => 'Invalid secret key or forum URL.'
        ]);
        exit;
    }

    $parsed = parse_url($forum_url);
    $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    if (!empty($parsed['port'])) {
        $origin .= ':' . $parsed['port'];
    }

    $config_array = [
        'secret'       => $secret,
        'forum_origin' => $origin,
        'max_daily'    => $max_daily,
        'max_dim'      => $max_dim,
        'max_kb'       => $max_kb,
        'paired_at'    => date('c'),
    ];

    $php_code = "<?php\nif (!defined('IMGHOST_RECEIVER_ACTIVE')) { http_response_code(403); exit; }\nreturn " . var_export($config_array, true) . ";\n";

    if (@file_put_contents(CONFIG_FILE, $php_code, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode([
            'paired' => false,
            'error'  => 'Cannot write imghost_config.php (check directory write permissions).'
        ]);
        exit;
    }

    @chmod(CONFIG_FILE, 0600);
    echo json_encode(['paired' => true]);
    exit;
}

// --- MODE 3: CLIENT IMAGE UPLOAD ---
$cfg = load_imghost_config();

if ($cfg === null) {
    silent_reject();
}

// CORS headers strictly for paired forum origin
header('Access-Control-Allow-Origin: ' . $cfg['forum_origin']);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    silent_reject();
}

// 1. Verify HMAC ticket (user_id:timestamp:signature)
$ticket = (string) ($_POST['ticket'] ?? '');
$parts = explode(':', $ticket);
if (count($parts) !== 3) {
    silent_reject();
}

list($user_id_raw, $timestamp_raw, $received_sig) = $parts;
$user_id = (int) $user_id_raw;
$timestamp = (int) $timestamp_raw;

if ($user_id <= 1 || $timestamp <= 0) {
    silent_reject();
}

if ((time() - $timestamp) > TICKET_TTL || ($timestamp - time()) > 300) {
    silent_reject();
}

$expected_sig = hash_hmac('sha256', $user_id . ':' . $timestamp, $cfg['secret']);
if (!hash_equals($expected_sig, $received_sig)) {
    silent_reject();
}

// 2. Validate incoming files array
if (empty($_FILES['images']['tmp_name']) || !is_array($_FILES['images']['tmp_name'])) {
    silent_reject();
}

$incoming_files = [];
foreach ($_FILES['images']['tmp_name'] as $idx => $tmp_name) {
    if (empty($tmp_name) || !is_uploaded_file($tmp_name)) {
        continue;
    }
    $incoming_files[] = [
        'tmp_name' => $tmp_name,
        'name'     => $_FILES['images']['name'][$idx] ?? 'image.jpg',
        'size'     => (int) ($_FILES['images']['size'][$idx] ?? 0),
        'error'    => (int) ($_FILES['images']['error'][$idx] ?? UPLOAD_ERR_NO_FILE),
    ];
}

if (empty($incoming_files)) {
    silent_reject();
}

// 3. Strict Firewall: validate EVERY file before saving anything; reject whole batch silently on any violation
$max_bytes = (int) $cfg['max_kb'] * 1024;
$max_dim   = (int) $cfg['max_dim'] + DIM_MARGIN;

foreach ($incoming_files as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        silent_reject();
    }
    if ($file['size'] <= 0 || $file['size'] > $max_bytes) {
        silent_reject();
    }

    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        silent_reject();
    }

    list($width, $height, $type) = $image_info;

    // Must be genuine JPEG within pixel limits
    if ($type !== IMAGETYPE_JPEG || $width <= 0 || $height <= 0 || $width > $max_dim || $height > $max_dim) {
        silent_reject();
    }

    // Must NOT contain EXIF metadata (client-side Canvas strips EXIF)
    if (has_exif_metadata($file['tmp_name'])) {
        silent_reject();
    }
}

// 4. Enforce daily per-user quota
if (!is_dir(LIMITS_DIR)) {
    @mkdir(LIMITS_DIR, 0755, true);
}

$today = date('Ymd');
// Probabilistic cleanup of old quota files (5% chance per request)
if (mt_rand(1, 20) === 1 && is_dir(LIMITS_DIR)) {
    foreach (glob(LIMITS_DIR . '/u_*_*.cnt') as $old_file) {
        if (strpos($old_file, '_' . $today . '.cnt') === false) {
            @unlink($old_file);
        }
    }
}

$quota_file = LIMITS_DIR . '/u_' . $user_id . '_' . $today . '.cnt';
$fp_quota = @fopen($quota_file, 'c+');
if ($fp_quota) {
    flock($fp_quota, LOCK_EX);
    $current_count = (int) stream_get_contents($fp_quota);
    if (($current_count + count($incoming_files)) > (int) $cfg['max_daily']) {
        flock($fp_quota, LOCK_UN);
        fclose($fp_quota);
        silent_reject();
    }
    ftruncate($fp_quota, 0);
    rewind($fp_quota);
    fwrite($fp_quota, (string) ($current_count + count($incoming_files)));
    fflush($fp_quota);
    flock($fp_quota, LOCK_UN);
    fclose($fp_quota);
}

// 5. Save validated files into YYYYMM/ subdirectory
$sub_dir = date('Ym') . '/';
if (!is_dir(__DIR__ . '/' . $sub_dir)) {
    if (@mkdir(__DIR__ . '/' . $sub_dir, 0755, true)) {
        @file_put_contents(__DIR__ . '/' . $sub_dir . 'index.html', '<html />');
    } else {
        http_response_code(500);
        exit;
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$base_url = $protocol . $_SERVER['HTTP_HOST'] . ($script_dir ? $script_dir . '/' : '/');

$uploaded_links = [];
foreach ($incoming_files as $file) {
    $clean_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $clean_name = preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ_\-\.]/u', '', $clean_name);
    if ($clean_name === '') {
        $clean_name = 'img';
    }

    $short_prefix = base_convert(str_replace('.', '', microtime(true)), 10, 36);
    $target_rel = $sub_dir . $short_prefix . '_' . $clean_name . '.jpg';

    if (@move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $target_rel)) {
        $uploaded_links[] = $base_url . $target_rel;
    }
}

header('Content-Type: application/json');
echo json_encode(['links' => $uploaded_links]);
exit;

