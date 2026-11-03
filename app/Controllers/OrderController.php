<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Services\CheckoutException;
use App\Services\OrderService;
use App\Services\PaymentService;

/** The customer's own orders. Every query is restricted to customer_id = current user. */
final class OrderController extends Controller
{
    private const STATUS_FILTERS = ['open' => 'Dalam proses', 'completed' => 'Selesai', 'closed' => 'Dibatalkan / ditolak'];

    public function index(): void
    {
        $filter = array_key_exists(query('status'), self::STATUS_FILTERS) ? query('status') : '';
        $where = 'o.customer_id = :uid';
        $params = ['uid' => Auth::id()];
        if ($filter === 'open') {
            $where .= " AND EXISTS (SELECT 1 FROM seller_orders x WHERE x.order_id = o.id AND x.status IN ('pending','confirmed','preparing','ready'))";
        } elseif ($filter === 'completed') {
            $where .= " AND EXISTS (SELECT 1 FROM seller_orders x WHERE x.order_id = o.id AND x.status = 'completed')";
        } elseif ($filter === 'closed') {
            $where .= " AND NOT EXISTS (SELECT 1 FROM seller_orders x WHERE x.order_id = o.id AND x.status NOT IN ('rejected','cancelled'))";
        }

        $total = (int) Database::value("SELECT COUNT(*) FROM orders o WHERE $where", $params);
        $pagination = paginate($total, 10);
        $orders = Database::fetchAll(
            "SELECT o.* FROM orders o WHERE $where ORDER BY o.created_at DESC, o.id DESC
             LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        foreach ($orders as &$order) {
            $order['parts'] = Database::fetchAll(
                'SELECT so.status, so.payment_status, so.subtotal, s.shop_name,
                    (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE seller_order_id = so.id) AS units
                 FROM seller_orders so JOIN seller_profiles s ON s.id = so.seller_id WHERE so.order_id = ? ORDER BY so.id',
                [(int) $order['id']]
            );
            $order['summary_status'] = OrderService::summaryStatus(array_column($order['parts'], 'status'));
        }

        $this->render('customer/orders', [
            'title'      => 'Pesanan saya',
            'orders'     => $orders,
            'pagination' => $pagination,
            'filter'     => $filter,
            'filters'    => self::STATUS_FILTERS,
        ]);
    }

    public function show(string $number): void
    {
        $order = $this->ownOrder($number);
        $sellerOrders = OrderService::sellerOrdersFor((int) $order['id']);

        // Which order lines can this customer review now?
        $reviewed = [];
        foreach (Database::fetchAll('SELECT order_item_id, rating FROM reviews WHERE user_id = ?', [Auth::id()]) as $r) {
            $reviewed[(int) $r['order_item_id']] = (int) $r['rating'];
        }

        $this->render('customer/order', [
            'title'        => 'Pesanan ' . $order['order_number'],
            'order'        => $order,
            'sellerOrders' => $sellerOrders,
            'payable'      => $order['payment_method'] === 'online_sim' ? PaymentService::payable((int) $order['id']) : [],
            'payments'     => Database::fetchAll('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC, id DESC', [(int) $order['id']]),
            'reviewed'     => $reviewed,
            'justPlaced'   => query('placed') === '1',
        ]);
    }

    /** Customer cancels one shop's part of the order while it is still pending. */
    public function cancel(string $number): void
    {
        $order = $this->ownOrder($number);
        $sellerOrderId = route_id(input('seller_order_id'));
        $belongs = Database::value('SELECT id FROM seller_orders WHERE id = ? AND order_id = ?', [$sellerOrderId, (int) $order['id']]);
        if (!$belongs) {
            abort(404, 'Pesanan tidak dijumpai.');
        }
        try {
            OrderService::transition($sellerOrderId, 'cancelled', 'customer', Auth::id(), mb_substr(input('reason'), 0, 255));
            flash('success', 'Pesanan telah dibatalkan dan stok dipulangkan kepada penjual.');
        } catch (CheckoutException $e) {
            flash('error', $e->getMessage());
        }
        redirect('/orders/' . $order['order_number']);
    }

    public function payment(string $number): void
    {
        $order = $this->ownOrder($number);
        if ($order['payment_method'] !== 'online_sim') {
            redirect('/orders/' . $order['order_number']);
        }
        $payable = PaymentService::payable((int) $order['id']);
        $this->render('customer/payment', [
            'title'     => 'Bayaran simulasi',
            'order'     => $order,
            'payable'   => $payable,
            'amountSen' => array_sum(array_map(fn ($so) => to_sen($so['subtotal']), $payable)),
            'attempts'  => Database::fetchAll("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC, id DESC", [(int) $order['id']]),
        ]);
    }

    public function pay(string $number): void
    {
        $order = $this->ownOrder($number);
        $outcome = input('outcome');
        if (!in_array($outcome, ['success', 'failure'], true)) {
            abort(400, 'Pilihan bayaran tidak sah.');
        }
        try {
            $ok = PaymentService::simulate((int) $order['id'], Auth::id(), $outcome === 'success');
        } catch (CheckoutException $e) {
            flash('error', $e->getMessage());
            redirect('/orders/' . $order['order_number']);
        }
        if ($ok) {
            flash('success', 'Bayaran simulasi berjaya. Penjual kini boleh mengesahkan pesanan anda.');
            redirect('/orders/' . $order['order_number']);
        }
        flash('error', 'Bayaran simulasi gagal. Tiada apa-apa dicaj. Anda boleh mencuba lagi atau membatalkan pesanan.');
        redirect('/orders/' . $order['order_number'] . '/pay');
    }

    /** Load an order that belongs to the signed-in customer, or 404 (never reveal other people's orders). */
    private function ownOrder(string $number): array
    {
        $order = Database::fetch('SELECT * FROM orders WHERE order_number = ? AND customer_id = ?', [$number, Auth::id()]);
        if (!$order) {
            abort(404, 'Pesanan tidak dijumpai.');
        }
        return $order;
    }
}
