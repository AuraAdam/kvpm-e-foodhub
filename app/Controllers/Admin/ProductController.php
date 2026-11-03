<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Services\AuditLog;
use App\Services\Notifier;

/** Product moderation queue and listing oversight. */
final class ProductController extends Controller
{
    public function index(): void
    {
        $where = ['1 = 1'];
        $params = [];
        $status = in_array(query('status'), ['pending', 'approved', 'rejected', 'suspended', 'archived', 'out_of_stock'], true) ? query('status') : '';
        match ($status) {
            'archived'     => $where[] = 'p.archived_at IS NOT NULL',
            'out_of_stock' => $where[] = "p.archived_at IS NULL AND p.approval_status = 'approved' AND p.stock = 0",
            ''             => null,
            default        => $where[] = 'p.archived_at IS NULL AND p.approval_status = :astatus',
        };
        if (!in_array($status, ['', 'archived', 'out_of_stock'], true)) {
            $params['astatus'] = $status;
        }
        if (($seller = query_int('seller')) > 0) {
            $where[] = 'p.seller_id = :seller';
            $params['seller'] = $seller;
        }
        if (($cat = query_int('category')) > 0) {
            $where[] = 'p.category_id = :cat';
            $params['cat'] = $cat;
        }
        if (($q = mb_substr(query('q'), 0, 80)) !== '') {
            $where[] = "p.name LIKE :q ESCAPE '!'";
            $params['q'] = '%' . like_escape($q) . '%';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM products p WHERE $whereSql", $params);
        $pagination = paginate($total, (int) config('pagination.dashboard'));
        $products = Database::fetchAll(
            "SELECT p.*, s.shop_name, c.name AS category_name,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path,
                (SELECT COUNT(*) FROM product_reports r WHERE r.product_id = p.id AND r.status = 'open') AS open_reports
             FROM products p JOIN seller_profiles s ON s.id = p.seller_id JOIN categories c ON c.id = p.category_id
             WHERE $whereSql ORDER BY CASE WHEN p.approval_status = 'pending' THEN 0 ELSE 1 END, p.updated_at DESC
             LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        $this->dashboard('admin/products', [
            'title'      => 'Moderasi Produk',
            'products'   => $products,
            'pagination' => $pagination,
            'status'     => $status,
            'sellers'    => Database::fetchAll('SELECT id, shop_name FROM seller_profiles ORDER BY shop_name'),
            'categories' => Database::fetchAll('SELECT id, name FROM categories ORDER BY sort_order, name'),
        ]);
    }

    public function show(string $id): void
    {
        $product = $this->find($id);
        $this->dashboard('admin/product', [
            'title'   => $product['name'],
            'product' => $product,
            'images'  => Database::fetchAll('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [(int) $product['id']]),
            'reports' => Database::fetchAll('SELECT r.*, u.name AS reporter FROM product_reports r JOIN users u ON u.id = r.reporter_id WHERE r.product_id = ? ORDER BY r.created_at DESC', [(int) $product['id']]),
            'logs'    => Database::fetchAll("SELECT a.*, u.name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id WHERE a.entity_type = 'product' AND a.entity_id = ? ORDER BY a.created_at DESC", [(int) $product['id']]),
        ]);
    }

    public function moderate(string $id): void
    {
        $product = $this->find($id);
        $action = input('action');
        $note = mb_substr(input('note'), 0, 255);
        $map = ['approve' => 'approved', 'reject' => 'rejected', 'suspend' => 'suspended'];
        if (!isset($map[$action])) {
            abort(400, 'Tindakan tidak sah.');
        }
        $to = $map[$action];
        if ($to === $product['approval_status']) {
            flash('info', 'Produk sudah berstatus ' . approval_label($to) . '.');
            redirect('/admin/products/' . $product['id']);
        }
        if ($to !== 'approved' && $note === '') {
            flash('error', 'Sila nyatakan sebab untuk menolak atau menggantung produk.');
            redirect('/admin/products/' . $product['id']);
        }
        Database::update('products', ['approval_status' => $to, 'moderation_note' => $note !== '' ? $note : null, 'updated_at' => now()], 'id = :id', ['id' => (int) $product['id']]);
        AuditLog::record('product.' . $to, 'product', (int) $product['id'], ['name' => $product['name'], 'from' => $product['approval_status'], 'note' => $note]);

        $messages = [
            'approved'  => ['Produk diluluskan', '"' . $product['name'] . '" kini dipaparkan kepada pembeli.'],
            'rejected'  => ['Produk ditolak', '"' . $product['name'] . '" ditolak. Sebab: ' . $note . '. Edit produk untuk menghantar semula.'],
            'suspended' => ['Produk digantung', '"' . $product['name'] . '" digantung. Sebab: ' . $note],
        ];
        Notifier::send((int) $product['owner_id'], 'product_' . $to, $messages[$to][0], $messages[$to][1], '/seller/products/' . $product['id'] . '/edit');
        flash('success', 'Produk "' . $product['name'] . '": ' . approval_label($to) . '.');
        redirect(input('return') === 'queue' ? '/admin/products?status=pending' : '/admin/products/' . $product['id']);
    }

    private function find(string $id): array
    {
        $product = Database::fetch(
            'SELECT p.*, s.shop_name, s.slug AS shop_slug, s.user_id AS owner_id, c.name AS category_name
             FROM products p JOIN seller_profiles s ON s.id = p.seller_id JOIN categories c ON c.id = p.category_id WHERE p.id = ?',
            [route_id($id)]
        );
        if (!$product) {
            abort(404, 'Produk tidak dijumpai.');
        }
        return $product;
    }
}
