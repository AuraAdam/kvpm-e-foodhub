<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Shopping cart stored in the database (cart_items), one cart per signed-in user.
 * Prices are never stored in the cart; they are always read from products.
 */
final class CartService
{
    /**
     * Cart contents grouped by shop, with live price/stock checks.
     *
     * @return array{groups: array, total_sen: int, item_count: int, has_issues: bool}
     */
    public static function contents(int $userId): array
    {
        $rows = Database::fetchAll(
            "SELECT ci.product_id, ci.quantity, p.name, p.slug, p.price, p.stock, p.is_available,
                    p.approval_status, p.archived_at, s.id AS shop_id, s.shop_name, s.slug AS shop_slug,
                    s.user_id AS shop_user_id, s.status AS shop_status, s.collection_location, u.status AS owner_status,
                    c.is_visible AS category_visible,
                    (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             JOIN seller_profiles s ON s.id = p.seller_id
             JOIN users u ON u.id = s.user_id
             JOIN categories c ON c.id = p.category_id
             WHERE ci.user_id = ?
             ORDER BY s.shop_name, ci.id",
            [$userId]
        );

        $groups = [];
        $totalSen = 0;
        $count = 0;
        $hasIssues = false;
        foreach ($rows as $row) {
            $row['issue'] = self::issueFor($row, $userId);
            $row['unit_sen'] = to_sen($row['price']);
            $row['line_sen'] = $row['unit_sen'] * (int) $row['quantity'];
            if ($row['issue'] !== null) {
                $hasIssues = true;
            }

            $shopId = (int) $row['shop_id'];
            $groups[$shopId] ??= [
                'shop_id' => $shopId,
                'shop_name' => $row['shop_name'],
                'shop_slug' => $row['shop_slug'],
                'collection_location' => $row['collection_location'],
                'items' => [],
                'subtotal_sen' => 0,
            ];
            $groups[$shopId]['items'][] = $row;
            if ($row['issue'] === null) {
                $groups[$shopId]['subtotal_sen'] += $row['line_sen'];
                $totalSen += $row['line_sen'];
                $count += (int) $row['quantity'];
            }
        }
        return ['groups' => array_values($groups), 'total_sen' => $totalSen, 'item_count' => $count, 'has_issues' => $hasIssues];
    }

    /** Why this cart line cannot be ordered right now, or null if it can. */
    public static function issueFor(array $row, int $userId): ?string
    {
        if ($row['approval_status'] !== 'approved' || $row['archived_at'] !== null || $row['shop_status'] !== 'approved'
            || $row['owner_status'] !== 'active' || !(int) $row['category_visible']) {
            return 'Produk ini tidak lagi dijual. Sila buang dari troli.';
        }
        if ((int) $row['shop_user_id'] === $userId) {
            return 'Anda tidak boleh membeli produk kedai anda sendiri.';
        }
        if (!(int) $row['is_available'] || (int) $row['stock'] <= 0) {
            return 'Stok habis buat masa ini.';
        }
        if ((int) $row['quantity'] > (int) $row['stock']) {
            return 'Hanya ' . (int) $row['stock'] . ' unit tersedia. Kurangkan kuantiti.';
        }
        return null;
    }

    /** @return string|null error message, or null on success */
    public static function add(int $userId, int $productId, int $quantity): ?string
    {
        if ($quantity < 1) {
            return 'Kuantiti mesti sekurang-kurangnya 1.';
        }
        $product = Database::fetch('SELECT p.id, p.stock, p.is_available, s.user_id AS shop_user_id ' . Catalogue::BASE_FROM .
            ' WHERE p.id = ? AND ' . Catalogue::VISIBLE, [$productId]);
        if (!$product) {
            return 'Produk tidak dijumpai atau tidak lagi dijual.';
        }
        if ((int) $product['shop_user_id'] === $userId) {
            return 'Anda tidak boleh membeli produk kedai anda sendiri.';
        }
        if (!(int) $product['is_available'] || (int) $product['stock'] <= 0) {
            return 'Produk ini kehabisan stok.';
        }

        $existing = (int) Database::value('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?', [$userId, $productId]);
        $newQty = $existing + $quantity;
        $limitError = self::quantityError($newQty, (int) $product['stock']);
        if ($limitError !== null) {
            return $limitError . ($existing > 0 ? " (Troli anda sudah ada $existing unit.)" : '');
        }

        if ($existing > 0) {
            Database::query('UPDATE cart_items SET quantity = ?, updated_at = ? WHERE user_id = ? AND product_id = ?', [$newQty, now(), $userId, $productId]);
        } else {
            Database::insert('cart_items', ['user_id' => $userId, 'product_id' => $productId, 'quantity' => $newQty, 'created_at' => now(), 'updated_at' => now()]);
        }
        return null;
    }

    public static function update(int $userId, int $productId, int $quantity): ?string
    {
        if ($quantity <= 0) {
            self::remove($userId, $productId);
            return null;
        }
        $row = Database::fetch('SELECT p.stock FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.user_id = ? AND ci.product_id = ?', [$userId, $productId]);
        if (!$row) {
            return 'Item tidak ada dalam troli anda.';
        }
        $error = self::quantityError($quantity, (int) $row['stock']);
        if ($error !== null) {
            return $error;
        }
        Database::query('UPDATE cart_items SET quantity = ?, updated_at = ? WHERE user_id = ? AND product_id = ?', [$quantity, now(), $userId, $productId]);
        return null;
    }

    private static function quantityError(int $quantity, int $stock): ?string
    {
        $max = (int) setting('max_quantity_per_item', '20');
        if ($quantity > $max) {
            return "Maksimum $max unit bagi setiap produk dalam satu pesanan.";
        }
        if ($quantity > $stock) {
            return "Hanya $stock unit tersedia.";
        }
        return null;
    }

    public static function remove(int $userId, int $productId): void
    {
        Database::query('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?', [$userId, $productId]);
    }

    public static function count(int $userId): int
    {
        return (int) Database::value('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?', [$userId]);
    }
}
