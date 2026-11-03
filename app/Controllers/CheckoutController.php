<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\CheckoutException;
use App\Services\OrderService;

final class CheckoutController extends Controller
{
    public function show(): void
    {
        $cart = CartService::contents(Auth::id());
        if (!$cart['groups']) {
            flash('info', 'Troli anda kosong.');
            redirect('/cart');
        }
        if ($cart['has_issues']) {
            flash('warning', 'Sesetengah item dalam troli perlu dikemas kini sebelum membuat pesanan.');
            redirect('/cart');
        }
        // One-time token: a form can only ever create one order.
        $token = bin2hex(random_bytes(32));
        Session::put('checkout_token', $token);

        $this->render('customer/checkout', [
            'title'         => 'Buat pesanan',
            'cart'          => $cart,
            'user'          => Auth::user(),
            'checkoutToken' => $token,
            'onlineEnabled' => setting('allow_online_sim_payment', '1') === '1',
        ]);
    }

    public function place(): void
    {
        $sentToken = input('checkout_token');
        $sessionToken = (string) Session::get('checkout_token', '');
        if ($sessionToken === '' || !hash_equals($sessionToken, $sentToken)) {
            flash('warning', 'Pesanan ini telah dihantar atau borang telah tamat tempoh. Semak "Pesanan saya" sebelum mencuba semula.');
            redirect('/orders');
        }

        $v = new Validator($_POST, [
            'contact_name'   => 'required|min:3|max:100',
            'contact_phone'  => 'required|phone',
            'notes'          => 'max:500',
            'payment_method' => 'required|in:cash,online_sim',
        ], ['contact_name' => 'Nama penerima', 'contact_phone' => 'Nombor telefon', 'notes' => 'Nota', 'payment_method' => 'Kaedah pembayaran']);
        if ($v->fails()) {
            back_with_errors($v->errors(), '/checkout');
        }

        try {
            $number = OrderService::checkout(Auth::id(), $v->validated(), $sentToken);
        } catch (CheckoutException $e) {
            flash('error', $e->getMessage());
            redirect('/cart');
        }
        Session::forget('checkout_token');

        if ($v->validated()['payment_method'] === 'online_sim') {
            flash('success', 'Pesanan dibuat. Lengkapkan bayaran simulasi untuk menghantarnya kepada penjual.');
            redirect('/orders/' . $number . '/pay');
        }
        flash('success', 'Pesanan anda telah dihantar kepada penjual.');
        redirect('/orders/' . $number . '?placed=1');
    }
}
