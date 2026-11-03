<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;

final class AccountController extends Controller
{
    public function profile(): void
    {
        $user = Auth::user();
        $stats = null;
        if (Auth::role() !== 'admin') {
            $stats = Database::fetch(
                "SELECT COUNT(DISTINCT o.id) AS orders,
                        SUM(CASE WHEN so.status IN ('pending','confirmed','preparing','ready') THEN 1 ELSE 0 END) AS open_parts
                 FROM orders o JOIN seller_orders so ON so.order_id = o.id WHERE o.customer_id = ?",
                [Auth::id()]
            );
        }
        $this->render('customer/profile', [
            'title' => 'Akaun saya',
            'user'  => $user,
            'stats' => $stats,
            'shop'  => Auth::sellerProfile(),
        ], Auth::role() === 'customer' ? 'main' : 'dashboard');
    }

    public function updateProfile(): void
    {
        // Only name and phone can be changed here. E-mail, role and status cannot be changed by users.
        $v = new Validator($_POST, [
            'name'  => 'required|min:3|max:100',
            'phone' => 'required|phone',
        ], ['name' => 'Nama penuh', 'phone' => 'Nombor telefon']);
        if ($v->fails()) {
            back_with_errors($v->errors(), '/account');
        }
        $data = $v->validated();
        Database::update('users', ['name' => $data['name'], 'phone' => $data['phone'], 'updated_at' => now()], 'id = :id', ['id' => Auth::id()]);
        flash('success', 'Profil anda telah dikemas kini.');
        redirect('/account');
    }

    public function showPassword(): void
    {
        $this->render('customer/password', [
            'title'  => 'Tukar kata laluan',
            'forced' => (bool) Auth::user()['must_change_password'],
        ], Auth::role() === 'customer' ? 'main' : 'dashboard');
    }

    public function updatePassword(): void
    {
        $min = (int) config('security.password_min_length');
        $v = new Validator($_POST, [
            'current_password' => 'required',
            'password'         => "required|min:$min|max:72|confirmed",
        ], ['current_password' => 'Kata laluan semasa', 'password' => 'Kata laluan baharu']);

        if (!$v->fails()) {
            if (!password_verify((string) $_POST['current_password'], Auth::user()['password_hash'])) {
                $v->addError('current_password', 'Kata laluan semasa tidak betul.');
            } elseif ($error = AuthController::passwordStrengthError((string) $_POST['password'])) {
                $v->addError('password', $error);
            } elseif (hash_equals((string) $_POST['current_password'], (string) $_POST['password'])) {
                $v->addError('password', 'Kata laluan baharu mesti berbeza daripada kata laluan semasa.');
            }
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), '/account/password');
        }

        Database::update('users', [
            'password_hash'        => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
            'must_change_password' => 0,
            'updated_at'           => now(),
        ], 'id = :id', ['id' => Auth::id()]);
        Session::regenerate();
        flash('success', 'Kata laluan anda telah ditukar.');
        redirect(Auth::homePath() === '/' ? '/account' : Auth::homePath());
    }

    public function notifications(): void
    {
        $total = (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [Auth::id()]);
        $pagination = paginate($total, 20);
        $items = Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            [Auth::id()]
        );
        $this->render('customer/notifications', [
            'title'         => 'Notifikasi',
            'notifications' => $items,
            'pagination'    => $pagination,
        ], Auth::role() === 'customer' ? 'main' : 'dashboard');
    }

    /** Mark one notification (id) or all as read. Opens the linked page when "open" is set. */
    public function markRead(): void
    {
        $id = input('id');
        if ($id === 'all') {
            Database::query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [Auth::id()]);
            flash('success', 'Semua notifikasi ditandakan sebagai dibaca.');
            redirect('/notifications');
        }
        $id = route_id($id);
        $notification = Database::fetch('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [$id, Auth::id()]);
        if (!$notification) {
            abort(404, 'Notifikasi tidak dijumpai.');
        }
        Database::query('UPDATE notifications SET is_read = 1 WHERE id = ?', [$id]);
        if (input('open') === '1' && $notification['link']) {
            redirect(safe_local_path($notification['link'], '/notifications'));
        }
        redirect('/notifications');
    }
}
