-- =====================================================================
-- E-FoodHub — DEMONSTRATION DATA (development only)
-- Generated 2026-10-26 11:34:09 by database/seed.php
-- All accounts: <name>@demo.test / password: Demo@1234
-- Import AFTER schema.sql. NEVER import this into a production database.
-- Product images are illustrated placeholders, not photographs of real items.
-- =====================================================================

USE kvpm_efoodhub;

INSERT INTO users (id, name, email, phone, password_hash, role, status, created_at, updated_at) VALUES
  (1, 'Pentadbir Demo', 'admin@demo.test', '013-000 0001', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'admin', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (2, 'Su Demo (Gerai Nasi)', 'kantin@demo.test', '013-000 0002', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'seller', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (3, 'Amin Demo (Kopi Kampus)', 'kopi@demo.test', '013-000 0003', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'seller', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (4, 'Lina Demo (Pena Biru)', 'pena@demo.test', '013-000 0004', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'seller', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (5, 'Hafiz Demo (Snek)', 'snek@demo.test', '013-000 0005', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'seller', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (6, 'Pelanggan Demo', 'pelanggan@demo.test', '013-000 0006', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'customer', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (7, 'Pelanggan Demo Dua', 'pelanggan2@demo.test', '013-000 0007', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'customer', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00'),
  (8, 'Pemohon Kedai Demo', 'pemohon@demo.test', '013-000 0008', '$2y$10$oWCjtufi9nrkDNJRr/BNFuO5SnzgnTmSh78fu9t3Ix0q081VPz0UO', 'customer', 'active', '2026-09-16 12:00:00', '2026-09-16 12:00:00');

INSERT INTO categories (id, name, slug, description, image_path, is_visible, sort_order) VALUES
  (1, 'Makanan', 'makanan', 'Nasi, mee dan hidangan panas dari gerai kampus.', 'categories/demo-makanan.png', 1, 10),
  (2, 'Minuman', 'minuman', 'Air panas, air sejuk dan jus segar.', 'categories/demo-minuman.png', 1, 20),
  (3, 'Snek', 'snek', 'Kuih, kerepek dan makanan ringan.', 'categories/demo-snek.png', 1, 30),
  (4, 'Alat Tulis', 'alat-tulis', 'Keperluan kelas dan bengkel.', 'categories/demo-alat-tulis.png', 1, 40),
  (5, 'Aksesori', 'aksesori', 'Barangan kecil untuk kegunaan harian.', 'categories/demo-aksesori.png', 1, 50),
  (6, 'Cenderamata', 'cenderamata', 'Barangan rasmi persatuan dan program pelajar.', 'categories/demo-cenderamata.png', 1, 60);

INSERT INTO seller_profiles (id, user_id, shop_name, slug, description, phone, collection_location, collection_hours, logo_path, status, reviewed_by, reviewed_at, created_at) VALUES
  (1, 2, 'Gerai Nasi Mak Su', 'gerai-nasi-mak-su', 'Nasi dan lauk panas dimasak setiap pagi. Tempah sebelum 11 pagi untuk makan tengah hari tanpa beratur.', '013-000 0010', 'Kantin Utama, Gerai 3', 'Isnin–Jumaat, 7.30 pagi – 2.00 petang', 'shops/demo-gerai-nasi-mak-su.png', 'approved', 1, '2026-09-18 12:00:00', '2026-09-17 12:00:00'),
  (2, 3, 'Kopi Kampus', 'kopi-kampus', 'Teh tarik, kopi dan minuman sejuk. Bancuhan dibuat apabila pesanan disahkan.', '013-000 0011', 'Kantin Utama, Gerai 5', 'Isnin–Jumaat, 7.00 pagi – 5.00 petang', 'shops/demo-kopi-kampus.png', 'approved', 1, '2026-09-18 12:00:00', '2026-09-17 12:00:00'),
  (3, 4, 'Kedai Pena Biru', 'kedai-pena-biru', 'Alat tulis, kalkulator dan keperluan bengkel pada harga pelajar.', '013-000 0012', 'Blok Akademik, Aras 1', 'Isnin–Jumaat, 8.00 pagi – 4.30 petang', 'shops/demo-kedai-pena-biru.png', 'approved', 1, '2026-09-18 12:00:00', '2026-09-17 12:00:00'),
  (4, 5, 'Snek Rangup Kelantan', 'snek-rangup-kelantan', 'Keropok lekor, kerepek dan kuih tradisional Kelantan. Digoreng segar setiap hari.', '013-000 0013', 'Dataran Pelajar, Kiosk 2', 'Isnin–Jumaat, 10.00 pagi – 6.00 petang', 'shops/demo-snek-rangup-kelantan.png', 'approved', 1, '2026-09-18 12:00:00', '2026-09-17 12:00:00'),
  (5, 8, 'Kek Lapis Cik Ros', 'kek-lapis-cik-ros', 'Kek lapis dan biskut buatan sendiri untuk tempahan majlis kecil.', '013-000 0014', 'Asrama Puteri, Blok C', 'Hujung minggu', 'shops/demo-kek-lapis-cik-ros.png', 'pending', NULL, NULL, '2026-10-25 12:00:00');

INSERT INTO products (id, seller_id, category_id, name, slug, description, price, stock, sku, is_available, approval_status, created_at, updated_at) VALUES
  (1, 1, 1, 'Nasi Lemak Bungkus', 'nasi-lemak-bungkus', 'Nasi lemak santan dengan sambal ikan bilis, telur rebus separuh, timun dan kacang. Dibungkus daun pisang.', 3.00, 40, NULL, 1, 'approved', '2026-09-21 09:00:00', '2026-09-21 09:00:00'),
  (2, 1, 1, 'Nasi Kerabu Ayam Percik', 'nasi-kerabu-ayam-percik', 'Nasi biru bunga telang dengan ulam, kerisik, budu dan ayam percik bakar.', 7.50, 18, NULL, 1, 'approved', '2026-09-22 10:07:00', '2026-09-22 10:07:00'),
  (3, 1, 1, 'Nasi Goreng Kampung', 'nasi-goreng-kampung', 'Nasi goreng dengan ikan bilis, kangkung dan telur mata. Tahap pedas sederhana.', 6.00, 25, NULL, 1, 'approved', '2026-09-23 11:14:00', '2026-09-23 11:14:00'),
  (4, 1, 1, 'Mee Goreng Mamak', 'mee-goreng-mamak', 'Mee kuning digoreng dengan tauhu, kentang, sayur dan sos istimewa.', 5.50, 20, NULL, 1, 'approved', '2026-09-24 12:21:00', '2026-09-24 12:21:00'),
  (5, 1, 1, 'Laksam Kelantan', 'laksam-kelantan', 'Laksam gulung lembut dengan kuah santan ikan, ulam dan sambal. Stok terhad setiap hari.', 5.00, 4, NULL, 1, 'approved', '2026-09-25 13:28:00', '2026-09-25 13:28:00'),
  (6, 2, 2, 'Teh Tarik', 'teh-tarik', 'Teh susu ditarik berbuih. Pilih panas atau ais dalam nota pesanan.', 2.00, 60, NULL, 1, 'approved', '2026-09-26 14:35:00', '2026-09-26 14:35:00'),
  (7, 2, 2, 'Kopi O Ais', 'kopi-o-ais', 'Kopi hitam pekat dengan ais. Kurang manis jika diminta dalam nota.', 2.50, 50, NULL, 1, 'approved', '2026-09-27 15:42:00', '2026-09-27 15:42:00'),
  (8, 2, 2, 'Air Sirap Bandung', 'air-sirap-bandung', 'Sirap mawar dengan susu sejat, dihidang sejuk.', 2.50, 40, NULL, 1, 'approved', '2026-09-28 16:49:00', '2026-09-28 16:49:00'),
  (9, 2, 2, 'Milo Ais', 'milo-ais', 'Minuman coklat malt sejuk dengan serbuk Milo di atas.', 3.00, 45, NULL, 1, 'approved', '2026-09-29 09:56:00', '2026-09-29 09:56:00'),
  (10, 2, 2, 'Jus Tembikai', 'jus-tembikai', 'Jus tembikai segar tanpa gula tambahan. Habis dijual untuk hari ini.', 3.50, 0, NULL, 1, 'approved', '2026-09-30 10:03:00', '2026-09-30 10:03:00'),
  (11, 2, 2, 'Air Mineral 500ml', 'air-mineral-500ml', 'Air mineral botol 500ml.', 1.20, 80, NULL, 1, 'approved', '2026-10-01 11:10:00', '2026-10-01 11:10:00'),
  (12, 3, 4, 'Pen Gel Hitam (3 batang)', 'pen-gel-hitam-3-batang', 'Pen gel 0.5mm dakwat hitam, tiga batang setiap pek.', 4.50, 35, 'PB-PEN-03', 1, 'approved', '2026-10-02 12:17:00', '2026-10-02 12:17:00'),
  (13, 3, 4, 'Buku Nota A5 Bergaris', 'buku-nota-a5-bergaris', 'Buku nota A5, 80 helai bergaris. Sesuai untuk catatan kuliah.', 3.00, 50, 'PB-BN-A5', 1, 'approved', '2026-10-03 13:24:00', '2026-10-03 13:24:00'),
  (14, 3, 4, 'Kalkulator Saintifik', 'kalkulator-saintifik', 'Kalkulator saintifik 240 fungsi untuk subjek matematik dan elektrik.', 45.00, 3, 'PB-KAL-240', 1, 'approved', '2026-10-04 14:31:00', '2026-10-04 14:31:00'),
  (15, 3, 5, 'Pemacu USB 32GB', 'pemacu-usb-32gb', 'Pemacu kilat USB 3.0 32GB untuk menyimpan tugasan dan projek.', 18.00, 12, 'PB-USB-32', 1, 'approved', '2026-10-05 15:38:00', '2026-10-05 15:38:00'),
  (16, 3, 6, 'Tali Lanyard Program Pelajar', 'tali-lanyard-program-pelajar', 'Tali lanyard dengan klip untuk kad pelajar. Reka bentuk program demo.', 6.00, 30, 'PB-LAN-01', 1, 'approved', '2026-10-06 16:45:00', '2026-10-06 16:45:00'),
  (17, 4, 3, 'Keropok Lekor (10 batang)', 'keropok-lekor-10-batang', 'Keropok lekor ikan digoreng segar, disertakan sos cili.', 3.00, 30, NULL, 1, 'approved', '2026-10-07 09:52:00', '2026-10-07 09:52:00'),
  (18, 4, 3, 'Kerepek Pisang Pedas', 'kerepek-pisang-pedas', 'Kerepek pisang tanduk rangup dengan serbuk cili. Paket 150g.', 3.50, 22, NULL, 1, 'approved', '2026-10-08 10:59:00', '2026-10-08 10:59:00'),
  (19, 4, 3, 'Karipap Kentang (5 biji)', 'karipap-kentang-5-biji', 'Karipap inti kentang dan kari, kulit berpusar.', 3.00, 15, NULL, 1, 'approved', '2026-10-09 11:06:00', '2026-10-09 11:06:00'),
  (20, 4, 3, 'Kuih Akok (6 biji)', 'kuih-akok-6-biji', 'Kuih tradisional Kelantan daripada telur, santan dan gula melaka.', 4.00, 10, NULL, 1, 'approved', '2026-10-10 12:13:00', '2026-10-10 12:13:00'),
  (21, 4, 3, 'Puding Roti Karamel', 'puding-roti-karamel', 'Puding roti lembut dengan karamel. Produk baharu, menunggu kelulusan pentadbir.', 4.50, 12, NULL, 1, 'pending', '2026-10-11 13:20:00', '2026-10-11 13:20:00');

INSERT INTO product_images (product_id, path, is_primary, sort_order) VALUES
  (1, 'products/demo-nasi-lemak-bungkus.jpg', 1, 0),
  (2, 'products/demo-nasi-kerabu-ayam-percik.jpg', 1, 0),
  (3, 'products/demo-nasi-goreng-kampung.jpg', 1, 0),
  (4, 'products/demo-mee-goreng-mamak.jpg', 1, 0),
  (5, 'products/demo-laksam-kelantan.jpg', 1, 0),
  (6, 'products/demo-teh-tarik.jpg', 1, 0),
  (7, 'products/demo-kopi-o-ais.jpg', 1, 0),
  (8, 'products/demo-air-sirap-bandung.jpg', 1, 0),
  (9, 'products/demo-milo-ais.jpg', 1, 0),
  (10, 'products/demo-jus-tembikai.jpg', 1, 0),
  (11, 'products/demo-air-mineral-500ml.jpg', 1, 0),
  (12, 'products/demo-pen-gel-hitam-3-batang.jpg', 1, 0),
  (13, 'products/demo-buku-nota-a5-bergaris.jpg', 1, 0),
  (14, 'products/demo-kalkulator-saintifik.jpg', 1, 0),
  (15, 'products/demo-pemacu-usb-32gb.jpg', 1, 0),
  (16, 'products/demo-tali-lanyard-program-pelajar.jpg', 1, 0),
  (17, 'products/demo-keropok-lekor-10-batang.jpg', 1, 0),
  (18, 'products/demo-kerepek-pisang-pedas.jpg', 1, 0),
  (19, 'products/demo-karipap-kentang-5-biji.jpg', 1, 0),
  (20, 'products/demo-kuih-akok-6-biji.jpg', 1, 0),
  (21, 'products/demo-puding-roti-karamel.jpg', 1, 0);

INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (1, 'KV261006-DEMO1', 6, 'cash', 'Pelanggan Demo', '013-000 0006', NULL, 17.50, 'demo-token-1', '2026-10-06 08:10:00', '2026-10-06 08:10:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (1, 1, 1, 'KV261006-DEMO1-1', 'completed', 'paid', 13.50, 0, '2026-10-06 08:15:00', '2026-10-06 09:05:00', NULL, '2026-10-06 08:10:00', '2026-10-06 09:05:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (1, 1, 'Nasi Lemak Bungkus', 3.00, 2, 6.00), (1, 2, 'Nasi Kerabu Ayam Percik', 7.50, 1, 7.50);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (1, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-06 08:10:00'), (1, 'pending', 'confirmed', 2, 'seller', NULL, '2026-10-06 08:15:00'), (1, 'confirmed', 'preparing', 2, 'seller', NULL, '2026-10-06 08:31:00'), (1, 'preparing', 'ready', 2, 'seller', NULL, '2026-10-06 08:47:00'), (1, 'ready', 'completed', 2, 'seller', NULL, '2026-10-06 09:03:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (1, 1, 'cash', 13.50, 'success', 'CASH-DEMO1', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-06 09:05:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (2, 1, 2, 'KV261006-DEMO1-2', 'completed', 'paid', 4.00, 0, '2026-10-06 08:15:00', '2026-10-06 09:05:00', NULL, '2026-10-06 08:10:00', '2026-10-06 09:05:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (2, 6, 'Teh Tarik', 2.00, 2, 4.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (2, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-06 08:10:00'), (2, 'pending', 'confirmed', 3, 'seller', NULL, '2026-10-06 08:15:00'), (2, 'confirmed', 'preparing', 3, 'seller', NULL, '2026-10-06 08:31:00'), (2, 'preparing', 'ready', 3, 'seller', NULL, '2026-10-06 08:47:00'), (2, 'ready', 'completed', 3, 'seller', NULL, '2026-10-06 09:03:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (1, 2, 'cash', 4.00, 'success', 'CASH-DEMO2', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-06 09:05:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (2, 'KV261010-DEMO2', 7, 'cash', 'Pelanggan Demo Dua', '013-000 0007', NULL, 10.50, 'demo-token-2', '2026-10-10 09:13:00', '2026-10-10 09:13:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (3, 2, 3, 'KV261010-DEMO2-1', 'completed', 'paid', 10.50, 0, '2026-10-10 09:18:00', '2026-10-10 10:08:00', NULL, '2026-10-10 09:13:00', '2026-10-10 10:08:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (3, 13, 'Buku Nota A5 Bergaris', 3.00, 2, 6.00), (3, 12, 'Pen Gel Hitam (3 batang)', 4.50, 1, 4.50);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (3, NULL, 'pending', 7, 'customer', 'Pesanan dibuat', '2026-10-10 09:13:00'), (3, 'pending', 'confirmed', 4, 'seller', NULL, '2026-10-10 09:18:00'), (3, 'confirmed', 'preparing', 4, 'seller', NULL, '2026-10-10 09:34:00'), (3, 'preparing', 'ready', 4, 'seller', NULL, '2026-10-10 09:50:00'), (3, 'ready', 'completed', 4, 'seller', NULL, '2026-10-10 10:06:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (2, 3, 'cash', 10.50, 'success', 'CASH-DEMO3', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-10 10:08:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (3, 'KV261014-DEMO3', 6, 'online_sim', 'Pelanggan Demo', '013-000 0006', NULL, 14.50, 'demo-token-3', '2026-10-14 10:16:00', '2026-10-14 10:16:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (3, NULL, 'online_sim', 14.50, 'success', 'SIM-DEMO3', 'Transaksi simulasi untuk demonstrasi. Tiada wang sebenar dipindahkan.', 6, '2026-10-14 10:18:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (4, 3, 1, 'KV261014-DEMO3-1', 'completed', 'paid', 14.50, 0, '2026-10-14 10:21:00', '2026-10-14 11:11:00', NULL, '2026-10-14 10:16:00', '2026-10-14 11:11:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (4, 1, 'Nasi Lemak Bungkus', 3.00, 3, 9.00), (4, 4, 'Mee Goreng Mamak', 5.50, 1, 5.50);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (4, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-14 10:16:00'), (4, 'pending', 'confirmed', 2, 'seller', NULL, '2026-10-14 10:21:00'), (4, 'confirmed', 'preparing', 2, 'seller', NULL, '2026-10-14 10:37:00'), (4, 'preparing', 'ready', 2, 'seller', NULL, '2026-10-14 10:53:00'), (4, 'ready', 'completed', 2, 'seller', NULL, '2026-10-14 11:09:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (4, 'KV261017-DEMO4', 7, 'cash', 'Pelanggan Demo Dua', '013-000 0007', NULL, 12.50, 'demo-token-4', '2026-10-17 11:19:00', '2026-10-17 11:19:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (5, 4, 4, 'KV261017-DEMO4-1', 'completed', 'paid', 9.50, 0, '2026-10-17 11:24:00', '2026-10-17 12:14:00', NULL, '2026-10-17 11:19:00', '2026-10-17 12:14:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (5, 17, 'Keropok Lekor (10 batang)', 3.00, 2, 6.00), (5, 18, 'Kerepek Pisang Pedas', 3.50, 1, 3.50);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (5, NULL, 'pending', 7, 'customer', 'Pesanan dibuat', '2026-10-17 11:19:00'), (5, 'pending', 'confirmed', 5, 'seller', NULL, '2026-10-17 11:24:00'), (5, 'confirmed', 'preparing', 5, 'seller', NULL, '2026-10-17 11:40:00'), (5, 'preparing', 'ready', 5, 'seller', NULL, '2026-10-17 11:56:00'), (5, 'ready', 'completed', 5, 'seller', NULL, '2026-10-17 12:12:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (4, 5, 'cash', 9.50, 'success', 'CASH-DEMO5', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-17 12:14:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (6, 4, 2, 'KV261017-DEMO4-2', 'completed', 'paid', 3.00, 0, '2026-10-17 11:24:00', '2026-10-17 12:14:00', NULL, '2026-10-17 11:19:00', '2026-10-17 12:14:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (6, 9, 'Milo Ais', 3.00, 1, 3.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (6, NULL, 'pending', 7, 'customer', 'Pesanan dibuat', '2026-10-17 11:19:00'), (6, 'pending', 'confirmed', 3, 'seller', NULL, '2026-10-17 11:24:00'), (6, 'confirmed', 'preparing', 3, 'seller', NULL, '2026-10-17 11:40:00'), (6, 'preparing', 'ready', 3, 'seller', NULL, '2026-10-17 11:56:00'), (6, 'ready', 'completed', 3, 'seller', NULL, '2026-10-17 12:12:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (4, 6, 'cash', 3.00, 'success', 'CASH-DEMO6', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-17 12:14:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (5, 'KV261020-DEMO5', 6, 'cash', 'Pelanggan Demo', '013-000 0006', NULL, 12.00, 'demo-token-5', '2026-10-20 08:22:00', '2026-10-20 08:22:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (7, 5, 1, 'KV261020-DEMO5-1', 'completed', 'paid', 12.00, 0, '2026-10-20 08:27:00', '2026-10-20 09:17:00', NULL, '2026-10-20 08:22:00', '2026-10-20 09:17:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (7, 3, 'Nasi Goreng Kampung', 6.00, 2, 12.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (7, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-20 08:22:00'), (7, 'pending', 'confirmed', 2, 'seller', NULL, '2026-10-20 08:27:00'), (7, 'confirmed', 'preparing', 2, 'seller', NULL, '2026-10-20 08:43:00'), (7, 'preparing', 'ready', 2, 'seller', NULL, '2026-10-20 08:59:00'), (7, 'ready', 'completed', 2, 'seller', NULL, '2026-10-20 09:15:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (5, 7, 'cash', 12.00, 'success', 'CASH-DEMO7', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-20 09:17:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (6, 'KV261022-DEMO6', 7, 'cash', 'Pelanggan Demo Dua', '013-000 0007', NULL, 5.00, 'demo-token-6', '2026-10-22 09:25:00', '2026-10-22 09:25:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (8, 6, 2, 'KV261022-DEMO6-1', 'cancelled', 'void', 5.00, 1, NULL, NULL, '2026-10-22 09:28:00', '2026-10-22 09:25:00', '2026-10-22 09:28:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (8, 8, 'Air Sirap Bandung', 2.50, 2, 5.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (8, NULL, 'pending', 7, 'customer', 'Pesanan dibuat', '2026-10-22 09:25:00'), (8, 'pending', 'cancelled', 7, 'customer', NULL, '2026-10-22 09:28:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (7, 'KV261024-DEMO7', 6, 'cash', 'Pelanggan Demo', '013-000 0006', NULL, 5.50, 'demo-token-7', '2026-10-24 10:28:00', '2026-10-24 10:28:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (9, 7, 1, 'KV261024-DEMO7-1', 'completed', 'paid', 3.00, 0, '2026-10-24 10:33:00', '2026-10-24 11:23:00', NULL, '2026-10-24 10:28:00', '2026-10-24 11:23:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (9, 1, 'Nasi Lemak Bungkus', 3.00, 1, 3.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (9, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-24 10:28:00'), (9, 'pending', 'confirmed', 2, 'seller', NULL, '2026-10-24 10:33:00'), (9, 'confirmed', 'preparing', 2, 'seller', NULL, '2026-10-24 10:49:00'), (9, 'preparing', 'ready', 2, 'seller', NULL, '2026-10-24 11:05:00'), (9, 'ready', 'completed', 2, 'seller', NULL, '2026-10-24 11:21:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (7, 9, 'cash', 3.00, 'success', 'CASH-DEMO9', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-24 11:23:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (10, 7, 2, 'KV261024-DEMO7-2', 'completed', 'paid', 2.50, 0, '2026-10-24 10:33:00', '2026-10-24 11:23:00', NULL, '2026-10-24 10:28:00', '2026-10-24 11:23:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (10, 7, 'Kopi O Ais', 2.50, 1, 2.50);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (10, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-24 10:28:00'), (10, 'pending', 'confirmed', 3, 'seller', NULL, '2026-10-24 10:33:00'), (10, 'confirmed', 'preparing', 3, 'seller', NULL, '2026-10-24 10:49:00'), (10, 'preparing', 'ready', 3, 'seller', NULL, '2026-10-24 11:05:00'), (10, 'ready', 'completed', 3, 'seller', NULL, '2026-10-24 11:21:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (7, 10, 'cash', 2.50, 'success', 'CASH-DEMO10', 'Tunai diterima oleh penjual semasa pengambilan (demo)', NULL, '2026-10-24 11:23:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (8, 'KV261026-DEMO8', 6, 'online_sim', 'Pelanggan Demo', '013-000 0006', NULL, 5.00, 'demo-token-8', '2026-10-26 11:31:00', '2026-10-26 11:31:00');
INSERT INTO payments (order_id, seller_order_id, method, amount, status, reference, note, created_by, created_at) VALUES (8, NULL, 'online_sim', 5.00, 'success', 'SIM-DEMO8', 'Transaksi simulasi untuk demonstrasi. Tiada wang sebenar dipindahkan.', 6, '2026-10-26 11:33:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (11, 8, 1, 'KV261026-DEMO8-1', 'preparing', 'paid', 5.00, 0, '2026-10-26 11:36:00', NULL, NULL, '2026-10-26 11:31:00', '2026-10-26 11:31:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (11, 5, 'Laksam Kelantan', 5.00, 1, 5.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (11, NULL, 'pending', 6, 'customer', 'Pesanan dibuat', '2026-10-26 11:31:00'), (11, 'pending', 'confirmed', 2, 'seller', NULL, '2026-10-26 11:36:00'), (11, 'confirmed', 'preparing', 2, 'seller', NULL, '2026-10-26 11:52:00');
INSERT INTO orders (id, order_number, customer_id, payment_method, contact_name, contact_phone, notes, total, checkout_token, created_at, updated_at) VALUES (9, 'KV261026-DEMO9', 7, 'cash', 'Pelanggan Demo Dua', '013-000 0007', NULL, 8.00, 'demo-token-9', '2026-10-26 08:34:00', '2026-10-26 08:34:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (12, 9, 4, 'KV261026-DEMO9-1', 'pending', 'unpaid', 6.00, 0, NULL, NULL, NULL, '2026-10-26 08:34:00', '2026-10-26 08:34:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (12, 19, 'Karipap Kentang (5 biji)', 3.00, 2, 6.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (12, NULL, 'pending', 7, 'customer', 'Pesanan dibuat', '2026-10-26 08:34:00');
INSERT INTO seller_orders (id, order_id, seller_id, reference, status, payment_status, subtotal, stock_restored, confirmed_at, completed_at, cancelled_at, created_at, updated_at) VALUES (13, 9, 2, 'KV261026-DEMO9-2', 'ready', 'unpaid', 2.00, 0, '2026-10-26 08:39:00', NULL, NULL, '2026-10-26 08:34:00', '2026-10-26 08:34:00');
INSERT INTO order_items (seller_order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (13, 6, 'Teh Tarik', 2.00, 1, 2.00);
INSERT INTO order_status_history (seller_order_id, from_status, to_status, changed_by, actor_role, note, created_at) VALUES (13, NULL, 'pending', 7, 'customer', 'Pesanan dibuat', '2026-10-26 08:34:00'), (13, 'pending', 'confirmed', 3, 'seller', NULL, '2026-10-26 08:39:00'), (13, 'confirmed', 'preparing', 3, 'seller', NULL, '2026-10-26 08:55:00'), (13, 'preparing', 'ready', 3, 'seller', NULL, '2026-10-26 09:11:00');

INSERT INTO announcements (title, body, is_active, created_by, created_at) VALUES ('Versi demonstrasi', 'Laman ini menggunakan data contoh untuk projek akademik. Bayaran dalam talian ialah simulasi dan tiada wang sebenar dipindahkan.', 1, 1, '2026-09-26 12:00:00');
