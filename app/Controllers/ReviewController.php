<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;

final class ReviewController extends Controller
{
    /**
     * Only the buyer of a COMPLETED order line can review it, once per line.
     */
    public function store(): void
    {
        $v = new Validator($_POST, [
            'order_item_id' => 'required|integer',
            'rating'        => 'required|integer|min_value:1|max_value:5',
            'comment'       => 'max:1000',
        ], ['order_item_id' => 'Item', 'rating' => 'Penilaian', 'comment' => 'Ulasan']);
        if ($v->fails()) {
            flash('error', implode(' ', $v->errors()));
            back('/orders');
        }
        $data = $v->validated();

        $item = Database::fetch(
            "SELECT oi.id, oi.product_id, o.order_number FROM order_items oi
             JOIN seller_orders so ON so.id = oi.seller_order_id
             JOIN orders o ON o.id = so.order_id
             WHERE oi.id = ? AND o.customer_id = ? AND so.status = 'completed'",
            [(int) $data['order_item_id'], Auth::id()]
        );
        if (!$item) {
            flash('error', 'Anda hanya boleh mengulas produk daripada pesanan anda yang telah selesai.');
            back('/orders');
        }
        if (Database::value('SELECT id FROM reviews WHERE order_item_id = ?', [(int) $item['id']])) {
            flash('info', 'Anda sudah mengulas item ini.');
            redirect('/orders/' . $item['order_number']);
        }

        Database::insert('reviews', [
            'product_id'    => (int) $item['product_id'],
            'user_id'       => Auth::id(),
            'order_item_id' => (int) $item['id'],
            'rating'        => (int) $data['rating'],
            'comment'       => $data['comment'] !== '' ? $data['comment'] : null,
            'status'        => 'published',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        flash('success', 'Terima kasih! Ulasan anda telah diterbitkan.');
        redirect('/orders/' . $item['order_number']);
    }

    public function mine(): void
    {
        $reviews = Database::fetchAll(
            'SELECT r.*, p.name AS product_name, p.slug AS product_slug FROM reviews r
             JOIN products p ON p.id = r.product_id WHERE r.user_id = ? ORDER BY r.created_at DESC',
            [Auth::id()]
        );
        $this->render('customer/reviews', ['title' => 'Ulasan saya', 'reviews' => $reviews]);
    }
}
