<?php
/**
 * Global helper functions used by controllers and views.
 */
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;

// ---------------------------------------------------------------------
// Configuration and environment
// ---------------------------------------------------------------------
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Current time as a DATETIME string in the application timezone. */
function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Read a value from the system_settings table (cached per request). */
function setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        foreach (Database::fetchAll('SELECT setting_key, setting_value FROM system_settings') as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings[$key] ?? $default;
}

// ---------------------------------------------------------------------
// Output escaping and URLs
// ---------------------------------------------------------------------
/** Escape text for HTML output. Use for EVERY value that came from users or the database. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL path, e.g. "/kvpm-e-foodhub/public" on XAMPP or "" on a virtual host. */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        // Only trust SCRIPT_NAME when it really is the front controller. PHP's built-in
        // server sets it to the requested path for some URLs, which must not become the base.
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_ends_with($script, '/index.php') ? rtrim(substr($script, 0, -strlen('/index.php')), '/') : '';
    }
    return $base;
}

function url(string $path = '/', array $query = []): string
{
    $query = array_filter($query, fn ($v) => $v !== null && $v !== '');
    $qs = $query ? '?' . http_build_query($query) : '';
    return base_path() . '/' . ltrim($path, '/') . $qs;
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return base_path() . '/assets/' . ltrim($path, '/') . '?v=' . $version;
}

function upload_url(?string $relative, string $fallback = 'images/placeholder-product.svg'): string
{
    if ($relative && preg_match('#^(products|shops|categories)/[A-Za-z0-9_\-]+\.(jpg|jpeg|png|webp|svg)$#', $relative)) {
        return base_path() . '/uploads/' . $relative;
    }
    return asset($fallback);
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

function current_path_with_query(): string
{
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    return current_path() . ($qs !== '' ? '?' . $qs : '');
}

/** Only allow redirects to local paths (prevents open redirects). */
function safe_local_path(?string $path, string $fallback = '/'): string
{
    if (!is_string($path) || $path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
        return $fallback;
    }
    return $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function back(string $fallback = '/'): never
{
    // Only follow the Referer header when it points at this same site.
    $parts = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $refHost = ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
    if (isset($parts['path']) && $refHost !== '' && $refHost === ($_SERVER['HTTP_HOST'] ?? '')) {
        $path = $parts['path'];
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . ltrim($path, '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        redirect(safe_local_path($path, $fallback));
    }
    redirect($fallback);
}

function abort(int $status, string $message = ''): never
{
    throw new HttpException($status, $message);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ---------------------------------------------------------------------
// Request input
// ---------------------------------------------------------------------
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query_int(string $key, int $default = 0, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
{
    $value = $_GET[$key] ?? null;
    if (!is_string($value) || !preg_match('/^\d+$/', $value)) {
        return $default;
    }
    return max($min, min($max, (int) $value));
}

/** Validate an ID from a URL segment. */
function route_id(string $value): int
{
    if (!preg_match('/^[1-9]\d{0,9}$/', $value)) {
        abort(404, 'Rekod tidak dijumpai.');
    }
    return (int) $value;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

// ---------------------------------------------------------------------
// Flash messages and old input
// ---------------------------------------------------------------------
function flash(string $type, string $message): void
{
    $messages = Session::get('_flash', []);
    $messages[] = ['type' => $type, 'message' => $message];
    Session::put('_flash', $messages);
}

function take_flash(): array
{
    return Session::pull('_flash', []);
}

/** Store submitted values and errors, then go back to the form. */
function back_with_errors(array $errors, string $fallback = '/', array $except = ['password', 'password_confirmation', 'current_password', '_token']): never
{
    $old = $_POST;
    foreach ($except as $key) {
        unset($old[$key]);
    }
    Session::put('_old', $old);
    Session::put('_errors', $errors);
    back($fallback);
}

function old(string $key, mixed $default = ''): string
{
    static $old = null;
    if ($old === null) {
        $old = Session::pull('_old', []);
    }
    $value = $old[$key] ?? $default;
    return is_scalar($value) ? (string) $value : '';
}

function errors(): array
{
    static $errors = null;
    if ($errors === null) {
        $errors = Session::pull('_errors', []);
    }
    return $errors;
}

function error_for(string $key): ?string
{
    return errors()[$key] ?? null;
}

function csrf_field(): string
{
    return Csrf::field();
}

// ---------------------------------------------------------------------
// Money and formatting
// ---------------------------------------------------------------------
/** Convert a DECIMAL string (e.g. "12.50") to integer sen to avoid float errors. */
function to_sen(string|int|float|null $amount): int
{
    return (int) round(((float) $amount) * 100);
}

function sen_to_decimal(int $sen): string
{
    return number_format($sen / 100, 2, '.', '');
}

function money(string|int|float|null $amount): string
{
    return 'RM' . number_format((float) $amount, 2);
}

function format_date(?string $datetime, bool $withTime = true): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    $months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'];
    $out = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('g:i a', $ts) : $out;
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    return match (true) {
        $diff < 60     => 'baru sahaja',
        $diff < 3600   => floor($diff / 60) . ' minit lalu',
        $diff < 86400  => floor($diff / 3600) . ' jam lalu',
        $diff < 604800 => floor($diff / 86400) . ' hari lalu',
        default        => format_date($datetime, false),
    };
}

/** Escape LIKE wildcards. Queries must use: LIKE :param ESCAPE '!' (works in MySQL and SQLite). */
function like_escape(string $text): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text);
}

function slugify(string $text): string
{
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $text !== '' ? substr($text, 0, 100) : 'item';
}

/** Generate a slug unique within $table.$column. Table/column come from code only. */
function unique_slug(string $table, string $text, ?int $ignoreId = null): string
{
    $base = slugify($text);
    $slug = $base;
    $n = 2;
    while (true) {
        $params = [$slug];
        $sql = "SELECT COUNT(*) FROM $table WHERE slug = ?";
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        if ((int) Database::value($sql, $params) === 0) {
            return $slug;
        }
        $slug = $base . '-' . $n++;
    }
}

// ---------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------
/** @return array{page:int, per_page:int, total:int, pages:int, offset:int} */
function paginate(int $total, int $perPage, ?int $page = null): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = $page ?? query_int('page', 1, 1);
    $page = min(max(1, $page), $pages);
    return [
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => $total,
        'pages'    => $pages,
        'offset'   => ($page - 1) * $perPage,
    ];
}

/** Keep current filters when building a link to another page. */
function url_with(array $changes): string
{
    $query = array_merge($_GET, $changes);
    foreach ($query as $k => $v) {
        if (!is_string($v) && !is_int($v)) {
            unset($query[$k]);
        }
    }
    return url(current_path(), $query);
}

// ---------------------------------------------------------------------
// Status labels (Malay) used across the interface
// ---------------------------------------------------------------------
function order_status_label(string $status): string
{
    return [
        'pending'   => 'Menunggu',
        'confirmed' => 'Disahkan',
        'preparing' => 'Sedang disediakan',
        'ready'     => 'Sedia diambil',
        'completed' => 'Selesai',
        'rejected'  => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ][$status] ?? $status;
}

function payment_status_label(string $status): string
{
    return [
        'unpaid'     => 'Belum dibayar',
        'paid'       => 'Dibayar',
        'refund_due' => 'Perlu bayaran balik',
        'void'       => 'Tidak berkenaan',
    ][$status] ?? $status;
}

function payment_method_label(string $method): string
{
    return [
        'cash'       => 'Tunai semasa ambil',
        'online_sim' => 'Bayaran dalam talian (simulasi)',
    ][$method] ?? $method;
}

function approval_label(string $status): string
{
    return [
        'pending'   => 'Menunggu semakan',
        'approved'  => 'Diluluskan',
        'rejected'  => 'Ditolak',
        'suspended' => 'Digantung',
        'active'    => 'Aktif',
        'open'      => 'Terbuka',
        'resolved'  => 'Selesai',
        'dismissed' => 'Ditolak',
        'published' => 'Diterbitkan',
        'hidden'    => 'Disembunyikan',
    ][$status] ?? $status;
}

function report_reason_label(string $reason): string
{
    return [
        'inaccurate' => 'Maklumat tidak tepat',
        'prohibited' => 'Barang tidak dibenarkan',
        'offensive'  => 'Kandungan tidak sesuai',
        'scam'       => 'Disyaki penipuan',
        'other'      => 'Lain-lain',
    ][$reason] ?? $reason;
}

function role_label(string $role): string
{
    return ['customer' => 'Pelanggan', 'seller' => 'Penjual', 'admin' => 'Pentadbir'][$role] ?? $role;
}

/** Badge colour family for any status value. */
function badge(string $status, ?string $label = null): string
{
    $tone = match ($status) {
        'completed', 'approved', 'paid', 'active', 'published', 'resolved', 'success' => 'success',
        'pending', 'unpaid', 'open' => 'warning',
        'confirmed', 'preparing' => 'info',
        'ready' => 'accent',
        'rejected', 'cancelled', 'suspended', 'failed', 'refund_due' => 'danger',
        default => 'neutral',
    };
    $label ??= match ($status) {
        'pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled' => order_status_label($status),
        'unpaid', 'paid', 'refund_due', 'void' => payment_status_label($status),
        'success' => 'Berjaya',
        'failed'  => 'Gagal',
        default => approval_label($status),
    };
    return '<span class="badge badge--' . $tone . '">' . e($label) . '</span>';
}

// ---------------------------------------------------------------------
// Icons — simple original stroke icons (24×24), no external dependency
// ---------------------------------------------------------------------
function icon(string $name, int $size = 20, string $class = ''): string
{
    $paths = [
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cart'     => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2"/><circle cx="10" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 2.9-6 6.5-6s6.5 2.5 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3c2.1.7 3.5 2.8 3.5 5.7"/>',
        'graduation-cap' => '<path d="m2 9 10-5 10 5-10 5Z"/><path d="M6 11v5c3.5 2.5 8.5 2.5 12 0v-5M22 9v6"/>',
        'store'    => '<path d="M3 9.5 4.6 4h14.8L21 9.5"/><path d="M3 9.5a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/><path d="M10 20v-4.5h4V20"/>',
        'package'  => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'bell'     => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15Z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        'heart'    => '<path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.2a4.3 4.3 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20Z"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'chevron-left'  => '<path d="m15 6-6 6 6 6"/>',
        'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16Z"/><path d="m13.5 6.5 4 4"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'archive'  => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11h14V8M10 12h4"/>',
        'eye'      => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'  => '<path d="M3 3l18 18M10.6 6a9.6 9.6 0 0 1 1.4-.1C18 5.9 21.5 12 21.5 12a17 17 0 0 1-2.6 3.4M6.6 6.7C3.9 8.4 2.5 12 2.5 12S6 18.5 12 18.5a9 9 0 0 0 4.4-1.1"/><path d="M9.9 10a3 3 0 0 0 4.2 4.2"/>',
        'logout'   => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M4.2 5.2l2.1 2.1M17.7 16.7l2.1 2.1M2.5 12h3M18.5 12h3M4.2 18.8l2.1-2.1M17.7 7.3l2.1-2.1"/>',
        'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'chart'    => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
        'tag'      => '<path d="M3.5 12.5V4h8.5l8.5 8.5-8.5 8.5Z"/><circle cx="8" cy="8.5" r="1.4"/>',
        'clipboard'=> '<rect x="5" y="4.5" width="14" height="16.5" rx="2"/><path d="M9 4.5V3h6v1.5M9 11h6M9 15h4"/>',
        'receipt'  => '<path d="M6 3h12v18l-2-1.5-2 1.5-2-1.5-2 1.5-2-1.5L6 21Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'alert'    => '<path d="M12 3.5 21.5 20h-19Z"/><path d="M12 10v4.5M12 17.2v.3"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.3"/>',
        'star'     => '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.3-4.1 5.9-.8Z"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'pin'      => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.4"/>',
        'phone'    => '<path d="M5 4h3.5l1.5 4-2 1.5a11 11 0 0 0 6.5 6.5l1.5-2 4 1.5V19a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
        'upload'   => '<path d="M12 15V4M7.5 8.5 12 4l4.5 4.5M4 15v4.5h16V15"/>',
        'image'    => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m4 18 5-5 4 4 2.5-2.5L20 19"/>',
        'shield'   => '<path d="M12 3 4.5 6v5.5c0 4.5 3.2 8.2 7.5 9.5 4.3-1.3 7.5-5 7.5-9.5V6Z"/><path d="m9 12 2 2 4-4"/>',
        'flag'     => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'megaphone'=> '<path d="M4 10v4h3l8 4.5v-13L7 10Z"/><path d="M7 14l1.5 5.5h2.5L10 15M18.5 9.5a3.5 3.5 0 0 1 0 5"/>',
        'home'     => '<path d="m3.5 11 8.5-7 8.5 7"/><path d="M6 9.5V20h12V9.5M10 20v-5.5h4V20"/>',
        'wallet'   => '<path d="M4 7h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a1 1 0 0 1-1-1V5.5A1.5 1.5 0 0 1 5.5 4H17v3"/><circle cx="16" cy="13.5" r="1.2"/>',
        'history'  => '<path d="M4 12a8 8 0 1 0 2.4-5.7L4 8.6"/><path d="M4 4v4.6h4.6M12 8v4.5l3 1.8"/>',
        'filter'   => '<path d="M4 5h16l-6 7.5V19l-4 1.5v-8Z"/>',
        'lock'     => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'leaf'     => '<path d="M5 19c0-8 5-13.5 15-14-.4 9.8-6 15-14 15"/><path d="M5 19c3-4 6.5-7 10.5-9"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
        'printer'  => '<path d="M7 9V3.5h10V9"/><rect x="3.5" y="9" width="17" height="8" rx="2"/><path d="M7 14h10v6.5H7Z"/>',
        'refresh'  => '<path d="M20 11a8 8 0 0 0-14.3-4.6L4 8.5M4 4v4.5h4.5M4 13a8 8 0 0 0 14.3 4.6l1.7-2.1M20 20v-4.5h-4.5"/>',
    ];
    $body = $paths[$name] ?? $paths['info'];
    $cls = 'icon' . ($class !== '' ? ' ' . $class : '');
    return '<svg class="' . e($cls) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

// ---------------------------------------------------------------------
// Small view helpers
// ---------------------------------------------------------------------
function view_partial(string $name, array $data = []): string
{
    return \App\Core\View::capture('components/' . $name, $data);
}

function nav_active(string $prefix, bool $exact = false): string
{
    $path = current_path();
    $match = $exact ? $path === $prefix : ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/'));
    return $match ? ' is-active' : '';
}

function auth_user(): ?array
{
    return Auth::user();
}

function stars(float $rating, int $size = 14): string
{
    $out = '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' daripada 5 bintang">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="star' . ($rating >= $i - 0.25 ? ' is-on' : '') . '">' . icon('star', $size) . '</span>';
    }
    return $out . '</span>';
}

function log_error(\Throwable $e): void
{
    $line = sprintf("[%s] %s: %s in %s:%d\n%s\n\n", now(), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    @file_put_contents(BASE_PATH . '/storage/logs/app.log', $line, FILE_APPEND | LOCK_EX);
}
