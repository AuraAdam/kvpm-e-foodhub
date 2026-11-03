<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Checkout and the order status workflow.
 *
 * ORDER MODEL
 *   One checkout creates one `orders` row. The cart may hold products from
 *   several shops, so one `seller_orders` row is created per shop. Each shop
 *   only ever sees its own seller order. Status and payment status live on
 *   the seller order.
 *
 * STATUS WORKFLOW (seller_orders.status)
 *   pending → confirmed → preparing → ready → completed
 *   pending|confirmed → rejected   (seller, reason required)
 *   pending → cancelled            (customer)
 *   any open status → cancelled / a later status (administrator override, reason required, audited)
 *
 * STOCK
 *   Stock is deducted inside the checkout transaction with row locks
 *   (SELECT ... FOR UPDATE) and a guarded UPDATE (stock >= quantity), so it
 *   can never go negative. It is restored once — and only once — when a seller
 *   order is rejected or cancelled (stock_restored flag).
 */
final class OrderService
{
    public const OPEN_STATUSES = ['pending', 'confirmed', 'preparing', 'ready'];
    public const FLOW = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];

    /** Allowed transitions per role. Administrators are handled in allowedTransitions(). */
    private const TRANSITIONS = [
        'seller' => [
            'pending'   => ['confirmed', 'rejected'],
            'confirmed' => ['preparing', 'rejected'],
            'preparing' => ['ready'],
            'ready'     => ['completed'],
        ],
        'customer' => [
            'pending' => ['cancelled'],
        ],
    ];

    /** Button labels for transitions (target status => label). */
    public const ACTION_LABELS = [
        'confirmed' => 'Sahkan pesanan',
        'preparing' => 'Mula sediakan',
        'ready'     => 'Tandakan sedia diambil',
        'completed' => 'Tandakan selesai',
        'rejected'  => 'Tolak pesanan',
        'cancelled' => 'Batalkan pesanan',
    ];

    public static function allowedTransitions(string $role, string $from): array
    {
        if ($role === 'admin') {
            if (!in_array($from, self::OPEN_STATUSES, true)) {
                return [];
            }
            $later = array_slice(self::FLOW, array_search($from, self::FLOW, true) + 1);
            return array_merge($later, ['cancelled']);
        }
        return self::TRANSITIONS[$role][$from] ?? [];
    }

    // -----------------------------------------------------------------
    // Checkout
    // -----------------------------------------------------------------

    /**
     * Turn the user's cart into an order.
     *
     * @param array{contact_name:string, contact_phone:string, notes:string, payment_method:string} $data
     * @return string the new order number
     * @throws CheckoutException with a message the customer can act on
     */
    public static function checkout(int $userId, array $data, string $checkoutToken): string
    {
        if (!in_array($data['payment_method'], ['cash', 'online_sim'], true)
            || ($data['payment_method'] === 'online_sim' && setting('allow_online_sim_payment', '1') !== '1')) {
            throw new CheckoutException('Kaedah pembayaran tidak sah.');
        }

        return Database::transaction(function () use ($userId, $data, $checkoutToken): string {
            // A checkout token can only ever create one order (double-click / resubmission protection).
            if (Database::value('SELECT id FROM orders WHERE checkout_token = ?', [$checkoutToken])) {
                throw new CheckoutException('Pesanan ini telah pun dihantar.');
            }

            // Lock the products in the cart so two customers cannot buy the last unit at the same time.
            $lines = Database::fetchAll(
                "SELECT ci.product_id, ci.quantity, p.name, p.price, p.stock, p.is_available, p.approval_status,
                        p.archived_at, p.seller_id, s.user_id AS shop_user_id, s.status AS shop_status,
                        s.shop_name, u.status AS owner_status, c.is_visible AS category_visible
                 FROM cart_items ci
                 JOIN products p ON p.id = ci.product_id
                 JOIN seller_profiles s ON s.id = p.seller_id
                 JOIN users u ON u.id = s.user_id
                 JOIN categories c ON c.id = p.category_id
                 WHERE ci.user_id = ?
                 ORDER BY p.id" . Database::forUpdate(),
                [$userId]
            );
            if (!$lines) {
                throw new CheckoutException('Troli anda kosong.');
            }

            $byShop = [];
            $totalSen = 0;
            foreach ($lines as $line) {
                $issue = CartService::issueFor($line, $userId);
                if ($issue !== null) {
                    throw new CheckoutException('"' . $line['name'] . '": ' . $issue);
                }
                $unitSen = to_sen($line['price']);
                $line['unit_sen'] = $unitSen;
                $line['line_sen'] = $unitSen * (int) $line['quantity'];
                $byShop[(int) $line['seller_id']][] = $line;
                $totalSen += $line['line_sen'];
            }

            $orderNumber = self::newOrderNumber();
            $timestamp = now();
            $orderId = Database::insert('orders', [
                'order_number'   => $orderNumber,
                'customer_id'    => $userId,
                'payment_method' => $data['payment_method'],
                'contact_name'   => $data['contact_name'],
                'contact_phone'  => $data['contact_phone'],
                'notes'          => $data['notes'] !== '' ? $data['notes'] : null,
                'total'          => sen_to_decimal($totalSen),
                'checkout_token' => $checkoutToken,
                'created_at'     => $timestamp,
                'updated_at'     => $timestamp,
            ]);

            $threshold = (int) setting('low_stock_threshold', '5');
            $sequence = 1;
            foreach ($byShop as $sellerId => $shopLines) {
                $subtotal = array_sum(array_column($shopLines, 'line_sen'));
                $sellerOrderId = Database::insert('seller_orders', [
                    'order_id'       => $orderId,
                    'seller_id'      => $sellerId,
                    'reference'      => $orderNumber . '-' . $sequence++,
                    'status'         => 'pending',
                    'payment_status' => 'unpaid',
                    'subtotal'       => sen_to_decimal($subtotal),
                    'created_at'     => $timestamp,
                    'updated_at'     => $timestamp,
                ]);
                self::history($sellerOrderId, null, 'pending', $userId, 'customer', 'Pesanan dibuat');

                foreach ($shopLines as $line) {
                    Database::insert('order_items', [
                        'seller_order_id' => $sellerOrderId,
                        'product_id'      => (int) $line['product_id'],
                        'product_name'    => $line['name'],
                        'unit_price'      => sen_to_decimal($line['unit_sen']),
                        'quantity'        => (int) $line['quantity'],
                        'line_total'      => sen_to_decimal($line['line_sen']),
                    ]);
                    $updated = Database::query(
                        'UPDATE products SET stock = stock - :qty, updated_at = :now WHERE id = :id AND stock >= :qty_check',
                        ['qty' => (int) $line['quantity'], 'now' => $timestamp, 'id' => (int) $line['product_id'], 'qty_check' => (int) $line['quantity']]
                    )->rowCount();
                    if ($updated !== 1) {
                        throw new CheckoutException('"' . $line['name'] . '" baru sahaja kehabisan stok. Sila semak troli anda.');
                    }
                    $newStock = (int) $line['stock'] - (int) $line['quantity'];
                    if ($newStock <= $threshold && (int) $line['stock'] > $threshold) {
                        Notifier::send((int) $line['shop_user_id'], 'low_stock', 'Stok hampir habis',
                            "Stok \"{$line['name']}\" tinggal $newStock unit.", '/seller/products?availability=low');
                    }
                }

                $shopUserId = (int) $shopLines[0]['shop_user_id'];
                $note = $data['payment_method'] === 'online_sim' ? ' Menunggu bayaran dalam talian (simulasi).' : '';
                Notifier::send($shopUserId, 'order_placed', 'Pesanan baharu diterima',
                    "Pesanan {$orderNumber} perlu disemak." . $note, '/seller/orders/' . $sellerOrderId);
            }

            Database::query('DELETE FROM cart_items WHERE user_id = ?', [$userId]);

            Notifier::send($userId, 'order_placed', 'Pesanan anda telah dihantar',
                "Pesanan {$orderNumber} sedang menunggu pengesahan penjual.", '/orders/' . $orderNumber);

            return $orderNumber;
        });
    }

    private static function newOrderNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $number = 'KV' . date('ymd') . '-' . $suffix;
        } while (Database::value('SELECT id FROM orders WHERE order_number = ?', [$number]));
        return $number;
    }

    // -----------------------------------------------------------------
    // Status changes
    // -----------------------------------------------------------------

    /**
     * Move a seller order to a new status after checking the role is allowed to.
     * Callers must already have checked OWNERSHIP (the seller owns it / the customer placed it).
     *
     * @throws CheckoutException with a user-facing message if the move is not allowed
     */
    public static function transition(int $sellerOrderId, string $to, string $role, int $actorId, string $note = ''): void
    {
        Database::transaction(function () use ($sellerOrderId, $to, $role, $actorId, $note): void {
            $so = Database::fetch(
                'SELECT so.*, o.order_number, o.customer_id, o.payment_method, s.user_id AS shop_user_id, s.shop_name
                 FROM seller_orders so
                 JOIN orders o ON o.id = so.order_id
                 JOIN seller_profiles s ON s.id = so.seller_id
                 WHERE so.id = ?' . Database::forUpdate(),
                [$sellerOrderId]
            );
            if (!$so) {
                throw new CheckoutException('Pesanan tidak dijumpai.');
            }
            $from = $so['status'];
            if (!in_array($to, self::allowedTransitions($role, $from), true)) {
                throw new CheckoutException('Status pesanan tidak boleh ditukar daripada "' . order_status_label($from) . '" kepada "' . order_status_label($to) . '".');
            }
            if (in_array($to, ['rejected'], true) && trim($note) === '') {
                throw new CheckoutException('Sila nyatakan sebab pesanan ditolak.');
            }
            if ($role === 'admin' && trim($note) === '') {
                throw new CheckoutException('Sila nyatakan sebab perubahan status oleh pentadbir.');
            }
            // An online (simulated) order cannot be accepted until it has been paid.
            if ($role === 'seller' && $to === 'confirmed' && $so['payment_method'] === 'online_sim' && $so['payment_status'] !== 'paid') {
                throw new CheckoutException('Pesanan ini belum dibayar oleh pelanggan. Tunggu bayaran sebelum mengesahkan.');
            }

            $timestamp = now();
            $changes = ['status' => $to, 'updated_at' => $timestamp];
            if ($note !== '') {
                $changes['status_note'] = mb_substr($note, 0, 255);
            }
            if ($to === 'confirmed') {
                $changes['confirmed_at'] = $timestamp;
            }

            if ($to === 'completed') {
                $changes['completed_at'] = $timestamp;
                // Completing a cash order by the seller records the cash as collected.
                if ($so['payment_method'] === 'cash' && $so['payment_status'] === 'unpaid' && $role === 'seller') {
                    $changes['payment_status'] = 'paid';
                    Database::insert('payments', [
                        'order_id'        => (int) $so['order_id'],
                        'seller_order_id' => $sellerOrderId,
                        'method'          => 'cash',
                        'amount'          => $so['subtotal'],
                        'status'          => 'success',
                        'reference'       => 'CASH-' . strtoupper(bin2hex(random_bytes(5))),
                        'note'            => 'Tunai diterima oleh penjual semasa pengambilan',
                        'created_by'      => $actorId,
                        'created_at'      => $timestamp,
                    ]);
                }
            }

            if ($to === 'rejected' || $to === 'cancelled') {
                $changes['cancelled_at'] = $timestamp;
                $changes['payment_status'] = match ($so['payment_status']) {
                    'paid'  => 'refund_due', // no automatic refunds: must be settled manually
                    default => 'void',
                };
                if (!(int) $so['stock_restored']) {
                    self::restoreStock($sellerOrderId);
                    $changes['stock_restored'] = 1;
                }
            }

            Database::update('seller_orders', $changes, 'id = :id', ['id' => $sellerOrderId]);
            self::history($sellerOrderId, $from, $to, $actorId, $role, $note !== '' ? $note : null);
            self::notifyStatusChange($so, $to, $role, $note);
        });
    }

    private static function restoreStock(int $sellerOrderId): void
    {
        $items = Database::fetchAll('SELECT product_id, quantity FROM order_items WHERE seller_order_id = ?', [$sellerOrderId]);
        foreach ($items as $item) {
            Database::query('UPDATE products SET stock = stock + ?, updated_at = ? WHERE id = ?', [(int) $item['quantity'], now(), (int) $item['product_id']]);
        }
    }

    private static function history(int $sellerOrderId, ?string $from, string $to, ?int $actorId, string $role, ?string $note): void
    {
        Database::insert('order_status_history', [
            'seller_order_id' => $sellerOrderId,
            'from_status'     => $from,
            'to_status'       => $to,
            'changed_by'      => $actorId,
            'actor_role'      => $role,
            'note'            => $note !== null ? mb_substr($note, 0, 255) : null,
            'created_at'      => now(),
        ]);
    }

    private static function notifyStatusChange(array $so, string $to, string $role, string $note): void
    {
        $number = $so['order_number'];
        $shop = $so['shop_name'];
        $customerLink = '/orders/' . $number;
        $messages = [
            'confirmed' => ['order_confirmed', 'Pesanan disahkan', "$shop telah mengesahkan pesanan $number."],
            'preparing' => ['order_preparing', 'Pesanan sedang disediakan', "$shop sedang menyediakan pesanan $number."],
            'ready'     => ['order_ready', 'Pesanan sedia diambil', "Pesanan $number dari $shop sedia untuk diambil."],
            'completed' => ['order_completed', 'Pesanan selesai', "Pesanan $number dari $shop telah selesai. Anda boleh menulis ulasan."],
            'rejected'  => ['order_rejected', 'Pesanan ditolak', "$shop menolak pesanan $number. Sebab: $note"],
            'cancelled' => ['order_cancelled', 'Pesanan dibatalkan', "Pesanan $number dari $shop telah dibatalkan."],
        ];
        [$type, $title, $message] = $messages[$to];

        if ($role !== 'customer') {
            Notifier::send((int) $so['customer_id'], $type, $title, $message, $customerLink);
        }
        if ($role !== 'seller') {
            $who = $role === 'admin' ? 'Pentadbir' : 'Pelanggan';
            Notifier::send((int) $so['shop_user_id'], $type, "$title oleh $who", "Pesanan {$so['reference']}: status kini " . order_status_label($to) . '.', '/seller/orders/' . $so['id']);
        }
    }

    // -----------------------------------------------------------------
    // Read helpers
    // -----------------------------------------------------------------

    /** Seller orders (with items) belonging to one checkout. */
    public static function sellerOrdersFor(int $orderId): array
    {
        $sellerOrders = Database::fetchAll(
            'SELECT so.*, s.shop_name, s.slug AS shop_slug, s.phone AS shop_phone, s.collection_location, s.collection_hours
             FROM seller_orders so JOIN seller_profiles s ON s.id = so.seller_id
             WHERE so.order_id = ? ORDER BY so.id',
            [$orderId]
        );
        foreach ($sellerOrders as &$so) {
            $so['items'] = self::items((int) $so['id']);
            $so['history'] = self::historyFor((int) $so['id']);
        }
        return $sellerOrders;
    }

    public static function items(int $sellerOrderId): array
    {
        return Database::fetchAll(
            'SELECT oi.*, p.slug AS product_slug,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = oi.product_id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path
             FROM order_items oi JOIN products p ON p.id = oi.product_id
             WHERE oi.seller_order_id = ? ORDER BY oi.id',
            [$sellerOrderId]
        );
    }

    public static function historyFor(int $sellerOrderId): array
    {
        return Database::fetchAll('SELECT * FROM order_status_history WHERE seller_order_id = ? ORDER BY created_at, id', [$sellerOrderId]);
    }

    /** Overall status shown on the customer's order list. */
    public static function summaryStatus(array $statuses): string
    {
        $statuses = array_unique($statuses);
        if (count($statuses) === 1) {
            return $statuses[0];
        }
        $open = array_values(array_intersect(self::FLOW, $statuses));
        return $open ? $open[0] : 'cancelled';
    }
}
