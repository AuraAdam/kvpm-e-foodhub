<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Core\Database;

/**
 * Seller dashboard and reports. Every figure is computed from this shop's
 * own records. "Sales" means the subtotal of COMPLETED seller orders only —
 * pending, rejected and cancelled orders are never counted as sales.
 */
final class DashboardController extends SellerController
{
    public function index(): void
    {
        $sid = $this->shopId();
        $threshold = (int) setting('low_stock_threshold', '5');

        $counts = Database::fetch(
            "SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN ('confirmed','preparing') THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS ready,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
             FROM seller_orders WHERE seller_id = ?",
            [$sid]
        );
        $since30 = date('Y-m-d 00:00:00', strtotime('-29 days'));
        $sales30 = Database::value("SELECT COALESCE(SUM(subtotal), 0) FROM seller_orders WHERE seller_id = ? AND status = 'completed' AND completed_at >= ?", [$sid, $since30]);
        $awaitingCash = Database::value("SELECT COALESCE(SUM(subtotal), 0) FROM seller_orders WHERE seller_id = ? AND status IN ('confirmed','preparing','ready') AND payment_status = 'unpaid'", [$sid]);
        $activeProducts = Database::value("SELECT COUNT(*) FROM products WHERE seller_id = ? AND archived_at IS NULL AND approval_status = 'approved' AND is_available = 1", [$sid]);
        $pendingProducts = Database::value("SELECT COUNT(*) FROM products WHERE seller_id = ? AND archived_at IS NULL AND approval_status = 'pending'", [$sid]);

        $lowStock = Database::fetchAll(
            'SELECT id, name, stock FROM products WHERE seller_id = ? AND archived_at IS NULL AND stock <= ? ORDER BY stock, name LIMIT 6',
            [$sid, $threshold]
        );
        $recent = Database::fetchAll(
            'SELECT so.*, o.order_number, o.contact_name, o.payment_method,
                (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE seller_order_id = so.id) AS units
             FROM seller_orders so JOIN orders o ON o.id = so.order_id
             WHERE so.seller_id = ? ORDER BY so.created_at DESC, so.id DESC LIMIT 6',
            [$sid]
        );

        $this->dashboard('seller/dashboard', [
            'title'           => 'Dashboard Penjual',
            'shop'            => $this->shop(),
            'counts'          => $counts,
            'sales30'         => $sales30,
            'awaitingCash'    => $awaitingCash,
            'activeProducts'  => (int) $activeProducts,
            'pendingProducts' => (int) $pendingProducts,
            'lowStock'        => $lowStock,
            'threshold'       => $threshold,
            'recent'          => $recent,
            'daily'           => $this->dailySales($sid, 14),
        ]);
    }

    public function reports(): void
    {
        $sid = $this->shopId();
        $range = in_array(query('range'), ['7', '30', '90'], true) ? (int) query('range') : 30;
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($range - 1) . ' days'));

        $summary = Database::fetch(
            "SELECT
                COUNT(*) AS orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status IN ('cancelled','rejected') THEN 1 ELSE 0 END) AS closed,
                SUM(CASE WHEN status IN ('pending','confirmed','preparing','ready') THEN 1 ELSE 0 END) AS open,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN subtotal ELSE 0 END), 0) AS sales
             FROM seller_orders WHERE seller_id = ? AND created_at >= ?",
            [$sid, $since]
        );
        $best = Database::fetchAll(
            "SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS revenue
             FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id
             WHERE so.seller_id = ? AND so.status = 'completed' AND so.completed_at >= ?
             GROUP BY oi.product_id, oi.product_name ORDER BY units DESC, revenue DESC LIMIT 10",
            [$sid, $since]
        );
        $lowStock = Database::fetchAll(
            'SELECT id, name, stock, is_available FROM products WHERE seller_id = ? AND archived_at IS NULL AND stock <= ? ORDER BY stock, name',
            [$sid, (int) setting('low_stock_threshold', '5')]
        );

        $this->dashboard('seller/reports', [
            'title'    => 'Laporan jualan',
            'range'    => $range,
            'summary'  => $summary,
            'best'     => $best,
            'lowStock' => $lowStock,
            'daily'    => $this->dailySales($sid, $range),
        ]);
    }

    /** CSV download of this shop's completed sales lines for a period. */
    public function export(): never
    {
        $sid = $this->shopId();
        $range = in_array(query('range'), ['7', '30', '90'], true) ? (int) query('range') : 30;
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($range - 1) . ' days'));
        $rows = Database::fetchAll(
            "SELECT so.reference, so.completed_at, oi.product_name, oi.quantity, oi.unit_price, oi.line_total, o.payment_method
             FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id JOIN orders o ON o.id = so.order_id
             WHERE so.seller_id = ? AND so.status = 'completed' AND so.completed_at >= ? ORDER BY so.completed_at",
            [$sid, $since]
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="jualan-' . $this->shop()['slug'] . '-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
        fputcsv($out, ['Rujukan', 'Tarikh selesai', 'Produk', 'Kuantiti', 'Harga seunit (RM)', 'Jumlah (RM)', 'Kaedah bayaran']);
        foreach ($rows as $r) {
            // Prefix cells that start with formula characters to prevent CSV injection in Excel.
            $name = preg_match('/^[=+\-@]/', $r['product_name']) ? "'" . $r['product_name'] : $r['product_name'];
            fputcsv($out, [$r['reference'], $r['completed_at'], $name, $r['quantity'], $r['unit_price'], $r['line_total'], payment_method_label($r['payment_method'])]);
        }
        fclose($out);
        exit;
    }

    /** Completed sales per day for the last $days days (days with no sales included as 0). */
    private function dailySales(int $sid, int $days): array
    {
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
        $rows = Database::fetchAll(
            "SELECT DATE(completed_at) AS day, SUM(subtotal) AS total, COUNT(*) AS orders
             FROM seller_orders WHERE seller_id = ? AND status = 'completed' AND completed_at >= ?
             GROUP BY DATE(completed_at)",
            [$sid, $since]
        );
        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['day']] = $r;
        }
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $series[] = ['day' => $day, 'total' => (float) ($byDay[$day]['total'] ?? 0), 'orders' => (int) ($byDay[$day]['orders'] ?? 0)];
        }
        return $series;
    }
}
