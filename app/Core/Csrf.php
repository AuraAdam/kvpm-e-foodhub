<?php
declare(strict_types=1);

namespace App\Core;

/** Cross-site request forgery protection (synchroniser token pattern). */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }
        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function verifyRequest(): void
    {
        $sent = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = Session::get('_csrf');
        if (!is_string($sent) || !is_string($expected) || !hash_equals($expected, $sent)) {
            throw new HttpException(419, 'Sesi borang telah tamat atau tidak sah. Muat semula halaman dan cuba lagi.');
        }
    }
}
