<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\ImageUpload;
use App\Core\Validator;
use App\Services\AuditLog;

/**
 * Categories. A category that still has products cannot be deleted
 * (the foreign key also prevents it); hide it instead.
 */
final class CategoryController extends Controller
{
    public function index(): void
    {
        $categories = Database::fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
             FROM categories c ORDER BY c.sort_order, c.name'
        );
        $this->dashboard('admin/categories', [
            'title'      => 'Kategori',
            'categories' => $categories,
            'editing'    => query_int('edit') > 0 ? Database::fetch('SELECT * FROM categories WHERE id = ?', [query_int('edit')]) : null,
        ]);
    }

    public function store(): void
    {
        $data = $this->validate(null);
        $image = $this->image(null);
        $id = Database::insert('categories', $data + [
            'slug'       => unique_slug('categories', $data['name']),
            'image_path' => $image,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditLog::record('category.created', 'category', $id, ['name' => $data['name']]);
        flash('success', 'Kategori "' . $data['name'] . '" dicipta.');
        redirect('/admin/categories');
    }

    public function update(string $id): void
    {
        $category = $this->find($id);
        $data = $this->validate($category);
        $image = $this->image($category);
        Database::update('categories', $data + [
            'slug'       => $data['name'] !== $category['name'] ? unique_slug('categories', $data['name'], (int) $category['id']) : $category['slug'],
            'image_path' => $image,
            'updated_at' => now(),
        ], 'id = :id', ['id' => (int) $category['id']]);
        if ($image !== $category['image_path']) {
            ImageUpload::delete($category['image_path']);
        }
        AuditLog::record('category.updated', 'category', (int) $category['id'], ['name' => $data['name'], 'visible' => $data['is_visible']]);
        flash('success', 'Kategori dikemas kini.');
        redirect('/admin/categories');
    }

    public function delete(string $id): void
    {
        $category = $this->find($id);
        $used = (int) Database::value('SELECT COUNT(*) FROM products WHERE category_id = ?', [(int) $category['id']]);
        if ($used > 0) {
            flash('error', "Kategori ini masih digunakan oleh $used produk. Sembunyikan kategori atau pindahkan produk dahulu.");
            redirect('/admin/categories');
        }
        Database::query('DELETE FROM categories WHERE id = ?', [(int) $category['id']]);
        ImageUpload::delete($category['image_path']);
        AuditLog::record('category.deleted', 'category', (int) $category['id'], ['name' => $category['name']]);
        flash('success', 'Kategori dipadam.');
        redirect('/admin/categories');
    }

    private function find(string $id): array
    {
        $c = Database::fetch('SELECT * FROM categories WHERE id = ?', [route_id($id)]);
        if (!$c) {
            abort(404, 'Kategori tidak dijumpai.');
        }
        return $c;
    }

    private function validate(?array $existing): array
    {
        $back = $existing ? '/admin/categories?edit=' . $existing['id'] : '/admin/categories';
        $v = new Validator($_POST, [
            'name'        => 'required|min:2|max:60',
            'description' => 'max:255',
            'sort_order'  => 'integer|min_value:0|max_value:999',
        ], ['name' => 'Nama kategori', 'description' => 'Penerangan', 'sort_order' => 'Susunan']);
        $data = $v->validated();
        if (!$v->fails()) {
            $dup = Database::value('SELECT id FROM categories WHERE name = ? AND id <> ?', [$data['name'], (int) ($existing['id'] ?? 0)]);
            if ($dup) {
                $v->addError('name', 'Nama kategori ini sudah wujud.');
            }
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), $back);
        }
        return [
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort_order'  => (int) ($data['sort_order'] ?: 0),
            'is_visible'  => !empty($_POST['is_visible']) ? 1 : 0,
        ];
    }

    private function image(?array $existing): ?string
    {
        if (empty($_FILES['image']['name'])) {
            return $existing['image_path'] ?? null;
        }
        try {
            return ImageUpload::store($_FILES['image'], 'categories');
        } catch (\InvalidArgumentException $e) {
            back_with_errors(['image' => $e->getMessage()], $existing ? '/admin/categories?edit=' . $existing['id'] : '/admin/categories');
        }
    }
}
