<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Server-side access control. Hiding a button is never the only protection:
 * each protected route lists its middleware in routes/web.php.
 */
final class Middleware
{
    public static function run(string $name): void
    {
        match ($name) {
            'guest'   => self::guest(),
            'auth'    => self::auth(),
            'shopper' => self::shopper(),
            'seller'  => self::seller(),
            'admin'   => self::admin(),
            default   => throw new \LogicException("Unknown middleware: $name"),
        };
    }

    private static function guest(): void
    {
        if (Auth::check()) {
            redirect(Auth::homePath());
        }
    }

    private static function auth(): void
    {
        if (!Auth::check()) {
            Session::put('intended', current_path_with_query());
            flash('info', 'Sila log masuk untuk meneruskan.');
            redirect('/login');
        }
        // Users given a temporary password must choose a new one first.
        if (Auth::user()['must_change_password'] && !in_array(current_path(), ['/account/password', '/logout'], true)) {
            flash('warning', 'Sila tukar kata laluan sementara anda sebelum meneruskan.');
            redirect('/account/password');
        }
    }

    private static function shopper(): void
    {
        self::auth();
        if (!in_array(Auth::role(), ['customer', 'seller'], true)) {
            throw new HttpException(403, 'Akaun pentadbir tidak boleh membuat pesanan.');
        }
    }

    private static function seller(): void
    {
        self::auth();
        if (Auth::role() !== 'seller') {
            throw new HttpException(403, 'Halaman ini hanya untuk penjual.');
        }
        $shop = Auth::sellerProfile();
        if ($shop === null || $shop['status'] !== 'approved') {
            flash('warning', 'Kedai anda belum aktif. Semak status permohonan anda.');
            redirect('/seller/apply');
        }
    }

    private static function admin(): void
    {
        self::auth();
        if (Auth::role() !== 'admin') {
            throw new HttpException(403, 'Halaman ini hanya untuk pentadbir.');
        }
    }
}
