<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Authentication state. The user record is re-read from the database on
 * every request, so suspensions and role changes take effect immediately.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;
    private static ?array $sellerProfile = null;
    private static bool $sellerLoaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if (is_int($id)) {
                $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$id]);
                if ($user && $user['status'] === 'active') {
                    self::$user = $user;
                } else {
                    // Account deleted or suspended while signed in.
                    Session::forget('user_id');
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function is(string $role): bool
    {
        return self::role() === $role;
    }

    /** Seller profile of the current user (any status), or null. */
    public static function sellerProfile(): ?array
    {
        if (!self::$sellerLoaded) {
            self::$sellerLoaded = true;
            if (self::check()) {
                self::$sellerProfile = Database::fetch('SELECT * FROM seller_profiles WHERE user_id = ?', [self::id()]);
            }
        }
        return self::$sellerProfile;
    }

    public static function login(array $user): void
    {
        Session::regenerate(); // prevent session fixation
        Session::put('user_id', (int) $user['id']);
        Csrf::token();
        self::$user = null;
        self::$loaded = false;
        self::$sellerLoaded = false;
        Database::update('users', ['last_login_at' => now()], 'id = :id', ['id' => (int) $user['id']]);
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    public static function homePath(): string
    {
        return match (self::role()) {
            'admin'  => '/admin',
            'seller' => '/seller',
            default  => '/',
        };
    }
}
