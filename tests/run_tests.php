<?php
/**
 * KVPM E-FoodHub — automated end-to-end test suite.
 *
 *   php tests/run_tests.php
 *
 * What it does:
 *  1. builds a fresh SQLite copy of database/schema.sql + database/seed.sql
 *  2. starts the real application with PHP's built-in web server
 *  3. drives it over HTTP like a browser (sessions, CSRF tokens, file uploads)
 *  4. checks responses AND the database after each step
 *  5. writes tests/results/latest.md
 *
 * IMPORTANT: this runs against SQLite, not MySQL. It proves the PHP logic,
 * routing, access control and SQL that both engines share. Re-run the manual
 * checklist in docs/TESTING.md on XAMPP/MySQL before the final demonstration.
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');
require __DIR__ . '/support/HttpClient.php';

$root = dirname(__DIR__);
$dbFile = $root . '/storage/e2e.sqlite';
$port = 8100 + random_int(0, 800);
$base = "http://127.0.0.1:$port";

// ---------------------------------------------------------------- setup
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/support/make_sqlite_db.php') . ' ' . escapeshellarg($dbFile), $rc);
if ($rc !== 0) {
    exit("Could not build test database\n");
}
$env = ['DB_DRIVER' => 'sqlite', 'DB_DATABASE' => $dbFile, 'APP_ENV' => 'development', 'APP_DEBUG' => '0'] + getenv();
$server = proc_open(
    [PHP_BINARY, '-d', 'upload_max_filesize=8M', '-d', 'post_max_size=10M', '-S', "127.0.0.1:$port", '-t', $root . '/public', $root . '/public/index.php'],
    [1 => ['file', $root . '/storage/logs/e2e-server.log', 'w'], 2 => ['file', $root . '/storage/logs/e2e-server.log', 'a']],
    $pipes,
    $root,
    $env
);
register_shutdown_function(function () use ($server) {
    proc_terminate($server);
});
for ($i = 0; $i < 50; $i++) {
    if (@file_get_contents($base . '/info/help') !== false) {
        break;
    }
    usleep(100000);
}

$db = new PDO('sqlite:' . $dbFile, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$val = function (string $sql, array $p = []) use ($db) {
    $s = $db->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
};
$row = function (string $sql, array $p = []) use ($db) {
    $s = $db->prepare($sql);
    $s->execute($p);
    return $s->fetch();
};
$client = fn () => new HttpClient($base);

// Fixtures for upload tests
$fx = $root . '/tests/fixtures';
@mkdir($fx, 0777, true);
$img = imagecreatetruecolor(400, 300);
imagefill($img, 0, 0, imagecolorallocate($img, 14, 122, 82));
imagejpeg($img, "$fx/valid.jpg", 85);
file_put_contents("$fx/shell.jpg", "<?php echo 'pwned'; ?>");
// a real JPEG followed by 3 MB of padding: still detected as image/jpeg, but over the 2 MB limit
file_put_contents("$fx/big.jpg", file_get_contents("$fx/valid.jpg") . random_bytes(3 * 1048576));
clearstatcache();

// ---------------------------------------------------------------- test runner
$results = [];
function test(string $id, string $name, callable $fn): void
{
    global $results;
    try {
        $out = $fn();
        $ok = $out === true;
        $detail = $ok ? '' : (is_string($out) ? $out : 'returned ' . var_export($out, true));
    } catch (Throwable $e) {
        $ok = false;
        $detail = get_class($e) . ': ' . $e->getMessage();
    }
    $results[] = [$id, $name, $ok, $detail];
    printf("%s  %-11s %s%s\n", $ok ? 'PASS' : 'FAIL', $id, $name, $ok ? '' : "\n      -> $detail");
}
function expect(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}
function checkoutAs(HttpClient $c, string $method = 'cash', array $extra = []): string
{
    $c->get('/checkout');
    expect($c->lastStatus === 200, 'checkout page status ' . $c->lastStatus . ' ' . $c->lastLocation);
    preg_match('/name="checkout_token" value="([a-f0-9]+)"/', $c->lastBody, $m);
    $c->post('/checkout', $extra + ['checkout_token' => $m[1], 'contact_name' => 'Ujian Pembeli', 'contact_phone' => '012-345 6789', 'notes' => 'Tolong kurang pedas', 'payment_method' => $method]);
    expect($c->lastStatus === 302, 'checkout POST status ' . $c->lastStatus);
    expect((bool) preg_match('#/orders/(KV[0-9]{6}-[A-Z0-9]+)#', $c->lastLocation, $n), 'no order in redirect: ' . $c->lastLocation);
    return $n[1];
}

// ================================================================ AUTHENTICATION
test('TC-AUTH-01', 'Register a new customer account', function () use ($client, $row) {
    $c = $client();
    $c->get('/register');
    $c->post('/register', ['name' => 'Pelajar Ujian', 'email' => 'Ujian1@Example.test', 'phone' => '012-111 2222', 'password' => 'Rahsia123', 'password_confirmation' => 'Rahsia123', 'agree' => '1']);
    expect($c->lastStatus === 302, 'status ' . $c->lastStatus);
    $u = $row('SELECT * FROM users WHERE email = ?', ['ujian1@example.test']);
    expect($u !== false, 'user not stored (e-mail should be lower-cased)');
    expect($u['role'] === 'customer', 'role is ' . $u['role']);
    expect(str_starts_with($u['password_hash'], '$2y$') && password_verify('Rahsia123', $u['password_hash']), 'password not hashed with bcrypt');
    $c->get('/account');
    return $c->lastStatus === 200 ?: 'not signed in after registering';
});

test('TC-AUTH-02', 'Duplicate e-mail is rejected', function () use ($client, $val) {
    $c = $client();
    $c->get('/register');
    $c->post('/register', ['name' => 'Orang Lain', 'email' => 'ujian1@example.test', 'phone' => '012-111 3333', 'password' => 'Rahsia123', 'password_confirmation' => 'Rahsia123', 'agree' => '1']);
    $c->follow();
    expect($c->contains('sudah didaftarkan'), 'no duplicate message');
    return (int) $val('SELECT COUNT(*) FROM users WHERE email = ?', ['ujian1@example.test']) === 1 ?: 'duplicate created';
});

test('TC-AUTH-03', 'Weak password and mismatched confirmation are rejected', function () use ($client, $val) {
    $c = $client();
    $c->get('/register');
    $c->post('/register', ['name' => 'Lemah', 'email' => 'lemah@example.test', 'phone' => '012-111 4444', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh', 'agree' => '1']);
    $c->follow();
    expect($c->contains('huruf dan satu nombor'), 'letters+digits rule not enforced');
    $c->post('/register', ['name' => 'Lemah', 'email' => 'lemah@example.test', 'phone' => '012-111 4444', 'password' => 'Rahsia123', 'password_confirmation' => 'Rahsia999', 'agree' => '1']);
    $c->follow();
    expect($c->contains('tidak sepadan'), 'confirmation mismatch accepted');
    return (int) $val("SELECT COUNT(*) FROM users WHERE email = 'lemah@example.test'") === 0 ?: 'account created';
});

test('TC-AUTH-04', 'Tampered role field cannot create an administrator', function () use ($client, $val) {
    $c = $client();
    $c->get('/register');
    $c->post('/register', ['name' => 'Penggodam', 'email' => 'hack@example.test', 'phone' => '012-111 5555', 'password' => 'Rahsia123', 'password_confirmation' => 'Rahsia123', 'agree' => '1', 'role' => 'admin', 'status' => 'active']);
    return $val("SELECT role FROM users WHERE email = 'hack@example.test'") === 'customer' ?: 'role was not forced to customer';
});

test('TC-AUTH-05', 'Wrong password shows a generic error and does not sign in', function () use ($client) {
    $c = $client();
    $c->login('pelanggan@demo.test', 'SalahSalah1');
    $c->follow();
    expect($c->contains('E-mel atau kata laluan tidak betul'), 'no generic error');
    $c->get('/account');
    return $c->lastStatus === 302 ?: 'signed in with wrong password';
});

test('TC-AUTH-06', 'Login is blocked after 5 failed attempts (rate limit)', function () use ($client) {
    $c = $client();
    for ($i = 0; $i < 5; $i++) {
        $c->login('ujian1@example.test', 'Salah' . $i . 'xx');
    }
    $c->login('ujian1@example.test', 'Rahsia123'); // correct password, still blocked
    $c->follow();
    expect($c->contains('Terlalu banyak cubaan'), 'not rate limited');
    $c->get('/account');
    return $c->lastStatus === 302 ?: 'signed in despite rate limit';
});

test('TC-AUTH-07', 'Session ID changes on login; logout requires POST', function () use ($client) {
    $c = $client();
    $c->get('/login');
    $before = $c->sessionId();
    $c->post('/login', ['email' => 'pelanggan@demo.test', 'password' => 'Demo@1234']);
    $after = $c->sessionId();
    expect($before !== '' && $after !== '' && $before !== $after, 'session id not regenerated');
    $c->get('/logout');
    expect($c->lastStatus === 405, 'GET /logout returned ' . $c->lastStatus);
    $c->get('/');
    $c->post('/logout');
    $c->get('/account');
    return $c->lastStatus === 302 ?: 'still signed in after logout';
});

test('TC-AUTH-08', 'Guests are redirected to login for protected pages', function () use ($client) {
    $c = $client();
    foreach (['/cart', '/orders', '/account', '/seller', '/admin'] as $p) {
        $c->get($p);
        expect($c->lastStatus === 302 && str_contains($c->lastLocation, '/login'), "$p -> {$c->lastStatus} {$c->lastLocation}");
    }
    return true;
});

// ================================================================ CATALOGUE & SEARCH
test('TC-CAT-01', 'Keyword search matches product name, description or shop name', function () use ($client) {
    $c = $client();
    $c->get('/products?q=kerabu');
    preg_match_all('#class="product-card__name"[^>]*>([^<]+)<#', $c->lastBody, $m);
    expect($m[1] === ['Nasi Kerabu Ayam Percik'], 'kerabu: ' . implode(', ', $m[1]));
    $c->get('/products?q=nasi'); // 3 products named "Nasi…" plus other dishes from "Gerai Nasi Mak Su"
    preg_match_all('#class="product-card__name"[^>]*>([^<]+)<#', $c->lastBody, $m);
    foreach (['Nasi Lemak Bungkus', 'Nasi Kerabu Ayam Percik', 'Nasi Goreng Kampung'] as $n) {
        expect(in_array($n, $m[1], true), "$n missing");
    }
    preg_match_all('#class="product-card__shop">.*?</svg>\s*([^<]+)<#s', $c->lastBody, $shops);
    foreach ($m[1] as $i => $n) {
        expect(stripos($n, 'nasi') !== false || stripos($shops[1][$i] ?? '', 'nasi') !== false, "unrelated result $n");
    }
    $c->get('/products?q=zzzztiadaproduk');
    return $c->contains('Tiada produk sepadan') ?: 'no empty state';
});

test('TC-CAT-02', 'Category, price-range and in-stock filters change the results', function () use ($client) {
    $c = $client();
    $c->get('/products?category=minuman');
    expect(preg_match_all('#class="product-card__name"#', $c->lastBody) === 6, 'minuman count');
    $c->get('/products?category=minuman&availability=in_stock');
    expect(preg_match_all('#class="product-card__name"#', $c->lastBody) === 5 && !$c->contains('Jus Tembikai'), 'in-stock filter');
    $c->get('/products?min=3&max=4');
    preg_match_all('#class="product-card__price">RM([0-9.]+)<#', $c->lastBody, $m);
    foreach ($m[1] as $p) {
        expect((float) $p >= 3 && (float) $p <= 4, "price $p out of range");
    }
    return count($m[1]) > 0 ?: 'no results';
});

test('TC-CAT-03', 'Sort by price (low to high) orders results correctly', function () use ($client) {
    $c = $client();
    $c->get('/products?sort=price_asc');
    preg_match_all('#class="product-card__price">RM([0-9.,]+)<#', $c->lastBody, $m);
    $prices = array_map(fn ($p) => (float) str_replace(',', '', $p), $m[1]);
    $sorted = $prices;
    sort($sorted);
    return $prices === $sorted ?: 'not sorted: ' . implode(',', $prices);
});

test('TC-CAT-04', 'Pending/unapproved product is hidden from catalogue and product page', function () use ($client) {
    $c = $client();
    $c->get('/products?q=puding');
    expect(!$c->contains('Puding Roti'), 'pending product listed');
    $c->get('/product/puding-roti-karamel');
    return $c->lastStatus === 404 ?: 'product page status ' . $c->lastStatus;
});

test('TC-CAT-05', 'Pagination limits results to 12 per page and keeps filters', function () use ($client) {
    $c = $client();
    $c->get('/products');
    expect(preg_match_all('#class="product-card__name"#', $c->lastBody) === 12, 'page 1 not 12 items');
    expect($c->contains('page=2'), 'no page 2 link');
    $c->get('/products?availability=in_stock&page=2');
    expect($c->lastStatus === 200 && $c->contains('availability=in_stock'), 'filter not kept in pagination links');
    $c->get('/products?page=999');
    return $c->lastStatus === 200 ?: 'out of range page error';
});

test('TC-SEC-01', 'SQL injection attempts in search, sort and IDs are harmless', function () use ($client, $val) {
    $c = $client();
    $before = (int) $val('SELECT COUNT(*) FROM products');
    foreach (["/products?q=" . urlencode("' OR 1=1 --"), "/products?sort=" . urlencode("price; DROP TABLE products"), "/products?min=" . urlencode("0 OR 1=1"), "/products?category=" . urlencode("x' UNION SELECT password_hash FROM users--"), "/product/" . urlencode("1' OR '1'='1")] as $p) {
        $c->get($p);
        expect(in_array($c->lastStatus, [200, 404], true), "$p -> {$c->lastStatus}");
        expect(!$c->contains('$2y$'), 'password hash leaked');
    }
    $c->get("/products?q=" . urlencode("' OR 1=1 --"));
    expect(!$c->contains('class="product-card__name"'), 'injection matched products');
    return (int) $val('SELECT COUNT(*) FROM products') === $before ?: 'products table changed';
});

test('TC-SEC-02', 'User input is HTML-escaped (reflected XSS in search)', function () use ($client) {
    $c = $client();
    $c->get('/products?q=' . urlencode('<script>alert(1)</script>'));
    expect(!$c->contains('<script>alert(1)</script>'), 'raw script tag reflected');
    return $c->contains('&lt;script&gt;alert(1)&lt;/script&gt;') ?: 'escaped text not found';
});

test('TC-SEC-03', 'Security headers are sent', function () use ($client) {
    $c = $client();
    $c->get('/');
    foreach (['x-content-type-options' => 'nosniff', 'x-frame-options' => 'DENY', 'content-security-policy' => "script-src 'self'"] as $h => $v) {
        expect(str_contains($c->lastHeaders[$h] ?? '', $v), "header $h missing");
    }
    return true;
});

test('TC-SEC-04', 'POST without a valid CSRF token is refused (419)', function () use ($client) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->post('/cart/add', ['product_id' => '1', 'quantity' => '1'], false);
    expect($c->lastStatus === 419, 'no-token status ' . $c->lastStatus);
    $c->post('/cart/add', ['product_id' => '1', 'quantity' => '1', '_token' => str_repeat('a', 64)]);
    return $c->lastStatus === 419 ?: 'wrong-token status ' . $c->lastStatus;
});

test('TC-SEC-05', 'Login "next" parameter cannot redirect to another site', function () use ($client) {
    $c = $client();
    $c->get('/login?next=' . urlencode('//evil.example/steal'));
    $c->post('/login', ['email' => 'pelanggan2@demo.test', 'password' => 'Demo@1234']);
    return !str_contains($c->lastLocation, 'evil.example') ?: 'open redirect to ' . $c->lastLocation;
});

test('TC-SEC-06', 'Project files outside public/ are not served', function () use ($client) {
    $c = $client();
    foreach (['/.env.example', '/../config/config.php', '/database/schema.sql', '/app/bootstrap.php'] as $p) {
        $c->get($p);
        expect($c->lastStatus === 404, "$p -> {$c->lastStatus}");
    }
    return true;
});

// ================================================================ CART
test('TC-CART-01', 'Add to cart, update quantity, remove (server-side totals)', function () use ($client, $val) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '1', 'quantity' => '2', 'price' => '0.01']); // price field must be ignored
    $c->post('/cart/add', ['product_id' => '6', 'quantity' => '1']);
    $c->get('/cart');
    expect($c->contains('RM8.00'), 'total should be 2×3.00 + 2.00 = RM8.00');
    $c->post('/cart/update', ['product_id' => '1', 'quantity' => '3']);
    expect((int) $val('SELECT quantity FROM cart_items WHERE user_id = 6 AND product_id = 1') === 3, 'quantity not updated');
    $c->post('/cart/update', ['product_id' => '6', 'quantity' => '0']);
    expect(!$val('SELECT id FROM cart_items WHERE user_id = 6 AND product_id = 6'), 'quantity 0 did not remove');
    $c->post('/cart/remove', ['product_id' => '1']);
    return (int) $val('SELECT COUNT(*) FROM cart_items WHERE user_id = 6') === 0 ?: 'cart not empty';
});

test('TC-CART-02', 'Cannot add more than stock, out-of-stock or unapproved items', function () use ($client, $val) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->post('/cart/add', ['product_id' => '14', 'quantity' => '5']); // stock 3
    $c->post('/cart/add', ['product_id' => '10', 'quantity' => '1']); // stock 0
    $c->post('/cart/add', ['product_id' => '21', 'quantity' => '1']); // pending approval
    $c->post('/cart/add', ['product_id' => '999', 'quantity' => '1']); // does not exist
    return (int) $val('SELECT COUNT(*) FROM cart_items WHERE user_id = 6') === 0 ?: 'invalid item entered the cart';
});

test('TC-CART-03', 'JSON add-to-cart returns the new cart count', function () use ($client) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '13', 'quantity' => '1'], true, ['Accept: application/json']);
    $j = json_decode($c->lastBody, true);
    expect(($j['ok'] ?? false) === true && ($j['cart_count'] ?? 0) === 1, 'json ' . $c->lastBody);
    $c->post('/cart/add', ['product_id' => '14', 'quantity' => '9'], true, ['Accept: application/json']);
    $j = json_decode($c->lastBody, true);
    expect($c->lastStatus === 422 && ($j['ok'] ?? true) === false, 'over-stock JSON not rejected');
    $c->post('/cart/remove', ['product_id' => '13']);
    return true;
});

test('TC-CART-04', 'A seller cannot add their own product to the cart', function () use ($client, $val) {
    $c = $client();
    $c->login('kantin@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '1', 'quantity' => '1']);
    return !$val('SELECT id FROM cart_items WHERE user_id = 2') ?: 'own product added';
});

// ================================================================ CHECKOUT & STOCK
$multiOrder = '';
test('TC-CHK-01', 'Multi-shop checkout: 1 order, 1 part per shop, stock deducted, cart emptied', function () use ($client, $val, $row, &$multiOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $s1 = (int) $val('SELECT stock FROM products WHERE id = 2');
    $s2 = (int) $val('SELECT stock FROM products WHERE id = 17');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '2', 'quantity' => '2']);   // Mak Su 7.50 ×2
    $c->post('/cart/add', ['product_id' => '17', 'quantity' => '1']);  // Snek 3.00
    $multiOrder = checkoutAs($c, 'cash');
    $o = $row('SELECT * FROM orders WHERE order_number = ?', [$multiOrder]);
    expect(abs((float) $o['total'] - 18.00) < 0.001, 'total ' . $o['total']);
    expect((int) $val('SELECT COUNT(*) FROM seller_orders WHERE order_id = ?', [$o['id']]) === 2, 'expected 2 seller orders');
    expect((int) $val('SELECT stock FROM products WHERE id = 2') === $s1 - 2, 'stock 2 not deducted');
    expect((int) $val('SELECT stock FROM products WHERE id = 17') === $s2 - 1, 'stock 17 not deducted');
    expect((int) $val('SELECT COUNT(*) FROM cart_items WHERE user_id = 6') === 0, 'cart not emptied');
    expect((int) $val("SELECT COUNT(*) FROM notifications WHERE user_id IN (2,5) AND type = 'order_placed'") >= 2, 'sellers not notified');
    return true;
});

test('TC-CHK-02', 'Re-submitting the same checkout form cannot create a second order', function () use ($client, $val) {
    $c = $client();
    $c->login('pelanggan2@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '12', 'quantity' => '1']);
    $c->get('/checkout');
    preg_match('/name="checkout_token" value="([a-f0-9]+)"/', $c->lastBody, $m);
    $fields = ['checkout_token' => $m[1], 'contact_name' => 'Pelanggan Dua', 'contact_phone' => '013-000 0007', 'notes' => '', 'payment_method' => 'cash'];
    $before = (int) $val('SELECT COUNT(*) FROM orders');
    $c->post('/checkout', $fields);
    $c->post('/cart/add', ['product_id' => '12', 'quantity' => '1']); // even with items in the cart again
    $c->post('/checkout', $fields);
    expect(str_contains($c->lastLocation, '/orders'), 'second submit not redirected to orders');
    $c->post('/cart/remove', ['product_id' => '12']);
    return (int) $val('SELECT COUNT(*) FROM orders') === $before + 1 ?: 'duplicate order created';
});

test('TC-CHK-03', 'Checkout re-checks stock: nothing is ordered if stock ran out meanwhile', function () use ($client, $val, $db) {
    $c = $client();
    $c->login('pelanggan2@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '20', 'quantity' => '3']);
    $c->get('/checkout');
    preg_match('/name="checkout_token" value="([a-f0-9]+)"/', $c->lastBody, $m);
    $db->exec('UPDATE products SET stock = 2 WHERE id = 20'); // someone else bought some
    $before = (int) $val('SELECT COUNT(*) FROM orders');
    $c->post('/checkout', ['checkout_token' => $m[1], 'contact_name' => 'Pelanggan Dua', 'contact_phone' => '013-000 0007', 'notes' => '', 'payment_method' => 'cash']);
    expect((int) $val('SELECT COUNT(*) FROM orders') === $before, 'order created');
    expect((int) $val('SELECT stock FROM products WHERE id = 20') === 2, 'stock changed');
    $c->follow();
    expect(str_contains($c->flashText(), 'unit tersedia'), 'no stock message: ' . $c->flashText());
    $c->post('/cart/remove', ['product_id' => '20']);
    $db->exec('UPDATE products SET stock = 10 WHERE id = 20');
    return true;
});

test('TC-CHK-04', 'Checkout validates required fields', function () use ($client, $val) {
    $c = $client();
    $c->login('pelanggan2@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '11', 'quantity' => '1']);
    $c->get('/checkout');
    preg_match('/name="checkout_token" value="([a-f0-9]+)"/', $c->lastBody, $m);
    $before = (int) $val('SELECT COUNT(*) FROM orders');
    $c->post('/checkout', ['checkout_token' => $m[1], 'contact_name' => '', 'contact_phone' => 'abc', 'notes' => '', 'payment_method' => 'bitcoin']);
    $c->follow();
    expect($c->contains('Nama penerima diperlukan') && $c->contains('nombor telefon yang sah') && $c->contains('Kaedah pembayaran tidak sah'), 'validation messages missing');
    $c->post('/cart/remove', ['product_id' => '11']);
    return (int) $val('SELECT COUNT(*) FROM orders') === $before ?: 'invalid order created';
});

// ================================================================ CUSTOMER ORDERS & ACCESS CONTROL
test('TC-ORD-01', 'Customers see only their own orders (other orders return 404)', function () use ($client, $multiOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->get('/orders/' . $multiOrder);
    expect($c->lastStatus === 200 && $c->contains($multiOrder), 'own order not visible');
    $c2 = $client();
    $c2->login('pelanggan2@demo.test');
    $c2->get('/orders/' . $multiOrder);
    expect($c2->lastStatus === 404, 'other customer got ' . $c2->lastStatus);
    $c2->get('/orders');
    return !$c2->contains($multiOrder) ?: 'order listed for another customer';
});

test('TC-ORD-02', 'Customer cancels a pending part; stock restored exactly once', function () use ($client, $val, $row, $multiOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $so = $row("SELECT so.* FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE o.order_number = ? AND so.seller_id = 4", [$multiOrder]);
    $stock = (int) $val('SELECT stock FROM products WHERE id = 17');
    $c->get('/orders/' . $multiOrder);
    $c->post('/orders/' . $multiOrder . '/cancel', ['seller_order_id' => (string) $so['id']]);
    expect($val('SELECT status FROM seller_orders WHERE id = ?', [$so['id']]) === 'cancelled', 'not cancelled');
    expect((int) $val('SELECT stock FROM products WHERE id = 17') === $stock + 1, 'stock not restored');
    $c->post('/orders/' . $multiOrder . '/cancel', ['seller_order_id' => (string) $so['id']]); // again
    expect((int) $val('SELECT stock FROM products WHERE id = 17') === $stock + 1, 'stock restored twice');
    return $val('SELECT payment_status FROM seller_orders WHERE id = ?', [$so['id']]) === 'void' ?: 'payment status not void';
});

test('TC-ORD-03', 'Customer cannot cancel another customer\'s order part', function () use ($client, $val, $row, $multiOrder) {
    $so = $row("SELECT so.id FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE o.order_number = ? AND so.seller_id = 1", [$multiOrder]);
    $c = $client();
    $c->login('pelanggan2@demo.test');
    $c->post('/orders/' . $multiOrder . '/cancel', ['seller_order_id' => (string) $so['id']]);
    expect($c->lastStatus === 404, 'status ' . $c->lastStatus);
    return $val('SELECT status FROM seller_orders WHERE id = ?', [$so['id']]) === 'pending' ?: 'status changed';
});

// ================================================================ SELLER WORKFLOW
test('TC-SEL-01', 'Seller moves an order through the full workflow; cash recorded on completion', function () use ($client, $val, $row, $multiOrder) {
    $so = $row("SELECT so.* FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE o.order_number = ? AND so.seller_id = 1", [$multiOrder]);
    $c = $client();
    $c->login('kantin@demo.test');
    $c->get('/seller/orders/' . $so['id']);
    expect($c->lastStatus === 200 && $c->contains('Tolong kurang pedas'), 'seller cannot see order notes');
    foreach (['confirmed', 'preparing', 'ready', 'completed'] as $to) {
        $c->post('/seller/orders/' . $so['id'] . '/status', ['to' => $to]);
        expect($val('SELECT status FROM seller_orders WHERE id = ?', [$so['id']]) === $to, "did not reach $to");
    }
    expect($val('SELECT payment_status FROM seller_orders WHERE id = ?', [$so['id']]) === 'paid', 'cash not marked paid');
    expect((int) $val("SELECT COUNT(*) FROM payments WHERE seller_order_id = ? AND method = 'cash' AND status = 'success'", [$so['id']]) === 1, 'no cash payment record');
    expect((int) $val('SELECT COUNT(*) FROM order_status_history WHERE seller_order_id = ?', [$so['id']]) === 5, 'history rows missing');
    return (int) $val("SELECT COUNT(*) FROM notifications WHERE user_id = 6 AND type = 'order_ready'") >= 1 ?: 'customer not notified';
});

test('TC-SEL-02', 'Invalid status jumps and customer-only actions are refused', function () use ($client, $val) {
    $c = $client();
    $c->login('snek@demo.test');
    $id = (int) $val("SELECT id FROM seller_orders WHERE seller_id = 4 AND status = 'pending' LIMIT 1");
    expect($id > 0, 'no pending order for snek in seed');
    foreach (['completed', 'ready', 'cancelled', 'bogus'] as $to) {
        $c->post("/seller/orders/$id/status", ['to' => $to]);
    }
    return $val('SELECT status FROM seller_orders WHERE id = ?', [$id]) === 'pending' ?: 'status changed by invalid transition';
});

test('TC-SEL-03', 'Seller cannot view or change another shop\'s orders and products', function () use ($client, $val) {
    $c = $client();
    $c->login('snek@demo.test');
    $foreignOrder = (int) $val('SELECT id FROM seller_orders WHERE seller_id = 1 LIMIT 1');
    $c->get("/seller/orders/$foreignOrder");
    expect($c->lastStatus === 404, 'foreign order view ' . $c->lastStatus);
    $status = $val('SELECT status FROM seller_orders WHERE id = ?', [$foreignOrder]);
    $c->post("/seller/orders/$foreignOrder/status", ['to' => 'cancelled']);
    expect($c->lastStatus === 404 && $val('SELECT status FROM seller_orders WHERE id = ?', [$foreignOrder]) === $status, 'foreign order changed');
    $c->get('/seller/products/1/edit');
    expect($c->lastStatus === 404, 'foreign product edit ' . $c->lastStatus);
    $c->post('/seller/products/1/stock', ['stock' => '0']);
    expect((int) $val('SELECT stock FROM products WHERE id = 1') > 0, 'foreign stock changed');
    $c->post('/seller/products/1/archive');
    return $val('SELECT archived_at FROM products WHERE id = 1') === null ?: 'foreign product archived';
});

test('TC-SEL-04', 'Rejecting needs a reason; rejection restores stock', function () use ($client, $val) {
    $c = $client();
    $c->login('snek@demo.test');
    $id = (int) $val("SELECT id FROM seller_orders WHERE seller_id = 4 AND status = 'pending' LIMIT 1");
    $items = $val('SELECT SUM(quantity) FROM order_items WHERE seller_order_id = ?', [$id]);
    $pid = (int) $val('SELECT product_id FROM order_items WHERE seller_order_id = ?', [$id]);
    $stock = (int) $val('SELECT stock FROM products WHERE id = ?', [$pid]);
    $c->get("/seller/orders/$id");
    $c->post("/seller/orders/$id/status", ['to' => 'rejected', 'note' => '']);
    expect($val('SELECT status FROM seller_orders WHERE id = ?', [$id]) === 'pending', 'rejected without reason');
    $c->post("/seller/orders/$id/status", ['to' => 'rejected', 'note' => 'Bahan habis']);
    expect($val('SELECT status FROM seller_orders WHERE id = ?', [$id]) === 'rejected', 'not rejected');
    return (int) $val('SELECT stock FROM products WHERE id = ?', [$pid]) === $stock + (int) $items ?: 'stock not restored';
});

test('TC-SEL-05', 'Seller creates a product with a valid image (stored with a random name, pending approval)', function () use ($client, $val, $fx) {
    $c = $client();
    $c->login('snek@demo.test');
    $c->get('/seller/products/create');
    $c->post('/seller/products', ['name' => 'Kuih Seri Muka Ujian', 'category_id' => '3', 'description' => 'Kuih dua lapis pulut dan seri kaya.', 'price' => '1.50', 'stock' => '24', 'sku' => '', 'is_available' => '1', 'images[0]' => new CURLFile("$fx/valid.jpg", 'image/jpeg', 'seri muka.jpg')]);
    $p = $val("SELECT id FROM products WHERE name = 'Kuih Seri Muka Ujian'");
    expect((bool) $p, 'product not created');
    expect($val('SELECT approval_status FROM products WHERE id = ?', [$p]) === 'pending', 'not pending approval');
    $path = (string) $val('SELECT path FROM product_images WHERE product_id = ?', [$p]);
    expect((bool) preg_match('#^products/[a-f0-9]{32}\.jpg$#', $path), 'unexpected stored name ' . $path);
    return is_file(dirname(__DIR__) . '/public/uploads/' . $path) ?: 'file not on disk';
});

test('TC-SEL-06', 'Uploads reject PHP code disguised as an image, and oversized files', function () use ($client, $val, $fx) {
    $c = $client();
    $c->login('snek@demo.test');
    $c->get('/seller/products/create');
    $before = (int) $val('SELECT COUNT(*) FROM product_images');
    $c->post('/seller/products', ['name' => 'Produk Berbahaya', 'category_id' => '3', 'description' => 'Ujian muat naik tidak sah.', 'price' => '1.00', 'stock' => '1', 'is_available' => '1', 'images[0]' => new CURLFile("$fx/shell.jpg", 'image/jpeg', 'shell.jpg')]);
    $c->follow();
    expect($c->contains('Hanya gambar JPG, PNG atau WebP'), 'disguised PHP not rejected: ' . $c->flashText());
    expect(filesize("$fx/big.jpg") > 2 * 1048576, 'fixture not big enough');
    $c->post('/seller/products', ['name' => 'Produk Besar', 'category_id' => '3', 'description' => 'Ujian fail terlalu besar.', 'price' => '1.00', 'stock' => '1', 'is_available' => '1', 'images[0]' => new CURLFile("$fx/big.jpg", 'image/jpeg', 'big.jpg')]);
    $c->follow();
    expect($c->contains('tidak melebihi') || $c->contains('terlalu besar'), 'big file not rejected');
    expect((int) $val("SELECT COUNT(*) FROM products WHERE name IN ('Produk Berbahaya','Produk Besar')") === 0, 'product saved despite bad image');
    return (int) $val('SELECT COUNT(*) FROM product_images') === $before ?: 'image rows added';
});

test('TC-SEL-07', 'Negative price/stock and invalid category are rejected', function () use ($client, $val) {
    $c = $client();
    $c->login('snek@demo.test');
    $c->get('/seller/products/create');
    $c->post('/seller/products', ['name' => 'Harga Negatif', 'category_id' => '999', 'description' => 'Ujian nilai tidak sah.', 'price' => '-5', 'stock' => '-1', 'is_available' => '1']);
    $c->follow();
    expect($c->contains('Harga mesti jumlah wang yang sah') && $c->contains('Stok mesti sekurang-kurangnya 0'), 'messages missing');
    return !$val("SELECT id FROM products WHERE name = 'Harga Negatif'") ?: 'saved';
});

test('TC-SEL-08', 'Customers cannot open seller pages (403)', function () use ($client) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    foreach (['/seller', '/seller/products', '/seller/orders', '/seller/reports'] as $p) {
        $c->get($p);
        expect($c->lastStatus === 403, "$p -> {$c->lastStatus}");
    }
    $c->post('/seller/products', ['name' => 'X', 'price' => '1']);
    return $c->lastStatus === 403 ?: 'POST status ' . $c->lastStatus;
});

test('TC-SEL-09', 'Seller reports count only completed orders as sales; CSV export works', function () use ($client, $val) {
    $c = $client();
    $c->login('kopi@demo.test');
    $expected = (float) $val("SELECT COALESCE(SUM(subtotal),0) FROM seller_orders WHERE seller_id = 2 AND status = 'completed' AND completed_at >= ?", [date('Y-m-d 00:00:00', strtotime('-29 days'))]);
    $c->get('/seller/reports?range=30');
    expect($c->contains('RM' . number_format($expected, 2)), 'sales figure RM' . number_format($expected, 2) . ' not shown');
    $c->get('/seller/reports/export?range=30');
    expect(str_contains($c->lastHeaders['content-type'] ?? '', 'text/csv'), 'not CSV');
    return substr_count($c->lastBody, "\n") >= 2 ?: 'CSV has no rows';
});

// ================================================================ ONLINE PAYMENT (SIMULATED)
$onlineOrder = '';
test('TC-PAY-01', 'Simulated online order: seller cannot confirm before payment', function () use ($client, $val, &$onlineOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->get('/products');
    $c->post('/cart/add', ['product_id' => '7', 'quantity' => '2']);
    $onlineOrder = checkoutAs($c, 'online_sim');
    expect(str_contains($c->lastLocation, '/pay'), 'not redirected to payment page');
    $id = (int) $val('SELECT so.id FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE o.order_number = ?', [$onlineOrder]);
    $s = $client();
    $s->login('kopi@demo.test');
    $s->get("/seller/orders/$id");
    $s->post("/seller/orders/$id/status", ['to' => 'confirmed']);
    return $val('SELECT status FROM seller_orders WHERE id = ?', [$id]) === 'pending' ?: 'confirmed before payment';
});

test('TC-PAY-02', 'Simulated failure records a failed payment and leaves the order unpaid', function () use ($client, $val, &$onlineOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->get("/orders/$onlineOrder/pay");
    expect($c->contains('Simulasi') && !preg_match('/name="(card|cvv|card_number|tac|otp)"/i', $c->lastBody), 'page should be labelled and ask for no card data');
    $c->post("/orders/$onlineOrder/pay", ['outcome' => 'failure']);
    $oid = $val('SELECT id FROM orders WHERE order_number = ?', [$onlineOrder]);
    expect((int) $val("SELECT COUNT(*) FROM payments WHERE order_id = ? AND status = 'failed'", [$oid]) === 1, 'no failed record');
    return $val('SELECT payment_status FROM seller_orders WHERE order_id = ?', [$oid]) === 'unpaid' ?: 'not unpaid';
});

test('TC-PAY-03', 'Simulated success marks it paid; a second payment is refused; seller can confirm', function () use ($client, $val, &$onlineOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $c->get("/orders/$onlineOrder/pay");
    $c->post("/orders/$onlineOrder/pay", ['outcome' => 'success']);
    $oid = $val('SELECT id FROM orders WHERE order_number = ?', [$onlineOrder]);
    expect($val('SELECT payment_status FROM seller_orders WHERE order_id = ?', [$oid]) === 'paid', 'not paid');
    $c->post("/orders/$onlineOrder/pay", ['outcome' => 'success']);
    expect((int) $val("SELECT COUNT(*) FROM payments WHERE order_id = ? AND status = 'success'", [$oid]) === 1, 'paid twice');
    $id = (int) $val('SELECT id FROM seller_orders WHERE order_id = ?', [$oid]);
    $s = $client();
    $s->login('kopi@demo.test');
    $s->get("/seller/orders/$id");
    $s->post("/seller/orders/$id/status", ['to' => 'confirmed']);
    return $val('SELECT status FROM seller_orders WHERE id = ?', [$id]) === 'confirmed' ?: 'seller could not confirm after payment';
});

test('TC-PAY-04', 'Customer cannot pay someone else\'s order or cancel after confirmation', function () use ($client, $val, &$onlineOrder) {
    $c = $client();
    $c->login('pelanggan2@demo.test');
    $c->post("/orders/$onlineOrder/pay", ['outcome' => 'success']);
    expect($c->lastStatus === 404, 'foreign payment status ' . $c->lastStatus);
    $o = $client();
    $o->login('pelanggan@demo.test');
    $soId = (int) $val('SELECT so.id FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE o.order_number = ?', [$onlineOrder]);
    $o->get("/orders/$onlineOrder");
    $o->post("/orders/$onlineOrder/cancel", ['seller_order_id' => (string) $soId]);
    return $val('SELECT status FROM seller_orders WHERE id = ?', [$soId]) === 'confirmed' ?: 'confirmed order was cancelled by customer';
});

// ================================================================ ADMINISTRATOR
test('TC-ADM-01', 'Non-administrators get 403 on admin pages and actions', function () use ($client, $val) {
    $c = $client();
    $c->login('kantin@demo.test');
    foreach (['/admin', '/admin/users', '/admin/orders', '/admin/settings'] as $p) {
        $c->get($p);
        expect($c->lastStatus === 403, "$p -> {$c->lastStatus}");
    }
    $c->post('/admin/users/6/status', ['status' => 'suspended', 'reason' => 'x']);
    expect($c->lastStatus === 403, 'POST ' . $c->lastStatus);
    return $val('SELECT status FROM users WHERE id = 6') === 'active' ?: 'user suspended by non-admin';
});

test('TC-ADM-02', 'Approving a seller application makes the account a seller (audited)', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/sellers');
    $c->post('/admin/sellers/5/decision', ['action' => 'approve']);
    expect($val('SELECT status FROM seller_profiles WHERE id = 5') === 'approved', 'not approved');
    expect($val('SELECT role FROM users WHERE id = 8') === 'seller', 'role not seller');
    expect((int) $val("SELECT COUNT(*) FROM audit_logs WHERE action = 'seller.approved' AND entity_id = 5") === 1, 'not audited');
    $s = $client();
    $s->login('pemohon@demo.test');
    $s->get('/seller');
    return $s->lastStatus === 200 ?: 'new seller cannot open dashboard';
});

test('TC-ADM-03', 'Approving a pending product makes it visible; rejecting needs a reason', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/products?status=pending');
    $c->post('/admin/products/21/moderate', ['action' => 'approve']);
    expect($val('SELECT approval_status FROM products WHERE id = 21') === 'approved', 'not approved');
    $pub = $client();
    $pub->get('/product/puding-roti-karamel');
    expect($pub->lastStatus === 200, 'approved product not public');
    $new = (int) $val("SELECT id FROM products WHERE name = 'Kuih Seri Muka Ujian'");
    $c->post("/admin/products/$new/moderate", ['action' => 'reject', 'note' => '']);
    expect($val('SELECT approval_status FROM products WHERE id = ?', [$new]) === 'pending', 'rejected without reason');
    $c->post("/admin/products/$new/moderate", ['action' => 'reject', 'note' => 'Sila tambah gambar sebenar']);
    expect($val('SELECT approval_status FROM products WHERE id = ?', [$new]) === 'rejected', 'not rejected');
    return (int) $val("SELECT COUNT(*) FROM audit_logs WHERE entity_type = 'product'") >= 2 ?: 'moderation not audited';
});

test('TC-ADM-04', 'Suspended users cannot sign in, and are signed out immediately', function () use ($client, $val) {
    $victim = $client();
    $victim->login('pelanggan2@demo.test');
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/users/7');
    $c->post('/admin/users/7/status', ['status' => 'suspended', 'reason' => '']);
    expect($val('SELECT status FROM users WHERE id = 7') === 'active', 'suspended without reason');
    $c->post('/admin/users/7/status', ['status' => 'suspended', 'reason' => 'Ujian penggantungan']);
    expect($val('SELECT status FROM users WHERE id = 7') === 'suspended', 'not suspended');
    $victim->get('/orders');
    expect($victim->lastStatus === 302, 'existing session still works');
    $v2 = $client();
    $v2->login('pelanggan2@demo.test');
    $v2->follow();
    expect($v2->contains('digantung'), 'no suspended message');
    $c->post('/admin/users/7/status', ['status' => 'active']);
    return $val('SELECT status FROM users WHERE id = 7') === 'active' ?: 'not reactivated';
});

test('TC-ADM-05', 'Self-lockout and last-administrator protection', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/users/1');
    $c->post('/admin/users/1/status', ['status' => 'suspended', 'reason' => 'ujian']);
    expect($val('SELECT status FROM users WHERE id = 1') === 'active', 'admin suspended self');
    $c->post('/admin/users/1/role', ['role' => 'demote', 'confirm_password' => 'Demo@1234']);
    return $val('SELECT role FROM users WHERE id = 1') === 'admin' ?: 'admin demoted self';
});

test('TC-ADM-06', 'Promoting to administrator requires your own password and is audited', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $uid = (int) $val("SELECT id FROM users WHERE email = 'hack@example.test'");
    $c->get("/admin/users/$uid");
    $c->post("/admin/users/$uid/role", ['role' => 'admin', 'confirm_password' => 'wrong-password']);
    expect($val('SELECT role FROM users WHERE id = ?', [$uid]) === 'customer', 'promoted with wrong password');
    $c->post("/admin/users/$uid/role", ['role' => 'admin', 'confirm_password' => 'Demo@1234']);
    expect($val('SELECT role FROM users WHERE id = ?', [$uid]) === 'admin', 'not promoted');
    expect((int) $val("SELECT COUNT(*) FROM audit_logs WHERE action = 'user.role_changed' AND entity_id = ?", [$uid]) === 1, 'not audited');
    $c->post("/admin/users/$uid/role", ['role' => 'demote', 'confirm_password' => 'Demo@1234']);
    return $val('SELECT role FROM users WHERE id = ?', [$uid]) === 'customer' ?: 'not demoted back';
});

test('TC-ADM-07', 'A category in use cannot be deleted; an empty one can', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/categories');
    $c->post('/admin/categories/1/delete');
    expect((bool) $val('SELECT id FROM categories WHERE id = 1'), 'category with products deleted');
    $c->post('/admin/categories', ['name' => 'Kategori Ujian', 'description' => '', 'sort_order' => '99', 'is_visible' => '1']);
    $id = (int) $val("SELECT id FROM categories WHERE name = 'Kategori Ujian'");
    expect($id > 0, 'category not created');
    $c->post("/admin/categories/$id/delete");
    return !$val('SELECT id FROM categories WHERE id = ?', [$id]) ?: 'empty category not deleted';
});

test('TC-ADM-08', 'Admin status override needs a reason, is audited, and refunds are flagged', function () use ($client, $val, &$onlineOrder) {
    $c = $client();
    $c->login('admin@demo.test');
    $soId = (int) $val('SELECT so.id FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE o.order_number = ?', [$onlineOrder]);
    $c->get("/admin/orders/$onlineOrder");
    $c->post("/admin/orders/$onlineOrder/override", ['seller_order_id' => (string) $soId, 'to' => 'cancelled', 'reason' => '']);
    expect($val('SELECT status FROM seller_orders WHERE id = ?', [$soId]) === 'confirmed', 'override without reason');
    $stock = (int) $val('SELECT stock FROM products WHERE id = 7');
    $c->post("/admin/orders/$onlineOrder/override", ['seller_order_id' => (string) $soId, 'to' => 'cancelled', 'reason' => 'Aduan pelanggan: gerai tutup']);
    expect($val('SELECT status FROM seller_orders WHERE id = ?', [$soId]) === 'cancelled', 'not cancelled');
    expect($val('SELECT payment_status FROM seller_orders WHERE id = ?', [$soId]) === 'refund_due', 'paid order not flagged refund_due');
    expect((int) $val('SELECT stock FROM products WHERE id = 7') === $stock + 2, 'stock not restored');
    expect((int) $val("SELECT COUNT(*) FROM audit_logs WHERE action = 'order.override' AND entity_id = ?", [$soId]) === 1, 'not audited');
    $c->post("/admin/orders/$onlineOrder/note", ['note' => 'Pelanggan dihubungi untuk bayaran balik manual.']);
    return (int) $val('SELECT COUNT(*) FROM order_notes') === 1 ?: 'note not saved';
});

test('TC-ADM-09', 'Temporary password reset forces a password change at next sign-in', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/users/6');
    $c->post('/admin/users/6/reset-password');
    $c->follow();
    expect((bool) preg_match('/Kv\d{4}-[a-f0-9]{5}/', $c->lastBody, $m), 'temporary password not shown once');
    $temp = $m[0];
    expect((int) $val("SELECT COUNT(*) FROM audit_logs WHERE details LIKE ?", ['%' . $temp . '%']) === 0, 'temporary password written to audit log');
    $u = $client();
    $u->login('pelanggan@demo.test', $temp);
    expect(str_contains($u->lastLocation, '/account/password'), 'not sent to change password');
    $u->get('/orders');
    expect(str_contains($u->lastLocation, '/account/password'), 'other pages reachable before change');
    $u->get('/account/password');
    $u->post('/account/password', ['current_password' => $temp, 'password' => 'Demo@1234', 'password_confirmation' => 'Demo@1234']);
    $u->get('/orders');
    return $u->lastStatus === 200 ?: 'still blocked after changing password';
});

test('TC-ADM-10', 'Suspending a shop hides all its products from the public', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin/sellers?status=approved');
    $c->post('/admin/sellers/3/decision', ['action' => 'suspend', 'note' => 'Ujian penggantungan kedai']);
    expect($val('SELECT status FROM seller_profiles WHERE id = 3') === 'suspended', 'not suspended');
    $p = $client();
    $p->get('/products?q=kalkulator');
    expect(!$p->contains('Kalkulator Saintifik'), 'product still listed');
    $p->get('/shop/kedai-pena-biru');
    expect($p->lastStatus === 404, 'shop page still public');
    $s = $client();
    $s->login('pena@demo.test');
    $s->get('/seller');
    expect($s->lastStatus === 302 && str_contains($s->lastLocation, '/seller/apply'), 'suspended seller reached dashboard');
    $c->post('/admin/sellers/3/decision', ['action' => 'reactivate']);
    return $val('SELECT status FROM seller_profiles WHERE id = 3') === 'approved' ?: 'not reactivated';
});

test('TC-ADM-11', 'Admin dashboard metrics match the database', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $c->get('/admin?range=30');
    $sales = (float) $val("SELECT COALESCE(SUM(subtotal),0) FROM seller_orders WHERE status = 'completed' AND completed_at >= ?", [date('Y-m-d 00:00:00', strtotime('-29 days'))]);
    expect($c->contains('RM' . number_format($sales, 2)), 'sales RM' . number_format($sales, 2) . ' not shown');
    $total = (int) $val('SELECT COUNT(*) FROM seller_orders');
    return (bool) preg_match('#Pesanan kedai</span><span class="stat__value">' . $total . '<#', $c->lastBody) ?: "order count $total not shown";
});

// ================================================================ REVIEWS & NOTIFICATIONS
test('TC-REV-01', 'Reviews: only for completed purchases, once per item, rating 1–5', function () use ($client, $val, $multiOrder) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $done = (int) $val("SELECT oi.id FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id JOIN orders o ON o.id = so.order_id WHERE o.order_number = ? AND so.status = 'completed' LIMIT 1", [$multiOrder]);
    $notDone = (int) $val("SELECT oi.id FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id WHERE so.status <> 'completed' LIMIT 1");
    $other = (int) $val("SELECT oi.id FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id JOIN orders o ON o.id = so.order_id WHERE o.customer_id = 7 AND so.status = 'completed' LIMIT 1");
    $c->get("/orders/$multiOrder");
    $c->post('/reviews', ['order_item_id' => (string) $done, 'rating' => '9', 'comment' => '']);
    expect(!$val('SELECT id FROM reviews WHERE order_item_id = ?', [$done]), 'rating 9 accepted');
    $c->post('/reviews', ['order_item_id' => (string) $notDone, 'rating' => '5', 'comment' => 'x']);
    $c->post('/reviews', ['order_item_id' => (string) $other, 'rating' => '5', 'comment' => 'x']);
    expect((int) $val('SELECT COUNT(*) FROM reviews WHERE order_item_id IN (?, ?)', [$notDone, $other]) === 0, 'ineligible review saved');
    $c->post('/reviews', ['order_item_id' => (string) $done, 'rating' => '4', 'comment' => '<b>Sedap</b> dan panas']);
    $c->post('/reviews', ['order_item_id' => (string) $done, 'rating' => '1', 'comment' => 'kali kedua']);
    expect((int) $val('SELECT COUNT(*) FROM reviews WHERE order_item_id = ?', [$done]) === 1, 'duplicate review');
    $p = $client();
    $p->get('/product/nasi-kerabu-ayam-percik');
    return ($p->contains('&lt;b&gt;Sedap&lt;/b&gt;') && $p->contains('4.0')) ?: 'review not shown escaped with rating';
});

test('TC-REV-02', 'Admin can hide a review (reason required); it disappears publicly', function () use ($client, $val) {
    $c = $client();
    $c->login('admin@demo.test');
    $rid = (int) $val('SELECT id FROM reviews LIMIT 1');
    $c->get('/admin/reviews');
    $c->post("/admin/reviews/$rid", ['note' => 'Bahasa tidak sesuai']);
    expect($val('SELECT status FROM reviews WHERE id = ?', [$rid]) === 'hidden', 'not hidden');
    $p = $client();
    $p->get('/product/nasi-kerabu-ayam-percik');
    return !$p->contains('Sedap') ?: 'hidden review still public';
});

test('TC-NOT-01', 'Notifications list and mark-as-read only touch your own notifications', function () use ($client, $val) {
    $c = $client();
    $c->login('pelanggan@demo.test');
    $foreign = (int) $val('SELECT id FROM notifications WHERE user_id <> 6 LIMIT 1');
    $c->get('/notifications');
    expect($c->lastStatus === 200 && $c->contains('Pesanan'), 'list not shown');
    $c->post('/notifications/read', ['id' => (string) $foreign]);
    expect($c->lastStatus === 404, 'foreign notification status ' . $c->lastStatus);
    $c->post('/notifications/read', ['id' => 'all']);
    return (int) $val('SELECT COUNT(*) FROM notifications WHERE user_id = 6 AND is_read = 0') === 0 ?: 'not all read';
});

test('TC-INT-01', 'Database integrity: no negative stock, totals match items, no orphans', function () use ($val) {
    expect((int) $val('SELECT COUNT(*) FROM products WHERE stock < 0') === 0, 'negative stock');
    $bad = (int) $val('SELECT COUNT(*) FROM seller_orders so WHERE ABS(so.subtotal - (SELECT SUM(line_total) FROM order_items WHERE seller_order_id = so.id)) > 0.001');
    expect($bad === 0, "$bad seller orders whose subtotal != sum of items");
    $bad = (int) $val('SELECT COUNT(*) FROM order_items WHERE ABS(line_total - unit_price * quantity) > 0.001');
    expect($bad === 0, "$bad order lines with wrong line_total");
    $fk = $val('PRAGMA foreign_key_check');
    return $fk === false ?: 'foreign key violations found';
});

// ---------------------------------------------------------------- report
$passed = count(array_filter($results, fn ($r) => $r[2]));
$total = count($results);
printf("\n%d of %d tests passed.\n", $passed, $total);

$md = "# Automated test results\n\n";
$md .= "- Run: " . date('Y-m-d H:i:s') . " (Asia/Kuala_Lumpur)\n";
$md .= "- Environment: PHP " . PHP_VERSION . " built-in server, **SQLite** test database built from database/schema.sql + database/seed.sql\n";
$md .= "- Result: **$passed of $total passed**\n\n";
$md .= "> These results were produced by `php tests/run_tests.php` and were not edited by hand. They do not cover MySQL/MariaDB-specific behaviour (row locking with SELECT … FOR UPDATE, ON UPDATE timestamps) or Apache .htaccess rules; see docs/TESTING.md for the manual XAMPP checklist.\n\n";
$md .= "| ID | Test | Status | Observed failure |\n|---|---|---|---|\n";
foreach ($results as [$id, $name, $ok, $detail]) {
    $md .= "| $id | " . str_replace('|', '\|', $name) . ' | ' . ($ok ? 'Pass' : '**Fail**') . ' | ' . str_replace('|', '\|', $detail) . " |\n";
}
@mkdir(__DIR__ . '/results', 0777, true);
file_put_contents(__DIR__ . '/results/latest.md', $md);

// clean up uploads created by the tests (random 32-hex names only; demo images are kept)
foreach (glob(dirname(__DIR__) . '/public/uploads/*/*') as $f) {
    if (preg_match('#/[a-f0-9]{32}\.(jpg|png|webp)$#', $f) && filemtime($f) >= ($_SERVER['REQUEST_TIME'] ?? time()) - 600) {
        @unlink($f);
    }
}
exit($passed === $total ? 0 : 1);
