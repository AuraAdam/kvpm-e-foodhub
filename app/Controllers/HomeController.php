<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\Catalogue;

final class HomeController extends Controller
{
    public function index(): void
    {
        $announcements = Database::fetchAll(
            'SELECT * FROM announcements WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= :now1) AND (ends_at IS NULL OR ends_at >= :now2)
             ORDER BY created_at DESC LIMIT 3',
            ['now1' => now(), 'now2' => now()]
        );

        $this->render('shop/home', [
            'title'         => 'Laman Utama',
            'categories'    => Catalogue::visibleCategories(),
            'latest'        => Catalogue::latest(8),
            'popular'       => Catalogue::popular(4),
            'shops'         => Catalogue::approvedShops(4),
            'announcements' => $announcements,
        ]);
    }

    /** Static information pages (help, terms, privacy). */
    public function page(string $page): void
    {
        $pages = [
            'help'    => 'Cara menggunakan E-FoodHub',
            'terms'   => 'Terma penggunaan',
            'privacy' => 'Notis privasi',
        ];
        if (!isset($pages[$page])) {
            abort(404, 'Halaman tidak dijumpai.');
        }
        $this->render('pages/' . $page, ['title' => $pages[$page]]);
    }
}
