<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Services\CartService;

final class CartController extends Controller
{
    public function index(): void
    {
        $this->render('customer/cart', [
            'title' => 'Troli',
            'cart'  => CartService::contents(Auth::id()),
        ]);
    }

    public function add(): void
    {
        $productId = route_id(input('product_id'));
        $quantity = (int) (preg_match('/^\d{1,3}$/', input('quantity', '1')) ? input('quantity', '1') : 0);
        $error = CartService::add(Auth::id(), $productId, $quantity);

        if ($this->wantsJson()) {
            $this->json($error === null, $error ?? 'Ditambah ke troli.', CartService::count(Auth::id()));
        }
        if ($error !== null) {
            flash('error', $error);
            back('/products');
        }
        flash('success', 'Produk ditambah ke troli.');
        if (input('buy_now') === '1') {
            redirect('/cart');
        }
        back('/cart');
    }

    public function update(): void
    {
        $productId = route_id(input('product_id'));
        $quantity = preg_match('/^\d{1,3}$/', input('quantity')) ? (int) input('quantity') : -1;
        if ($quantity < 0) {
            flash('error', 'Kuantiti tidak sah.');
            redirect('/cart');
        }
        $error = CartService::update(Auth::id(), $productId, $quantity);
        flash($error ? 'error' : 'success', $error ?? ($quantity === 0 ? 'Item dibuang dari troli.' : 'Kuantiti dikemas kini.'));
        redirect('/cart');
    }

    public function remove(): void
    {
        CartService::remove(Auth::id(), route_id(input('product_id')));
        flash('success', 'Item dibuang dari troli.');
        redirect('/cart');
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function json(bool $ok, string $message, int $count): never
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 422);
        echo json_encode(['ok' => $ok, 'message' => $message, 'cart_count' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
