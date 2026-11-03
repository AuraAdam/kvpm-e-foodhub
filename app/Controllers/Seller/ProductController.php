<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Core\Database;
use App\Core\ImageUpload;
use App\Core\Validator;
use App\Services\Notifier;

/**
 * Product management for the signed-in seller. Every lookup is filtered by
 * seller_id, so a seller can never read or change another shop's products.
 */
final class ProductController extends SellerController
{
    private const MAX_IMAGES = 5;

    private const FILTERS = [
        'available'   => 'Dijual',
        'unavailable' => 'Tidak dijual',
        'low'         => 'Stok rendah',
        'pending'     => 'Menunggu kelulusan',
        'rejected'    => 'Ditolak / digantung',
        'archived'    => 'Diarkib',
    ];

    public function index(): void
    {
        $where = ['p.seller_id = :sid'];
        $params = ['sid' => $this->shopId()];
        $filter = array_key_exists(query('availability'), self::FILTERS) ? query('availability') : '';

        $where[] = $filter === 'archived' ? 'p.archived_at IS NOT NULL' : 'p.archived_at IS NULL';
        match ($filter) {
            'available'   => $where[] = "p.is_available = 1 AND p.stock > 0 AND p.approval_status = 'approved'",
            'unavailable' => $where[] = '(p.is_available = 0 OR p.stock = 0)',
            'low'         => $where[] = 'p.stock <= ' . (int) setting('low_stock_threshold', '5'),
            'pending'     => $where[] = "p.approval_status = 'pending'",
            'rejected'    => $where[] = "p.approval_status IN ('rejected','suspended')",
            default       => null,
        };
        if (($q = mb_substr(query('q'), 0, 80)) !== '') {
            $where[] = "(p.name LIKE :q1 ESCAPE '!' OR p.sku LIKE :q2 ESCAPE '!')";
            $params['q1'] = $params['q2'] = '%' . like_escape($q) . '%';
        }
        if (($cat = query_int('category')) > 0) {
            $where[] = 'p.category_id = :cat';
            $params['cat'] = $cat;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM products p WHERE $whereSql", $params);
        $pagination = paginate($total, (int) config('pagination.dashboard'));
        $products = Database::fetchAll(
            "SELECT p.*, c.name AS category_name,
                (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS image_path
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE $whereSql ORDER BY p.updated_at DESC, p.id DESC
             LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );

        $this->dashboard('seller/products', [
            'title'      => 'Pengurusan Produk',
            'products'   => $products,
            'pagination' => $pagination,
            'filters'    => self::FILTERS,
            'filter'     => $filter,
            'categories' => $this->categories(),
            'threshold'  => (int) setting('low_stock_threshold', '5'),
        ]);
    }

    public function create(): void
    {
        $this->dashboard('seller/product_form', [
            'title'      => 'Tambah produk',
            'product'    => null,
            'images'     => [],
            'categories' => $this->categories(),
            'needsApproval' => setting('require_product_approval', '1') === '1',
        ]);
    }

    public function store(): void
    {
        $data = $this->validate('/seller/products/create');
        $needsApproval = setting('require_product_approval', '1') === '1';

        $files = $this->uploadedFiles();
        if (count($files) > self::MAX_IMAGES) {
            back_with_errors(['images' => 'Maksimum ' . self::MAX_IMAGES . ' gambar bagi setiap produk.'], '/seller/products/create');
        }
        $paths = $this->storeImages($files, '/seller/products/create');

        $id = Database::transaction(function () use ($data, $needsApproval, $paths): int {
            $id = Database::insert('products', $data + [
                'seller_id'       => $this->shopId(),
                'slug'            => unique_slug('products', $data['name']),
                'approval_status' => $needsApproval ? 'pending' : 'approved',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $this->attachImages($id, $paths, 0);
            return $id;
        });

        if ($needsApproval) {
            Notifier::admins('product_pending', 'Produk menunggu kelulusan', '"' . $data['name'] . '" dari ' . $this->shop()['shop_name'] . ' perlu disemak.', '/admin/products?status=pending');
            flash('success', 'Produk disimpan dan dihantar untuk kelulusan pentadbir.');
        } else {
            flash('success', 'Produk disimpan dan kini dipaparkan kepada pembeli.');
        }
        redirect('/seller/products/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $product = $this->ownProduct($id);
        $this->dashboard('seller/product_form', [
            'title'      => 'Edit produk',
            'product'    => $product,
            'images'     => $this->images((int) $product['id']),
            'categories' => $this->categories((int) $product['category_id']),
            'needsApproval' => setting('require_product_approval', '1') === '1',
        ]);
    }

    public function update(string $id): void
    {
        $product = $this->ownProduct($id);
        $back = '/seller/products/' . $product['id'] . '/edit';
        $data = $this->validate($back, (int) $product['category_id']);

        $existing = count($this->images((int) $product['id']));
        $files = $this->uploadedFiles();
        if ($existing + count($files) > self::MAX_IMAGES) {
            back_with_errors(['images' => 'Maksimum ' . self::MAX_IMAGES . ' gambar bagi setiap produk. Padam gambar lama dahulu.'], $back);
        }
        $paths = $this->storeImages($files, $back);

        // Rejected listings go back for review. Approved listings go back for review only if
        // the name or description changed (price/stock edits do not need re-approval).
        $status = $product['approval_status'];
        $needsApproval = setting('require_product_approval', '1') === '1';
        $textChanged = $data['name'] !== $product['name'] || $data['description'] !== (string) $product['description'];
        if ($status === 'rejected' || ($needsApproval && $status === 'approved' && $textChanged)) {
            $status = $needsApproval ? 'pending' : 'approved';
        }

        Database::transaction(function () use ($product, $data, $status, $paths, $existing): void {
            Database::update('products', $data + [
                'slug'            => $data['name'] !== $product['name'] ? unique_slug('products', $data['name'], (int) $product['id']) : $product['slug'],
                'approval_status' => $status,
                'updated_at'      => now(),
            ], 'id = :id AND seller_id = :sid', ['id' => (int) $product['id'], 'sid' => $this->shopId()]);
            $this->attachImages((int) $product['id'], $paths, $existing);
        });

        if ($status === 'pending' && $product['approval_status'] !== 'pending') {
            Notifier::admins('product_pending', 'Produk menunggu kelulusan', '"' . $data['name'] . '" dari ' . $this->shop()['shop_name'] . ' telah dikemas kini dan perlu disemak.', '/admin/products?status=pending');
            flash('success', 'Perubahan disimpan. Produk dihantar semula untuk kelulusan pentadbir.');
        } else {
            flash('success', 'Perubahan produk disimpan.');
        }
        redirect($back);
    }

    /** Quick stock update from the product list. */
    public function stock(string $id): void
    {
        $product = $this->ownProduct($id);
        $stock = input('stock');
        if (!preg_match('/^\d{1,6}$/', $stock)) {
            flash('error', 'Stok mesti nombor bulat 0 atau lebih.');
            back('/seller/products');
        }
        Database::update('products', ['stock' => (int) $stock, 'updated_at' => now()], 'id = :id AND seller_id = :sid', ['id' => (int) $product['id'], 'sid' => $this->shopId()]);
        flash('success', 'Stok "' . $product['name'] . '" dikemas kini kepada ' . (int) $stock . '.');
        back('/seller/products');
    }

    public function availability(string $id): void
    {
        $product = $this->ownProduct($id);
        $new = (int) $product['is_available'] === 1 ? 0 : 1;
        Database::update('products', ['is_available' => $new, 'updated_at' => now()], 'id = :id AND seller_id = :sid', ['id' => (int) $product['id'], 'sid' => $this->shopId()]);
        flash('success', $new ? 'Produk kini ditandakan sebagai dijual.' : 'Produk ditandakan sebagai tidak dijual.');
        back('/seller/products');
    }

    /** Archive (soft delete) or restore. Archived products stay in old orders. */
    public function archive(string $id): void
    {
        $product = $this->ownProduct($id);
        $archiving = $product['archived_at'] === null;
        Database::update('products', [
            'archived_at'  => $archiving ? now() : null,
            'is_available' => $archiving ? 0 : (int) $product['is_available'],
            'updated_at'   => now(),
        ], 'id = :id AND seller_id = :sid', ['id' => (int) $product['id'], 'sid' => $this->shopId()]);
        flash('success', $archiving ? 'Produk diarkibkan. Ia tidak lagi dipaparkan kepada pembeli.' : 'Produk dipulihkan. Tandakan sebagai dijual apabila sedia.');
        redirect('/seller/products' . ($archiving ? '' : '?availability=archived'));
    }

    public function deleteImage(string $id, string $imageId): void
    {
        $product = $this->ownProduct($id);
        $image = Database::fetch('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [route_id($imageId), (int) $product['id']]);
        if (!$image) {
            abort(404, 'Gambar tidak dijumpai.');
        }
        Database::query('DELETE FROM product_images WHERE id = ?', [(int) $image['id']]);
        ImageUpload::delete($image['path']);
        if ((int) $image['is_primary'] === 1) {
            $next = Database::value('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id LIMIT 1', [(int) $product['id']]);
            if ($next) {
                Database::query('UPDATE product_images SET is_primary = 1 WHERE id = ?', [(int) $next]);
            }
        }
        flash('success', 'Gambar dipadam.');
        redirect('/seller/products/' . $product['id'] . '/edit');
    }

    public function primaryImage(string $id, string $imageId): void
    {
        $product = $this->ownProduct($id);
        $imageId = route_id($imageId);
        if (!Database::value('SELECT id FROM product_images WHERE id = ? AND product_id = ?', [$imageId, (int) $product['id']])) {
            abort(404, 'Gambar tidak dijumpai.');
        }
        Database::query('UPDATE product_images SET is_primary = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE product_id = ?', [$imageId, (int) $product['id']]);
        flash('success', 'Gambar utama ditukar.');
        redirect('/seller/products/' . $product['id'] . '/edit');
    }

    // -----------------------------------------------------------------

    private function ownProduct(string $id): array
    {
        $product = Database::fetch('SELECT * FROM products WHERE id = ? AND seller_id = ?', [route_id($id), $this->shopId()]);
        if (!$product) {
            abort(404, 'Produk tidak dijumpai.');
        }
        return $product;
    }

    private function images(int $productId): array
    {
        return Database::fetchAll('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order, id', [$productId]);
    }

    /** Visible categories, plus the product's current one even if it has since been hidden. */
    private function categories(int $includeId = 0): array
    {
        return Database::fetchAll('SELECT id, name FROM categories WHERE is_visible = 1 OR id = ? ORDER BY sort_order, name', [$includeId]);
    }

    private function validate(string $back, int $currentCategory = 0): array
    {
        $v = new Validator($_POST, [
            'name'        => 'required|min:3|max:120',
            'category_id' => 'required|integer',
            'description' => 'required|min:10|max:3000',
            'price'       => 'required|money|min_value:0.10|max_value:9999',
            'stock'       => 'required|integer|min_value:0|max_value:99999',
            'sku'         => 'max:40',
        ], ['name' => 'Nama produk', 'category_id' => 'Kategori', 'description' => 'Penerangan', 'price' => 'Harga', 'stock' => 'Stok', 'sku' => 'SKU']);
        $data = $v->validated();
        if (!$v->fails()) {
            $categoryOk = Database::value('SELECT id FROM categories WHERE id = ? AND (is_visible = 1 OR id = ?)', [(int) $data['category_id'], $currentCategory]);
            if (!$categoryOk) {
                $v->addError('category_id', 'Sila pilih kategori yang sah.');
            }
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), $back);
        }
        return [
            'name'         => $data['name'],
            'category_id'  => (int) $data['category_id'],
            'description'  => $data['description'],
            'price'        => number_format((float) $data['price'], 2, '.', ''),
            'stock'        => (int) $data['stock'],
            'sku'          => $data['sku'] !== '' ? $data['sku'] : null,
            'is_available' => !empty($_POST['is_available']) ? 1 : 0,
        ];
    }

    /** Normalise the images[] upload field into a list of single-file arrays. */
    private function uploadedFiles(): array
    {
        $f = $_FILES['images'] ?? null;
        if (!$f || !is_array($f['name'] ?? null)) {
            return [];
        }
        $files = [];
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $name === '') {
                continue;
            }
            $files[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $files;
    }

    private function storeImages(array $files, string $back): array
    {
        $paths = [];
        foreach ($files as $file) {
            try {
                $paths[] = ImageUpload::store($file, 'products');
            } catch (\InvalidArgumentException $e) {
                foreach ($paths as $p) {
                    ImageUpload::delete($p);
                }
                back_with_errors(['images' => '"' . $file['name'] . '": ' . $e->getMessage()], $back);
            }
        }
        return $paths;
    }

    private function attachImages(int $productId, array $paths, int $existing): void
    {
        foreach ($paths as $i => $path) {
            Database::insert('product_images', [
                'product_id' => $productId,
                'path'       => $path,
                'is_primary' => ($existing === 0 && $i === 0) ? 1 : 0,
                'sort_order' => $existing + $i,
                'created_at' => now(),
            ]);
        }
    }
}
