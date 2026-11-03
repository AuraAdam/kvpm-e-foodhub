<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;
use App\Services\Catalogue;
use App\Services\Notifier;

final class ProductController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q'            => mb_substr(query('q'), 0, 80),
            'category'     => preg_match('/^[a-z0-9-]{1,80}$/', query('category')) ? query('category') : '',
            'min'          => is_numeric(query('min')) ? query('min') : '',
            'max'          => is_numeric(query('max')) ? query('max') : '',
            'availability' => query('availability') === 'in_stock' ? 'in_stock' : '',
            'sort'         => array_key_exists(query('sort'), Catalogue::SORTS) ? query('sort') : 'newest',
        ];
        $result = Catalogue::search($filters, (int) config('pagination.catalogue'));

        $currentCategory = null;
        $categories = Catalogue::visibleCategories();
        foreach ($categories as $c) {
            if ($c['slug'] === $filters['category']) {
                $currentCategory = $c;
            }
        }

        $this->render('shop/catalogue', [
            'title'           => $currentCategory['name'] ?? ($filters['q'] !== '' ? 'Carian: ' . $filters['q'] : 'Semua produk'),
            'filters'         => $filters,
            'products'        => $result['items'],
            'pagination'      => $result['pagination'],
            'categories'      => $categories,
            'currentCategory' => $currentCategory,
            'sorts'           => Catalogue::SORTS,
        ]);
    }

    public function show(string $slug): void
    {
        $product = Catalogue::findVisibleBySlug($slug);
        if (!$product) {
            abort(404, 'Produk tidak dijumpai atau tidak lagi dijual.');
        }
        $images = Database::fetchAll('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [(int) $product['id']]);
        $reviews = Database::fetchAll(
            "SELECT r.*, u.name AS reviewer FROM reviews r JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.status = 'published' ORDER BY r.created_at DESC LIMIT 20",
            [(int) $product['id']]
        );
        $rating = Database::fetch(
            "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = ? AND status = 'published'",
            [(int) $product['id']]
        );
        $inWishlist = Auth::check() && (bool) Database::value(
            'SELECT 1 FROM wishlist_items WHERE user_id = ? AND product_id = ?',
            [Auth::id(), (int) $product['id']]
        );

        $this->render('shop/product', [
            'title'      => $product['name'],
            'product'    => $product,
            'images'     => $images,
            'reviews'    => $reviews,
            'rating'     => $rating,
            'related'    => Catalogue::related($product, 4),
            'inWishlist' => $inWishlist,
            'isOwner'    => Auth::id() === (int) $product['shop_user_id'],
            'maxQty'     => min((int) $product['stock'], (int) setting('max_quantity_per_item', '20')),
        ]);
    }

    /** A shopper reports a listing to the administrators. */
    public function report(string $slug): void
    {
        $product = Catalogue::findVisibleBySlug($slug);
        if (!$product) {
            abort(404, 'Produk tidak dijumpai.');
        }
        $v = new Validator($_POST, [
            'reason'  => 'required|in:inaccurate,prohibited,offensive,scam,other',
            'details' => 'max:500',
        ], ['reason' => 'Sebab', 'details' => 'Butiran']);
        if ($v->fails()) {
            flash('error', implode(' ', $v->errors()));
            redirect('/product/' . $product['slug']);
        }
        $open = Database::value("SELECT id FROM product_reports WHERE product_id = ? AND reporter_id = ? AND status = 'open'", [(int) $product['id'], Auth::id()]);
        if ($open) {
            flash('info', 'Anda sudah melaporkan produk ini. Pentadbir sedang menyemaknya.');
            redirect('/product/' . $product['slug']);
        }
        $data = $v->validated();
        Database::insert('product_reports', [
            'product_id'  => (int) $product['id'],
            'reporter_id' => Auth::id(),
            'reason'      => $data['reason'],
            'details'     => $data['details'] !== '' ? $data['details'] : null,
            'status'      => 'open',
            'created_at'  => now(),
        ]);
        Notifier::admins('product_report', 'Laporan produk baharu', 'Produk "' . $product['name'] . '" telah dilaporkan.', '/admin/reports');
        flash('success', 'Terima kasih. Laporan anda telah dihantar kepada pentadbir.');
        redirect('/product/' . $product['slug']);
    }
}
