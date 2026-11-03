<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Read queries for the public catalogue.
 *
 * A product is publicly visible only when ALL of these hold:
 *  - it is approved by an administrator and not archived
 *  - its shop is approved and the shop owner's account is active
 *  - its category is visible
 * "Available" (can be bought now) additionally needs is_available = 1 and stock > 0.
 */
final class Catalogue
{
    public const VISIBLE = "p.approval_status = 'approved' AND p.archived_at IS NULL
        AND s.status = 'approved' AND u.status = 'active' AND c.is_visible = 1";

    public const BASE_FROM = 'FROM products p
        JOIN seller_profiles s ON s.id = p.seller_id
        JOIN users u ON u.id = s.user_id
        JOIN categories c ON c.id = p.category_id';

    /** Columns for product cards: primary image, shop, average rating. */
    public const CARD_COLUMNS = "p.id, p.name, p.slug, p.price, p.stock, p.is_available, p.created_at,
        s.shop_name, s.campus_name, s.slug AS shop_slug, c.name AS category_name,
        (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path,
        (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'published') AS avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'published') AS review_count";

    /** Allow-listed sort options (never put user input into ORDER BY directly). */
    public const SORTS = [
        'newest'     => ['label' => 'Terbaru', 'sql' => 'p.created_at DESC, p.id DESC'],
        'price_asc'  => ['label' => 'Harga: rendah ke tinggi', 'sql' => 'p.price ASC, p.id DESC'],
        'price_desc' => ['label' => 'Harga: tinggi ke rendah', 'sql' => 'p.price DESC, p.id DESC'],
        'name_asc'   => ['label' => 'Nama: A–Z', 'sql' => 'p.name ASC, p.id ASC'],
        'popular'    => ['label' => 'Paling laris', 'sql' => 'sold_count DESC, p.created_at DESC'],
    ];

    /**
     * @param array{q?:string, category?:string, shop_id?:int, min?:string, max?:string, availability?:string, sort?:string} $filters
     * @return array{items: array, pagination: array}
     */
    public static function search(array $filters, int $perPage): array
    {
        [$where, $params] = self::filterSql($filters);

        $total = (int) Database::value('SELECT COUNT(*) ' . self::BASE_FROM . " WHERE $where", $params);
        $pagination = paginate($total, $perPage);

        $sortKey = array_key_exists($filters['sort'] ?? '', self::SORTS) ? $filters['sort'] : 'newest';
        $order = self::SORTS[$sortKey]['sql'];
        $limit = (int) $pagination['per_page'];
        $offset = (int) $pagination['offset'];

        $items = Database::fetchAll(
            'SELECT ' . self::CARD_COLUMNS . ', ' . self::soldCountSql() . ' AS sold_count ' . self::BASE_FROM .
            " WHERE $where ORDER BY $order LIMIT $limit OFFSET $offset",
            $params
        );
        return ['items' => $items, 'pagination' => $pagination];
    }

    /** Units sold in completed orders (used for "popular"). */
    public static function soldCountSql(): string
    {
        return "(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
            JOIN seller_orders so ON so.id = oi.seller_order_id
            WHERE oi.product_id = p.id AND so.status = 'completed')";
    }

    private static function filterSql(array $filters): array
    {
        $where = [self::VISIBLE];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $q = mb_substr($q, 0, 80);
            $where[] = "(p.name LIKE :q1 ESCAPE '!' OR p.description LIKE :q2 ESCAPE '!' OR s.shop_name LIKE :q3 ESCAPE '!')";
            $like = '%' . like_escape($q) . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = :category';
            $params['category'] = (string) $filters['category'];
        }
        if (!empty($filters['shop_id'])) {
            $where[] = 'p.seller_id = :shop_id';
            $params['shop_id'] = (int) $filters['shop_id'];
        }
        if (isset($filters['min']) && is_numeric($filters['min']) && $filters['min'] >= 0) {
            $where[] = 'p.price >= :min';
            $params['min'] = (string) round((float) $filters['min'], 2);
        }
        if (isset($filters['max']) && is_numeric($filters['max']) && $filters['max'] > 0) {
            $where[] = 'p.price <= :max';
            $params['max'] = (string) round((float) $filters['max'], 2);
        }
        if (($filters['availability'] ?? '') === 'in_stock') {
            $where[] = 'p.is_available = 1 AND p.stock > 0';
        }
        return [implode(' AND ', $where), $params];
    }

    public static function latest(int $limit): array
    {
        return Database::fetchAll('SELECT ' . self::CARD_COLUMNS . ' ' . self::BASE_FROM . ' WHERE ' . self::VISIBLE .
            ' AND p.is_available = 1 AND p.stock > 0 ORDER BY p.created_at DESC, p.id DESC LIMIT ' . (int) $limit);
    }

    /** Best sellers by units in completed orders. Returns [] if nothing has been sold yet. */
    public static function popular(int $limit): array
    {
        return Database::fetchAll('SELECT * FROM (SELECT ' . self::CARD_COLUMNS . ', ' . self::soldCountSql() . ' AS sold_count ' .
            self::BASE_FROM . ' WHERE ' . self::VISIBLE . ' AND p.is_available = 1 AND p.stock > 0) t
            WHERE t.sold_count > 0 ORDER BY t.sold_count DESC, t.created_at DESC LIMIT ' . (int) $limit);
    }

    public static function findVisibleBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT p.*, s.shop_name, s.slug AS shop_slug, s.id AS shop_id, s.user_id AS shop_user_id,
                s.logo_path AS shop_logo, s.campus_name, s.collection_location, s.collection_hours, s.phone AS shop_phone,
                c.name AS category_name, c.slug AS category_slug ' . self::BASE_FROM .
            ' WHERE p.slug = :slug AND ' . self::VISIBLE, ['slug' => $slug]);
    }

    public static function related(array $product, int $limit): array
    {
        return Database::fetchAll('SELECT ' . self::CARD_COLUMNS . ' ' . self::BASE_FROM . ' WHERE ' . self::VISIBLE .
            ' AND p.category_id = :cat AND p.id <> :id AND p.is_available = 1 AND p.stock > 0
            ORDER BY p.created_at DESC LIMIT ' . (int) $limit, ['cat' => (int) $product['category_id'], 'id' => (int) $product['id']]);
    }

    public static function visibleCategories(): array
    {
        return Database::fetchAll("SELECT c.*,
                (SELECT COUNT(*) FROM products p
                    JOIN seller_profiles s ON s.id = p.seller_id
                    JOIN users u ON u.id = s.user_id
                    WHERE p.category_id = c.id AND p.approval_status = 'approved' AND p.archived_at IS NULL
                      AND s.status = 'approved' AND u.status = 'active') AS product_count
            FROM categories c WHERE c.is_visible = 1 ORDER BY c.sort_order, c.name");
    }

    public static function approvedShops(int $limit = 0, string $campus = ''): array
    {
        $sql = "SELECT s.*, (SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id
                    WHERE p.seller_id = s.id AND p.approval_status = 'approved' AND p.archived_at IS NULL AND c.is_visible = 1) AS product_count
                FROM seller_profiles s JOIN users u ON u.id = s.user_id
                WHERE s.status = 'approved' AND u.status = 'active'
                AND (:campus = '' OR s.campus_name = :campus_match)
                ORDER BY product_count DESC, s.shop_name";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return Database::fetchAll($sql, ['campus' => $campus, 'campus_match' => $campus]);
    }
}
