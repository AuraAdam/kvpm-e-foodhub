<?php
/**
 * DEVELOPMENT / DEMONSTRATION SEED GENERATOR
 *
 * Writes database/seed.sql: demo accounts, shops, categories, products and a
 * few demo orders. Dates are relative to the moment you run this script, so
 * the dashboards always have recent demo data.
 *
 *   php database/seed.php                 (rewrites database/seed.sql)
 *   php database/seed.php --json          (prints product list for the image generator)
 *
 * All accounts end in @demo.test and use the password Demo@1234.
 * NEVER import seed.sql into a production database. No real student data is used.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const DEMO_PASSWORD = 'Demo@1234';

$categories = [
    // slug => [name, description, emoji]
    'makanan'     => ['Makanan', 'Nasi, mee dan hidangan panas dari gerai kampus.', '🍛'],
    'minuman'     => ['Minuman', 'Air panas, air sejuk dan jus segar.', '🥤'],
    'snek'        => ['Snek', 'Kuih, kerepek dan makanan ringan.', '🥟'],
    'alat-tulis'  => ['Alat Tulis', 'Keperluan kelas dan bengkel.', '✏️'],
    'aksesori'    => ['Aksesori', 'Barangan kecil untuk kegunaan harian.', '🎒'],
    'cenderamata' => ['Cenderamata', 'Barangan rasmi persatuan dan program pelajar.', '🎁'],
];

// Demo users: [key, name, email, phone, role]
$users = [
    ['admin',     'Pentadbir Demo',          'admin@demo.test',      '013-000 0001', 'admin'],
    ['kantin',    'Su Demo (Gerai Nasi)',    'kantin@demo.test',     '013-000 0002', 'seller'],
    ['kopi',      'Amin Demo (Kopi Kampus)', 'kopi@demo.test',       '013-000 0003', 'seller'],
    ['pena',      'Lina Demo (Pena Biru)',   'pena@demo.test',       '013-000 0004', 'seller'],
    ['snek',      'Hafiz Demo (Snek)',       'snek@demo.test',       '013-000 0005', 'seller'],
    ['pelanggan', 'Pelanggan Demo',          'pelanggan@demo.test',  '013-000 0006', 'customer'],
    ['pelanggan2','Pelanggan Demo Dua',      'pelanggan2@demo.test', '013-000 0007', 'customer'],
    ['pemohon',   'Pemohon Kedai Demo',      'pemohon@demo.test',    '013-000 0008', 'customer'],
];

// Shops: [owner key, name, slug, description, location, hours, status, emoji, colour]
$shops = [
    ['kantin', 'Gerai Nasi Mak Su', 'gerai-nasi-mak-su', 'Nasi dan lauk panas dimasak setiap pagi. Tempah sebelum 11 pagi untuk makan tengah hari tanpa beratur.', 'Kantin Utama, Gerai 3', 'Isnin–Jumaat, 7.30 pagi – 2.00 petang', 'approved', '🍛', '#F4E3C8'],
    ['kopi', 'Kopi Kampus', 'kopi-kampus', 'Teh tarik, kopi dan minuman sejuk. Bancuhan dibuat apabila pesanan disahkan.', 'Kantin Utama, Gerai 5', 'Isnin–Jumaat, 7.00 pagi – 5.00 petang', 'approved', '☕', '#E9DCCB'],
    ['pena', 'Kedai Pena Biru', 'kedai-pena-biru', 'Alat tulis, kalkulator dan keperluan bengkel pada harga pelajar.', 'Blok Akademik, Aras 1', 'Isnin–Jumaat, 8.00 pagi – 4.30 petang', 'approved', '✏️', '#DCE7F3'],
    ['snek', 'Snek Rangup Kelantan', 'snek-rangup-kelantan', 'Keropok lekor, kerepek dan kuih tradisional Kelantan. Digoreng segar setiap hari.', 'Dataran Pelajar, Kiosk 2', 'Isnin–Jumaat, 10.00 pagi – 6.00 petang', 'approved', '🥟', '#F3E0D2'],
    ['pemohon', 'Kek Lapis Cik Ros', 'kek-lapis-cik-ros', 'Kek lapis dan biskut buatan sendiri untuk tempahan majlis kecil.', 'Asrama Puteri, Blok C', 'Hujung minggu', 'pending', '🍰', '#F6E1E6'],
];

// Products: [shop slug, category slug, name, price, stock, emoji, description, approval, available, sku]
$products = [
    ['gerai-nasi-mak-su', 'makanan', 'Nasi Lemak Bungkus', '3.00', 40, '🍙', "Nasi lemak santan dengan sambal ikan bilis, telur rebus separuh, timun dan kacang. Dibungkus daun pisang.", 'approved', 1, null],
    ['gerai-nasi-mak-su', 'makanan', 'Nasi Kerabu Ayam Percik', '7.50', 18, '🍗', "Nasi biru bunga telang dengan ulam, kerisik, budu dan ayam percik bakar.", 'approved', 1, null],
    ['gerai-nasi-mak-su', 'makanan', 'Nasi Goreng Kampung', '6.00', 25, '🍳', "Nasi goreng dengan ikan bilis, kangkung dan telur mata. Tahap pedas sederhana.", 'approved', 1, null],
    ['gerai-nasi-mak-su', 'makanan', 'Mee Goreng Mamak', '5.50', 20, '🍜', "Mee kuning digoreng dengan tauhu, kentang, sayur dan sos istimewa.", 'approved', 1, null],
    ['gerai-nasi-mak-su', 'makanan', 'Laksam Kelantan', '5.00', 4, '🥣', "Laksam gulung lembut dengan kuah santan ikan, ulam dan sambal. Stok terhad setiap hari.", 'approved', 1, null],
    ['kopi-kampus', 'minuman', 'Teh Tarik', '2.00', 60, '🍵', "Teh susu ditarik berbuih. Pilih panas atau ais dalam nota pesanan.", 'approved', 1, null],
    ['kopi-kampus', 'minuman', 'Kopi O Ais', '2.50', 50, '☕', "Kopi hitam pekat dengan ais. Kurang manis jika diminta dalam nota.", 'approved', 1, null],
    ['kopi-kampus', 'minuman', 'Air Sirap Bandung', '2.50', 40, '🥤', "Sirap mawar dengan susu sejat, dihidang sejuk.", 'approved', 1, null],
    ['kopi-kampus', 'minuman', 'Milo Ais', '3.00', 45, '🧋', "Minuman coklat malt sejuk dengan serbuk Milo di atas.", 'approved', 1, null],
    ['kopi-kampus', 'minuman', 'Jus Tembikai', '3.50', 0, '🍉', "Jus tembikai segar tanpa gula tambahan. Habis dijual untuk hari ini.", 'approved', 1, null],
    ['kopi-kampus', 'minuman', 'Air Mineral 500ml', '1.20', 80, '💧', "Air mineral botol 500ml.", 'approved', 1, null],
    ['kedai-pena-biru', 'alat-tulis', 'Pen Gel Hitam (3 batang)', '4.50', 35, '🖊️', "Pen gel 0.5mm dakwat hitam, tiga batang setiap pek.", 'approved', 1, 'PB-PEN-03'],
    ['kedai-pena-biru', 'alat-tulis', 'Buku Nota A5 Bergaris', '3.00', 50, '📒', "Buku nota A5, 80 helai bergaris. Sesuai untuk catatan kuliah.", 'approved', 1, 'PB-BN-A5'],
    ['kedai-pena-biru', 'alat-tulis', 'Kalkulator Saintifik', '45.00', 3, '🧮', "Kalkulator saintifik 240 fungsi untuk subjek matematik dan elektrik.", 'approved', 1, 'PB-KAL-240'],
    ['kedai-pena-biru', 'aksesori', 'Pemacu USB 32GB', '18.00', 12, '💾', "Pemacu kilat USB 3.0 32GB untuk menyimpan tugasan dan projek.", 'approved', 1, 'PB-USB-32'],
    ['kedai-pena-biru', 'cenderamata', 'Tali Lanyard Program Pelajar', '6.00', 30, '🪪', "Tali lanyard dengan klip untuk kad pelajar. Reka bentuk program demo.", 'approved', 1, 'PB-LAN-01'],
    ['snek-rangup-kelantan', 'snek', 'Keropok Lekor (10 batang)', '3.00', 30, '🍢', "Keropok lekor ikan digoreng segar, disertakan sos cili.", 'approved', 1, null],
    ['snek-rangup-kelantan', 'snek', 'Kerepek Pisang Pedas', '3.50', 22, '🍌', "Kerepek pisang tanduk rangup dengan serbuk cili. Paket 150g.", 'approved', 1, null],
    ['snek-rangup-kelantan', 'snek', 'Karipap Kentang (5 biji)', '3.00', 15, '🥟', "Karipap inti kentang dan kari, kulit berpusar.", 'approved', 1, null],
    ['snek-rangup-kelantan', 'snek', 'Kuih Akok (6 biji)', '4.00', 10, '🧁', "Kuih tradisional Kelantan daripada telur, santan dan gula melaka.", 'approved', 1, null],
    ['snek-rangup-kelantan', 'snek', 'Puding Roti Karamel', '4.50', 12, '🍮', "Puding roti lembut dengan karamel. Produk baharu, menunggu kelulusan pentadbir.", 'pending', 1, null],
];

if (in_array('--json', $argv, true)) {
    $out = ['products' => [], 'shops' => [], 'categories' => []];
    foreach ($products as $p) {
        $out['products'][] = ['file' => 'demo-' . slug($p[2]) . '.jpg', 'emoji' => $p[5], 'name' => $p[2], 'category' => $p[1]];
    }
    foreach ($shops as $s) {
        $out['shops'][] = ['file' => 'demo-' . $s[2] . '.png', 'emoji' => $s[7], 'color' => $s[8]];
    }
    foreach ($categories as $slug => $c) {
        $out['categories'][] = ['file' => 'demo-' . $slug . '.png', 'emoji' => $c[2]];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function slug(string $s): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
}
function q(?string $v): string
{
    return $v === null ? 'NULL' : "'" . str_replace("'", "''", $v) . "'";
}
function ago(float $days, int $hour = 12, int $minute = 0): string
{
    return date('Y-m-d', strtotime('-' . (int) floor($days) . ' days')) . sprintf(' %02d:%02d:00', $hour, $minute);
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$hash = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');
$sql = [];
$sql[] = "-- =====================================================================";
$sql[] = "-- E-FoodHub — DEMONSTRATION DATA (development only)";
$sql[] = "-- Generated " . $now . " by database/seed.php";
$sql[] = "-- All accounts: <name>@demo.test / password: " . DEMO_PASSWORD;
$sql[] = "-- Import AFTER schema.sql. NEVER import this into a production database.";
$sql[] = "-- Product images are illustrated placeholders, not photographs of real items.";
$sql[] = "-- =====================================================================";
$sql[] = "";
$sql[] = "USE kvpm_efoodhub;";
$sql[] = "";

// Users
$userIds = [];
$rows = [];
foreach ($users as $i => [$key, $name, $email, $phone, $role]) {
    $userIds[$key] = $i + 1;
    $rows[] = sprintf("(%d, %s, %s, %s, %s, %s, 'active', %s, %s)", $i + 1, q($name), q($email), q($phone), q($hash), q($role), q(ago(40)), q(ago(40)));
}
$sql[] = "INSERT INTO users (id, name, email, phone, password_hash, role, status, created_at, updated_at) VALUES\n  " . implode(",\n  ", $rows) . ";";

// Categories
$catIds = [];
$rows = [];
$i = 0;
foreach ($categories as $slug => [$name, $desc]) {
    $i++;
    $catIds[$slug] = $i;
    $rows[] = sprintf("(%d, %s, %s, %s, %s, 1, %d)", $i, q($name), q($slug), q($desc), q('categories/demo-' . $slug . '.png'), $i * 10);
}
$sql[] = "\nINSERT INTO categories (id, name, slug, description, image_path, is_visible, sort_order) VALUES\n  " . implode(",\n  ", $rows) . ";";

// Shops
$shopIds = [];
$rows = [];
foreach ($shops as $i => [$owner, $name, $slug, $desc, $loc, $hours, $status]) {
    $shopIds[$slug] = $i + 1;
    $reviewed = $status === 'approved';
    $rows[] = sprintf(
        "(%d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        $i + 1, $userIds[$owner], q($name), q($slug), q($desc), q(sprintf('013-000 00%02d', 10 + $i)), q($loc), q($hours),
        q('shops/demo-' . $slug . '.png'), q($status), $reviewed ? (string) $userIds['admin'] : 'NULL', q($reviewed ? ago(38) : null), q(ago($reviewed ? 39 : 1))
    );
}
$sql[] = "\nINSERT INTO seller_profiles (id, user_id, shop_name, slug, description, phone, collection_location, collection_hours, logo_path, status, reviewed_by, reviewed_at, created_at) VALUES\n  " . implode(",\n  ", $rows) . ";";

// Products and images
$productIds = [];
$prices = [];
$rows = [];
$imgRows = [];
foreach ($products as $i => [$shop, $cat, $name, $price, $stock, $emoji, $desc, $approval, $available, $sku]) {
    $id = $i + 1;
    $pslug = slug($name);
    $productIds[$name] = $id;
    $prices[$name] = $price;
    $created = ago(35 - $i, 9 + ($i % 8), ($i * 7) % 60);
    $rows[] = sprintf("(%d, %d, %d, %s, %s, %s, %s, %d, %s, %d, %s, %s, %s)", $id, $shopIds[$shop], $catIds[$cat], q($name), q($pslug), q($desc), $price, $stock, q($sku), $available, q($approval), q($created), q($created));
    $imgRows[] = sprintf("(%d, %s, 1, 0)", $id, q('products/demo-' . $pslug . '.jpg'));
}
$sql[] = "\nINSERT INTO products (id, seller_id, category_id, name, slug, description, price, stock, sku, is_available, approval_status, created_at, updated_at) VALUES\n  " . implode(",\n  ", $rows) . ";";
$sql[] = "\nINSERT INTO product_images (product_id, path, is_primary, sort_order) VALUES\n  " . implode(",\n  ", $imgRows) . ";";

// Demo orders. Each: [customer, days ago, method, [[shop, status, payment_status, [[product, qty]...]]...]]
$orders = [
    ['pelanggan',  20, 'cash',       [['gerai-nasi-mak-su', 'completed', 'paid', [['Nasi Lemak Bungkus', 2], ['Nasi Kerabu Ayam Percik', 1]]], ['kopi-kampus', 'completed', 'paid', [['Teh Tarik', 2]]]]],
    ['pelanggan2', 16, 'cash',       [['kedai-pena-biru', 'completed', 'paid', [['Buku Nota A5 Bergaris', 2], ['Pen Gel Hitam (3 batang)', 1]]]]],
    ['pelanggan',  12, 'online_sim', [['gerai-nasi-mak-su', 'completed', 'paid', [['Nasi Lemak Bungkus', 3], ['Mee Goreng Mamak', 1]]]]],
    ['pelanggan2',  9, 'cash',       [['snek-rangup-kelantan', 'completed', 'paid', [['Keropok Lekor (10 batang)', 2], ['Kerepek Pisang Pedas', 1]]], ['kopi-kampus', 'completed', 'paid', [['Milo Ais', 1]]]]],
    ['pelanggan',   6, 'cash',       [['gerai-nasi-mak-su', 'completed', 'paid', [['Nasi Goreng Kampung', 2]]]]],
    ['pelanggan2',  4, 'cash',       [['kopi-kampus', 'cancelled', 'void', [['Air Sirap Bandung', 2]]]]],
    ['pelanggan',   2, 'cash',       [['gerai-nasi-mak-su', 'completed', 'paid', [['Nasi Lemak Bungkus', 1]]], ['kopi-kampus', 'completed', 'paid', [['Kopi O Ais', 1]]]]],
    ['pelanggan',   0, 'online_sim', [['gerai-nasi-mak-su', 'preparing', 'paid', [['Laksam Kelantan', 1]]]]],
    ['pelanggan2',  0, 'cash',       [['snek-rangup-kelantan', 'pending', 'unpaid', [['Karipap Kentang (5 biji)', 2]]], ['kopi-kampus', 'ready', 'unpaid', [['Teh Tarik', 1]]]]],
];
$flow = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
$orderId = 0;
$soId = 0;
$sql[] = "";
foreach ($orders as $n => [$customer, $days, $method, $parts]) {
    $orderId++;
    $created = ago($days, 8 + $n % 4, 10 + $n * 3);
    $createdTs = strtotime($created);
    $number = 'KV' . date('ymd', $createdTs) . '-DEMO' . ($n + 1);
    $total = 0;
    $soSql = [];
    foreach ($parts as $k => [$shop, $status, $pay, $items]) {
        $soId++;
        $sub = 0;
        $itemSql = [];
        foreach ($items as [$pname, $qty]) {
            $line = (int) round((float) $prices[$pname] * 100) * $qty;
            $sub += $line;
            $itemSql[] = sprintf("(%d, %d, %s, %s, %d, %s)", $soId, $productIds[$pname], q($pname), $prices[$pname], $qty, number_format($line / 100, 2, '.', ''));
        }
        $total += $sub;
        $subDec = number_format($sub / 100, 2, '.', '');
        $t = fn (int $min) => date('Y-m-d H:i:s', $createdTs + $min * 60);
        $confirmed = in_array($status, ['confirmed', 'preparing', 'ready', 'completed'], true) ? $t(5) : null;
        $completed = $status === 'completed' ? $t(55) : null;
        $cancelled = $status === 'cancelled' ? $t(3) : null;
        $soSql[] = sprintf(
            "INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (%d, %d, %d, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s);",
            $soId, $orderId, $shopIds[$shop], q($number . '-' . ($k + 1)), q($status), q($pay), $subDec, $status === 'cancelled' ? 1 : 0, q($confirmed), q($completed), q($cancelled), q($created), q($completed ?? $cancelled ?? $created)
        );
        $soSql[] = "INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES " . implode(', ', $itemSql) . ";";
        // status history
        $hist = [sprintf("(%d, NULL, 'pending', %d, 'customer', 'Pesanan dibuat', %s)", $soId, $userIds[$customer], q($created))];
        if ($status === 'cancelled') {
            $hist[] = sprintf("(%d, 'pending', 'cancelled', %d, 'customer', NULL, %s)", $soId, $userIds[$customer], q($cancelled));
        } else {
            $sellerUser = $userIds[array_values(array_filter($shops, fn ($s) => $s[2] === $shop))[0][0]];
            $reach = array_search($status, $flow, true);
            for ($s = 1; $s <= $reach; $s++) {
                $hist[] = sprintf("(%d, %s, %s, %d, 'seller', NULL, %s)", $soId, q($flow[$s - 1]), q($flow[$s]), $sellerUser, q($t(5 + ($s - 1) * 16)));
            }
        }
        $soSql[] = "INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES " . implode(', ', $hist) . ";";
        if ($method === 'cash' && $pay === 'paid') {
            $soSql[] = sprintf("INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (%d, %d, 'cash', %s, 'success', %s, 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, %s);",
                $orderId, $soId, $subDec, q('CASH-DEMO' . $soId), q($completed ?? $created));
        }
    }
    $sql[] = sprintf("INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (%d, %s, %d, %s, %s, %s, NULL, %s, %s, %s, %s);",
        $orderId, q($number), $userIds[$customer], q($method), q($users[$userIds[$customer] - 1][1]), q($users[$userIds[$customer] - 1][3]), number_format($total / 100, 2, '.', ''), q('demo-token-' . $orderId), q($created), q($created));
    if ($method === 'online_sim') {
        $sql[] = sprintf("INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (%d, NULL, 'online_sim', %s, 'success', %s, 'Transaksi simulasi untuk demonstrasi. Tiada wang sebenar dipindahkan.', %d, %s);",
            $orderId, number_format($total / 100, 2, '.', ''), q('SIM-DEMO' . $orderId), $userIds[$customer], q(date('Y-m-d H:i:s', $createdTs + 120)));
    }
    array_push($sql, ...$soSql);
}

$sql[] = "\nINSERT INTO announcements (title, body, is_active, created_by, created_at) VALUES (" . q('Versi demonstrasi') . ", " . q('Laman ini menggunakan data contoh untuk projek akademik. Bayaran dalam talian ialah simulasi dan tiada wang sebenar dipindahkan.') . ", 1, 1, " . q(ago(30)) . ");";

$file = __DIR__ . '/seed.sql';
file_put_contents($file, implode("\n", $sql) . "\n");
fwrite(STDOUT, "Wrote $file (" . count($users) . " users, " . count($products) . " products, " . count($orders) . " orders)\n");
