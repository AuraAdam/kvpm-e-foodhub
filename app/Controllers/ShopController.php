<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\Catalogue;

final class ShopController extends Controller
{
    public function index(): void
    {
        $campus = mb_substr(trim(query('campus')), 0, 150);
        $campuses = Database::fetchAll("SELECT DISTINCT s.campus_name FROM seller_profiles s JOIN users u ON u.id = s.user_id
            WHERE s.status = 'approved' AND u.status = 'active' ORDER BY s.campus_name");
        $this->render('shop/shops', [
            'title' => 'Kedai',
            'shops' => Catalogue::approvedShops(0, $campus),
            'campuses' => $campuses,
            'selectedCampus' => $campus,
        ]);
    }

    public function show(string $slug): void
    {
        $shop = Database::fetch(
            "SELECT s.* FROM seller_profiles s JOIN users u ON u.id = s.user_id
             WHERE s.slug = ? AND s.status = 'approved' AND u.status = 'active'",
            [$slug]
        );
        if (!$shop) {
            abort(404, 'Kedai tidak dijumpai atau tidak aktif.');
        }
        $filters = [
            'shop_id'      => (int) $shop['id'],
            'q'            => mb_substr(query('q'), 0, 80),
            'availability' => query('availability') === 'in_stock' ? 'in_stock' : '',
            'sort'         => array_key_exists(query('sort'), Catalogue::SORTS) ? query('sort') : 'newest',
        ];
        $result = Catalogue::search($filters, (int) config('pagination.catalogue'));
        $rating = Database::fetch(
            "SELECT AVG(r.rating) AS avg_rating, COUNT(*) AS total FROM reviews r
             JOIN products p ON p.id = r.product_id WHERE p.seller_id = ? AND r.status = 'published'",
            [(int) $shop['id']]
        );

        $this->render('shop/shop', [
            'title'      => $shop['shop_name'],
            'shop'       => $shop,
            'filters'    => $filters,
            'products'   => $result['items'],
            'pagination' => $result['pagination'],
            'rating'     => $rating,
            'sorts'      => Catalogue::SORTS,
        ]);
    }
}
