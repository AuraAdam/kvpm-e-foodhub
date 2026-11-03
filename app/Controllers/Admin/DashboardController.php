<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Services\AuditLog;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $range = in_array(query('range'), ['7', '30', '90'], true) ? (int) query('range') : 30;
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($range - 1) . ' days'));

        $users = Database::fetch(
            "SELECT SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) AS customers,
                    SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) AS sellers,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended
             FROM users"
        );
        $orders = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status IN ('cancelled','rejected') THEN 1 ELSE 0 END) AS closed,
                    SUM(CASE WHEN status IN ('pending','confirmed','preparing','ready') THEN 1 ELSE 0 END) AS open,
                    COALESCE(SUM(CASE WHEN status = 'completed' AND completed_at >= :since THEN subtotal ELSE 0 END), 0) AS sales
             FROM seller_orders",
            ['since' => $since]
        );

        $this->dashboard('admin/dashboard', [
            'title'           => 'Dashboard Pentadbir',
            'range'           => $range,
            'users'           => $users,
            'orders'          => $orders,
            'pendingSellers'  => (int) Database::value("SELECT COUNT(*) FROM seller_profiles WHERE status = 'pending'"),
            'pendingProducts' => (int) Database::value("SELECT COUNT(*) FROM products WHERE approval_status = 'pending' AND archived_at IS NULL"),
            'activeProducts'  => (int) Database::value("SELECT COUNT(*) FROM products WHERE approval_status = 'approved' AND archived_at IS NULL AND is_available = 1"),
            'openReports'     => (int) Database::value("SELECT COUNT(*) FROM product_reports WHERE status = 'open'"),
            'outOfStock'      => (int) Database::value("SELECT COUNT(*) FROM products WHERE approval_status = 'approved' AND archived_at IS NULL AND stock = 0"),
            'recentAudit'     => Database::fetchAll('SELECT a.*, u.name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.created_at DESC, a.id DESC LIMIT 8'),
            'topShops'        => Database::fetchAll(
                "SELECT s.shop_name, COUNT(*) AS orders, SUM(so.subtotal) AS sales
                 FROM seller_orders so JOIN seller_profiles s ON s.id = so.seller_id
                 WHERE so.status = 'completed' AND so.completed_at >= ?
                 GROUP BY s.id, s.shop_name ORDER BY sales DESC LIMIT 5",
                [$since]
            ),
        ]);
    }

    public function audit(): void
    {
        $where = ['1 = 1'];
        $params = [];
        if (($entity = query('entity')) !== '' && preg_match('/^[a-z_]{1,40}$/', $entity)) {
            $where[] = 'a.entity_type = :entity';
            $params['entity'] = $entity;
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM audit_logs a WHERE $whereSql", $params);
        $pagination = paginate($total, 25);
        $logs = Database::fetchAll(
            "SELECT a.*, u.name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id
             WHERE $whereSql ORDER BY a.created_at DESC, a.id DESC LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        $this->dashboard('admin/audit', [
            'title'      => 'Log audit',
            'logs'       => $logs,
            'pagination' => $pagination,
            'entity'     => $entity,
            'entities'   => array_column(Database::fetchAll('SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type'), 'entity_type'),
        ]);
    }

    public static function describe(string $action): string
    {
        return AuditLog::describe($action);
    }
}
