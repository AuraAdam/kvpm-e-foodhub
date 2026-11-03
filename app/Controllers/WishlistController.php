<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Services\Catalogue;

final class WishlistController extends Controller
{
    public function index(): void
    {
        $products = Database::fetchAll(
            'SELECT ' . Catalogue::CARD_COLUMNS . ' ' . Catalogue::BASE_FROM . '
             JOIN wishlist_items w ON w.product_id = p.id
             WHERE w.user_id = :uid AND ' . Catalogue::VISIBLE . ' ORDER BY w.created_at DESC',
            ['uid' => Auth::id()]
        );
        $this->render('customer/wishlist', ['title' => 'Senarai simpanan', 'products' => $products]);
    }

    public function toggle(): void
    {
        $productId = route_id(input('product_id'));
        $exists = Database::value('SELECT id FROM wishlist_items WHERE user_id = ? AND product_id = ?', [Auth::id(), $productId]);
        if ($exists) {
            Database::query('DELETE FROM wishlist_items WHERE id = ?', [(int) $exists]);
            flash('success', 'Dibuang dari senarai simpanan.');
        } else {
            $visible = Database::value('SELECT p.id ' . Catalogue::BASE_FROM . ' WHERE p.id = ? AND ' . Catalogue::VISIBLE, [$productId]);
            if (!$visible) {
                abort(404, 'Produk tidak dijumpai.');
            }
            Database::insert('wishlist_items', ['user_id' => Auth::id(), 'product_id' => $productId, 'created_at' => now()]);
            flash('success', 'Disimpan ke senarai simpanan.');
        }
        back('/wishlist');
    }
}
