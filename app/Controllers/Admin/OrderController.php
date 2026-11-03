<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;
use App\Services\AuditLog;
use App\Services\CheckoutException;
use App\Services\OrderService;

/** Order oversight: search, inspect, override status (audited), record resolutions. */
final class OrderController extends Controller
{
    public function index(): void
    {
        $where = ['1 = 1'];
        $params = [];
        $status = in_array(query('status'), ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'rejected', 'cancelled'], true) ? query('status') : '';
        if ($status !== '') {
            $where[] = 'so.status = :status';
            $params['status'] = $status;
        }
        if (in_array(query('payment'), ['unpaid', 'paid', 'refund_due', 'void'], true)) {
            $where[] = 'so.payment_status = :pay';
            $params['pay'] = query('payment');
        }
        if (($q = mb_substr(query('q'), 0, 80)) !== '') {
            $where[] = "(so.reference LIKE :q1 ESCAPE '!' OR o.contact_name LIKE :q2 ESCAPE '!' OR s.shop_name LIKE :q3 ESCAPE '!' OR u.email LIKE :q4 ESCAPE '!')";
            $params['q1'] = $params['q2'] = $params['q3'] = $params['q4'] = '%' . like_escape($q) . '%';
        }
        $whereSql = implode(' AND ', $where);
        $from = 'FROM seller_orders so JOIN orders o ON o.id = so.order_id JOIN seller_profiles s ON s.id = so.seller_id JOIN users u ON u.id = o.customer_id';
        $total = (int) Database::value("SELECT COUNT(*) $from WHERE $whereSql", $params);
        $pagination = paginate($total, (int) config('pagination.dashboard'));
        $orders = Database::fetchAll(
            "SELECT so.*, o.order_number, o.payment_method, o.contact_name, u.email AS customer_email, s.shop_name
             $from WHERE $whereSql ORDER BY so.created_at DESC, so.id DESC
             LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        $this->dashboard('admin/orders', ['title' => 'Semua Pesanan', 'orders' => $orders, 'pagination' => $pagination, 'status' => $status]);
    }

    public function show(string $number): void
    {
        $order = $this->find($number);
        $this->dashboard('admin/order', [
            'title'        => 'Pesanan ' . $order['order_number'],
            'order'        => $order,
            'sellerOrders' => OrderService::sellerOrdersFor((int) $order['id']),
            'payments'     => Database::fetchAll('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC, id DESC', [(int) $order['id']]),
            'notes'        => Database::fetchAll('SELECT n.*, u.name AS admin_name FROM order_notes n JOIN users u ON u.id = n.admin_id WHERE n.order_id = ? ORDER BY n.created_at DESC', [(int) $order['id']]),
        ]);
    }

    public function override(string $number): void
    {
        $order = $this->find($number);
        $sellerOrderId = route_id(input('seller_order_id'));
        $so = Database::fetch('SELECT * FROM seller_orders WHERE id = ? AND order_id = ?', [$sellerOrderId, (int) $order['id']]);
        if (!$so) {
            abort(404, 'Pesanan tidak dijumpai.');
        }
        $to = input('to');
        $reason = mb_substr(input('reason'), 0, 255);
        try {
            OrderService::transition($sellerOrderId, $to, 'admin', Auth::id(), $reason);
            AuditLog::record('order.override', 'seller_order', $sellerOrderId, ['reference' => $so['reference'], 'from' => $so['status'], 'to' => $to, 'reason' => $reason]);
            flash('success', 'Status ' . $so['reference'] . ' ditukar kepada ' . order_status_label($to) . '.');
        } catch (CheckoutException $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/orders/' . $order['order_number']);
    }

    public function note(string $number): void
    {
        $order = $this->find($number);
        $v = new Validator($_POST, ['note' => 'required|min:5|max:2000'], ['note' => 'Nota']);
        if ($v->fails()) {
            back_with_errors($v->errors(), '/admin/orders/' . $order['order_number']);
        }
        Database::insert('order_notes', ['order_id' => (int) $order['id'], 'admin_id' => Auth::id(), 'note' => $v->validated()['note'], 'created_at' => now()]);
        AuditLog::record('order.note', 'order', (int) $order['id'], ['order_number' => $order['order_number']]);
        flash('success', 'Nota penyelesaian disimpan.');
        redirect('/admin/orders/' . $order['order_number']);
    }

    private function find(string $number): array
    {
        $order = Database::fetch(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM orders o JOIN users u ON u.id = o.customer_id WHERE o.order_number = ?',
            [$number]
        );
        if (!$order) {
            abort(404, 'Pesanan tidak dijumpai.');
        }
        return $order;
    }
}
