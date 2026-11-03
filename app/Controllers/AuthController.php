<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $next = safe_local_path(query('next'), '');
        if ($next !== '') {
            Session::put('intended', $next);
        }
        $this->render('auth/login', ['title' => 'Log masuk']);
    }

    public function login(): void
    {
        $email = mb_strtolower(input('email'));
        $password = (string) ($_POST['password'] ?? '');
        $ip = client_ip();

        if ($this->tooManyAttempts($email, $ip)) {
            $minutes = config('security.login_window_minutes');
            back_with_errors(['email' => "Terlalu banyak cubaan log masuk. Cuba lagi selepas $minutes minit."], '/login');
        }

        $user = $email !== '' ? Database::fetch('SELECT * FROM users WHERE email = ?', [$email]) : null;
        // Always run password_verify so response time does not reveal whether the e-mail exists.
        $hash = $user['password_hash'] ?? '$2y$10$i84wE.Xdtvxw3raPlBdhzumOVee8abg779kxDrY.N45uQG7PZrREm';
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            Database::insert('login_attempts', ['email' => $email, 'ip_address' => $ip, 'attempted_at' => now()]);
            back_with_errors(['email' => 'E-mel atau kata laluan tidak betul.'], '/login');
        }
        if ($user['status'] !== 'active') {
            back_with_errors(['email' => 'Akaun anda telah digantung. Hubungi pentadbir untuk bantuan.'], '/login');
        }

        Database::query('DELETE FROM login_attempts WHERE email = ?', [$email]);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => (int) $user['id']]);
        }

        $intended = Session::pull('intended');
        Auth::login($user);
        flash('success', 'Selamat datang, ' . $user['name'] . '.');

        if ((int) $user['must_change_password'] === 1) {
            redirect('/account/password');
        }
        $home = Auth::homePath();
        redirect($home === '/' ? safe_local_path($intended, '/') : $home);
    }

    private function tooManyAttempts(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - 60 * (int) config('security.login_window_minutes'));
        $max = (int) config('security.login_max_attempts');
        $byEmail = (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= ?', [$email, $since]);
        $byIp = (int) Database::value('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?', [$ip, $since]);
        return $byEmail >= $max || $byIp >= $max * 4;
    }

    public function showRegister(): void
    {
        $this->render('auth/register', ['title' => 'Daftar akaun']);
    }

    public function register(): void
    {
        $min = (int) config('security.password_min_length');
        $v = new Validator($_POST, [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:150',
            'phone'    => 'required|phone',
            'password' => "required|min:$min|max:72|confirmed",
        ], ['name' => 'Nama penuh', 'email' => 'E-mel', 'phone' => 'Nombor telefon', 'password' => 'Kata laluan']);

        $data = $v->validated();
        if (!$v->fails()) {
            $passwordError = self::passwordStrengthError((string) $_POST['password']);
            if ($passwordError) {
                $v->addError('password', $passwordError);
            }
            if (Database::value('SELECT id FROM users WHERE email = ?', [mb_strtolower($data['email'])])) {
                $v->addError('email', 'E-mel ini sudah didaftarkan. Log masuk atau gunakan e-mel lain.');
            }
        }
        if (empty($_POST['agree'])) {
            $v->addError('agree', 'Sila bersetuju dengan terma penggunaan.');
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), '/register');
        }

        $id = Database::insert('users', [
            'name'          => $data['name'],
            'email'         => mb_strtolower($data['email']),
            'phone'         => $data['phone'],
            'password_hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
            'role'          => 'customer', // public registration can never create sellers or administrators
            'status'        => 'active',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        Auth::login(Database::fetch('SELECT * FROM users WHERE id = ?', [$id]));
        flash('success', 'Akaun anda telah didaftarkan. Selamat datang ke E-FoodHub!');
        redirect(safe_local_path(Session::pull('intended'), '/'));
    }

    /** Must contain at least one letter and one digit. */
    public static function passwordStrengthError(string $password): ?string
    {
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'Kata laluan mesti mengandungi sekurang-kurangnya satu huruf dan satu nombor.';
        }
        return null;
    }

    public function forgot(): void
    {
        $this->render('auth/forgot', ['title' => 'Lupa kata laluan']);
    }

    public function logout(): void
    {
        Auth::logout();
        Session::start(config('session'));
        flash('success', 'Anda telah log keluar.');
        redirect('/');
    }
}
