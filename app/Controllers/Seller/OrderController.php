<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Core\Auth;
use App\Core\Database;
use App\Services\CheckoutException;
use App\Services\OrderService;

/**
 * Orders received by the signed-in seller's shop. A seller only ever sees
 * their own seller_orders rows and the customer details needed to fulfil them.
 */
final class OrderController extends SellerController
{
    private const TABS = [
        ''          => 'Semua',
        'pending'   => 'Menunggu',
        'confirmed' => 'Disahkan',
        'preparing' => 'Disediakan',
        'ready'     => 'Sedia diambil',
        'completed' => 'Selesai',
        'closed'    => 'Ditolak / batal',
    ];

    public function index(): void
    {
        $status = array_key_exists(query('status'), self::TABS) ? query('status') : '';
        $where = ['so.seller_id = :sid'];
        $params = ['sid' => $this->shopId()];
        if ($status === 'closed') {
            $where[] = "so.status IN ('rejected','cancelled')";
        } elseif ($status !== '') {
            $where[] = 'so.status = :status';
            $params['status'] = $status;
        }
        if (($q = mb_substr(query('q'), 0, 60)) !== '') {
            $where[] = "(so.reference LIKE :q1 ESCAPE '!' OR o.contact_name LIKE :q2 ESCAPE '!')";
            $params['q1'] = $params['q2'] = '%' . like_escape($q) . '%';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE $whereSql", $params);
        $pagination = paginate($total, (int) config('pagination.dashboard'));
        $orders = Database::fetchAll(
            "SELECT so.*, o.order_number, o.contact_name, o.payment_method,
                (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE seller_order_id = so.id) AS units
             FROM seller_orders so JOIN orders o ON o.id = so.order_id
             WHERE $whereSql ORDER BY so.created_at DESC, so.id DESC
             LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        $counts = [];
        foreach (Database::fetchAll('SELECT status, COUNT(*) AS n FROM seller_orders WHERE seller_id = ? GROUP BY status', [$this->shopId()]) as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }

        $this->dashboard('seller/orders', [
            'title'      => 'Pengurusan Pesanan',
            'orders'     => $orders,
            'pagination' => $pagination,
            'tabs'       => self::TABS,
            'status'     => $status,
            'counts'     => $counts,
        ]);
    }

    public function show(string $id): void
    {
        $so = $this->ownSellerOrder($id);
        $this->dashboard('seller/order', [
            'title'       => 'Pesanan ' . $so['reference'],
            'so'          => $so,
            'items'       => OrderService::items((int) $so['id']),
            'history'     => OrderService::historyFor((int) $so['id']),
            'payments'    => Database::fetchAll('SELECT * FROM payments WHERE order_id = ? AND (seller_order_id IS NULL OR seller_order_id = ?) ORDER BY created_at DESC', [(int) $so['order_id'], (int) $so['id']]),
            'transitions' => OrderService::allowedTransitions('seller', $so['status']),
        ]);
    }

    public function status(string $id): void
    {
        $so = $this->ownSellerOrder($id);
        $to = input('to');
        try {
            OrderService::transition((int) $so['id'], $to, 'seller', Auth::id(), mb_substr(input('note'), 0, 255));
            flash('success', 'Status pesanan dikemas kini: ' . order_status_label($to) . '.');
        } catch (CheckoutException $e) {
            flash('error', $e->getMessage());
        }
        redirect('/seller/orders/' . $so['id']);
    }

    private function ownSellerOrder(string $id): array
    {
        $so = Database::fetch(
            'SELECT so.*, o.order_number, o.contact_name, o.contact_phone, o.notes, o.payment_method, o.created_at AS ordered_at
             FROM seller_orders so JOIN orders o ON o.id = so.order_id
             WHERE so.id = ? AND so.seller_id = ?',
            [route_id($id), $this->shopId()]
        );
        if (!$so) {
            abort(404, 'Pesanan tidak dijumpai.');
        }
        return $so;
    }
}
