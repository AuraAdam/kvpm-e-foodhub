# User guide

The interface is in Bahasa Melayu. Menu names are shown in **bold** exactly as they appear on screen.

---

## Part A — Customer (Pelanggan)

### A1. Create an account
1. Press **Daftar** at the top right (on small phones: **Log masuk** → *Daftar*).
2. Enter your full name, e-mail, phone number and a password (at least 8 characters with letters and numbers).
3. Tick the terms box and press **Daftar akaun**. You are signed in straight away.

### A2. Find products
- Type in the search box at the top (*Cari nasi, air, alat tulis…*). Search looks at product names, descriptions and shop names.
- On **Produk**, use **Tapis produk** for category, price range (RM) and *Hanya produk yang ada stok*. Use **Susun** to sort by newest, price, name or best-selling.
- Badges on product cards: **Tinggal 3** = low stock, **Stok habis** = sold out.

### A3. Add to the cart
- Press the green **+** on a product card, or open the product, choose **Kuantiti** and press **Tambah ke troli**. **Beli sekarang** adds it and opens the cart.
- The cart (**Troli**) can hold products from several shops. Change quantities with − / +, or press **Buang**.
- If a line shows an orange/red note (sold out, not enough stock, no longer sold) fix it before ordering.

### A4. Place an order
1. In **Troli** press **Teruskan ke pesanan**.
2. Check your name and phone number; add a note for the seller if needed (*kurang pedas*, pickup time…).
3. Choose a payment method:
   - **Tunai semasa ambil** — pay the seller when you collect.
   - **Bayaran dalam talian (Simulasi)** — a demonstration only. You will see two buttons: *Simulasikan bayaran berjaya* or *…gagal*. No money is taken and no card details are asked for.
4. Press **Hantar pesanan**. You get an order number such as `KV261026-AB12C`.

### A5. Track and collect
- **Pesanan Saya** lists all your orders. Each shop's part appears as its own slip with a stamped status: *Menunggu → Disahkan → Sedang disediakan → Sedia diambil → Selesai*.
- The bell icon shows notifications when a status changes.
- Go to the stall shown on the slip and show your order number.
- While a part is still *Menunggu* you can press **Batalkan pesanan ini**. After the seller confirms, contact the seller (phone number on the slip).

### A6. Reviews, wishlist, reports
- When a slip shows *Selesai*, press **Tulis ulasan** under an item, choose 1–5 stars and optionally write a comment. One review per item bought.
- **Simpan untuk kemudian** on a product page saves it to **Senarai simpanan**.
- **Laporkan produk ini** (bottom of a product page) sends a report to the administrators.

### A7. Account
**Akaun saya** — change your name and phone; **Tukar kata laluan** to change your password. Forgot your password? See **Lupa kata laluan?** on the sign-in page: an administrator gives you a temporary password, and you must choose a new one when you sign in.

---

## Part B — Seller (Penjual)

### B1. Apply to open a shop
1. Sign in as a customer and choose **Buka kedai** (menu, footer, or homepage banner).
2. Enter the shop name, description, shop phone, pickup location, opening hours and an optional logo. Confirm the declaration and press **Hantar permohonan**.
3. Wait for approval. You will be notified. After approval the **Dashboard Penjual** appears in your account menu.

### B2. Dashboard
**Dashboard Penjual** shows: orders waiting for you (*Perlu disahkan*), orders in progress, completed sales for the last 30 days, products on sale, a 14-day chart of completed sales, low-stock products and the latest orders. *Only completed orders count as sales.*

### B3. Products (Pengurusan Produk)
- **Tambah produk**: name, category, description, price (RM), stock, optional SKU, *Dijual sekarang*, and up to 5 images (JPG/PNG/WebP, max 2 MB each). The first image becomes the main image.
- New products wait for administrator approval (status *Menunggu semakan*) before buyers can see them. Changing the name or description later sends the product for approval again; changing price or stock does not.
- In the product list you can update stock directly (**Simpan**), toggle **Dijual / Disembunyi**, **Edit**, and archive (box icon). Archived products disappear from the shop but stay in old orders; restore them from the *Diarkib* tab.
- If a product is rejected, the administrator's note is shown — edit and save to resubmit.

### B4. Orders (Pengurusan Pesanan)
1. Open an order with status *Menunggu* (**Semak**). You see the items, the customer's name, phone and note.
2. Press **Sahkan pesanan**. (Online-simulated orders can only be confirmed after the customer has paid.)
3. Then **Mula sediakan** → **Tandakan sedia diambil** → when the customer collects, **Tandakan selesai**. For cash orders, completing also records that you received the cash.
4. Can't fulfil it? **Tolak pesanan**, give a reason — the customer sees it and the stock is returned automatically.

### B5. Reports and shop profile
- **Laporan jualan**: 7/30/90-day totals, completed vs. cancelled, best sellers, low stock, and **Muat turun CSV** for Excel.
- **Profil kedai**: change the name, description, phone, pickup location, hours and logo.

---

## Part C — Administrator (Pentadbir)

### C1. Getting access
Administrator accounts are created with `database/create_admin.php` (see [INSTALLATION.md](INSTALLATION.md#8-creating-a-real-administrator)) or by another administrator. There is no sign-up page for administrators.

### C2. Dashboard
**Dashboard Pentadbir** shows items needing attention (shop applications, products waiting, open reports), customers, sellers, all shop orders and completed sales for 7/30/90 days, recent administrative actions and top shops.

### C3. Shop applications (Permohonan & Kedai)
- *Menunggu semakan* tab: read the application, then **Luluskan** (the account becomes a seller) or **Tolak permohonan** with a reason.
- *Aktif* tab: **Gantung kedai** with a reason hides all its products immediately. *Digantung* tab: **Aktifkan semula**.

### C4. Product moderation (Moderasi Produk)
- Pending products are listed first. **Luluskan** directly from the list, or **Semak** to see images, description and buyer reports.
- **Tolak** (seller may edit and resubmit) or **Gantung** (hidden until approved again). A reason is required and sent to the seller.

### C5. Users (Pengguna)
- Search by name, e-mail or phone; filter by role and status. Open a user with **Urus**.
- **Gantung akaun** (reason required) blocks sign-in immediately; **Aktifkan semula** restores it.
- **Tetapkan kata laluan sementara** shows a one-time password to give to the user in person after checking their identity.
- **Jadikan pentadbir / Turunkan kepada pelanggan** requires your own password. You cannot suspend or demote yourself, and the last administrator cannot be removed.

### C6. Orders (Semua Pesanan)
- Search and filter by status and payment status. Open an order to see every shop's slip, the status history and payments.
- **Ubah status (pentadbir)** moves a part forward or cancels it; a reason is required and the change is logged. Cancelling returns stock; a paid order becomes *Perlu bayaran balik* — arrange the refund manually.
- **Nota penyelesaian** records complaints, investigations and outcomes.

### C7. Content and settings
- **Kategori**: create, edit, hide; delete only when no products use the category.
- **Ulasan**: hide inappropriate reviews (reason required) or publish them again.
- **Laporan produk**: resolve or dismiss reports with a note.
- **Pengumuman**: notices shown on the homepage, optionally between two dates.
- **Tetapan sistem**: product approval on/off, simulated payment on/off, low-stock threshold, max quantity per item, contact details, pickup message.
- **Log audit**: who did what and when.


## Multi-campus marketplace

E-FoodHub is designed for participating Kolej Vokasional campuses. Public users may register as customers. A registered customer can apply to become a seller by providing the shop name, operating campus, contact information and collection location. The application remains pending until an administrator approves it. Shop cards and product listings show the seller's campus so buyers can identify where orders must be collected.
