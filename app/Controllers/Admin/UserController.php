<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Services\AuditLog;
use App\Services\Notifier;

/**
 * User management. Safeguards:
 *  - an administrator cannot suspend or demote their own account (self-lockout)
 *  - the last active administrator can never be suspended or demoted
 *  - promoting/demoting administrators requires re-entering your own password
 *  - every change is written to the audit log
 * Users are never hard-deleted (orders and reviews depend on them); suspend instead.
 */
final class UserController extends Controller
{
    public function index(): void
    {
        $where = ['1 = 1'];
        $params = [];
        if (in_array(query('role'), ['customer', 'seller', 'admin'], true)) {
            $where[] = 'u.role = :role';
            $params['role'] = query('role');
        }
        if (in_array(query('status'), ['active', 'suspended'], true)) {
            $where[] = 'u.status = :status';
            $params['status'] = query('status');
        }
        if (($q = mb_substr(query('q'), 0, 80)) !== '') {
            $where[] = "(u.name LIKE :q1 ESCAPE '!' OR u.email LIKE :q2 ESCAPE '!' OR u.phone LIKE :q3 ESCAPE '!')";
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . like_escape($q) . '%';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM users u WHERE $whereSql", $params);
        $pagination = paginate($total, (int) config('pagination.dashboard'));
        $users = Database::fetchAll(
            "SELECT u.*, s.shop_name, s.status AS shop_status FROM users u LEFT JOIN seller_profiles s ON s.user_id = u.id
             WHERE $whereSql ORDER BY u.created_at DESC, u.id DESC LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        $this->dashboard('admin/users', ['title' => 'Pengurusan Pengguna', 'users' => $users, 'pagination' => $pagination]);
    }

    public function show(string $id): void
    {
        $user = $this->findUser($id);
        $this->dashboard('admin/user', [
            'title'  => $user['name'],
            'user'   => $user,
            'shop'   => Database::fetch('SELECT * FROM seller_profiles WHERE user_id = ?', [(int) $user['id']]),
            'orders' => Database::fetchAll(
                'SELECT o.*, (SELECT COUNT(*) FROM seller_orders WHERE order_id = o.id) AS parts FROM orders o WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 10',
                [(int) $user['id']]
            ),
            'logs'   => Database::fetchAll(
                "SELECT a.*, u.name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id
                 WHERE a.entity_type = 'user' AND a.entity_id = ? ORDER BY a.created_at DESC LIMIT 10",
                [(int) $user['id']]
            ),
            'isSelf' => (int) $user['id'] === Auth::id(),
        ]);
    }

    public function status(string $id): void
    {
        $user = $this->findUser($id);
        $to = input('status');
        $reason = mb_substr(input('reason'), 0, 255);
        if (!in_array($to, ['active', 'suspended'], true) || $to === $user['status']) {
            flash('error', 'Status tidak sah.');
            redirect('/admin/users/' . $user['id']);
        }
        if ($to === 'suspended') {
            if ((int) $user['id'] === Auth::id()) {
                flash('error', 'Anda tidak boleh menggantung akaun anda sendiri.');
                redirect('/admin/users/' . $user['id']);
            }
            if ($user['role'] === 'admin' && $this->activeAdminCount() <= 1) {
                flash('error', 'Pentadbir aktif terakhir tidak boleh digantung.');
                redirect('/admin/users/' . $user['id']);
            }
            if ($reason === '') {
                flash('error', 'Sila nyatakan sebab penggantungan.');
                redirect('/admin/users/' . $user['id']);
            }
        }
        Database::update('users', ['status' => $to, 'updated_at' => now()], 'id = :id', ['id' => (int) $user['id']]);
        AuditLog::record($to === 'suspended' ? 'user.suspended' : 'user.activated', 'user', (int) $user['id'], ['email' => $user['email'], 'reason' => $reason]);
        flash('success', $to === 'suspended' ? 'Akaun digantung. Pengguna tidak lagi boleh log masuk.' : 'Akaun diaktifkan semula.');
        redirect('/admin/users/' . $user['id']);
    }

    /** Promote to / demote from administrator. Seller roles are granted only via seller approval. */
    public function role(string $id): void
    {
        $user = $this->findUser($id);
        $to = input('role');
        $back = '/admin/users/' . $user['id'];

        if (!password_verify((string) ($_POST['confirm_password'] ?? ''), Auth::user()['password_hash'])) {
            flash('error', 'Kata laluan pengesahan anda tidak betul. Tiada perubahan dibuat.');
            redirect($back);
        }
        if ($to === 'admin' && $user['role'] === 'customer') {
            $new = 'admin';
        } elseif ($to === 'demote' && $user['role'] === 'admin') {
            if ((int) $user['id'] === Auth::id()) {
                flash('error', 'Anda tidak boleh menurunkan peranan anda sendiri.');
                redirect($back);
            }
            if ($this->activeAdminCount() <= 1) {
                flash('error', 'Pentadbir terakhir tidak boleh diturunkan peranan.');
                redirect($back);
            }
            $new = 'customer';
        } else {
            flash('error', 'Perubahan peranan ini tidak dibenarkan. Hanya pelanggan boleh dinaikkan menjadi pentadbir.');
            redirect($back);
        }

        Database::update('users', ['role' => $new, 'updated_at' => now()], 'id = :id', ['id' => (int) $user['id']]);
        AuditLog::record('user.role_changed', 'user', (int) $user['id'], ['email' => $user['email'], 'from' => $user['role'], 'to' => $new]);
        Notifier::send((int) $user['id'], 'role_changed', 'Peranan akaun ditukar', 'Peranan akaun anda kini: ' . role_label($new) . '.');
        flash('success', 'Peranan ditukar kepada ' . role_label($new) . '.');
        redirect($back);
    }

    /**
     * Account recovery without e-mail: the admin sets a one-time temporary password,
     * shows it once, and the user must change it at next sign-in.
     */
    public function resetPassword(string $id): void
    {
        $user = $this->findUser($id);
        if ((int) $user['id'] === Auth::id()) {
            flash('error', 'Gunakan halaman "Tukar kata laluan" untuk akaun anda sendiri.');
            redirect('/admin/users/' . $user['id']);
        }
        $temporary = 'Kv' . random_int(1000, 9999) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
        Database::update('users', [
            'password_hash'        => password_hash($temporary, PASSWORD_DEFAULT),
            'must_change_password' => 1,
            'updated_at'           => now(),
        ], 'id = :id', ['id' => (int) $user['id']]);
        Database::query('DELETE FROM login_attempts WHERE email = ?', [$user['email']]);
        AuditLog::record('user.password_reset', 'user', (int) $user['id'], ['email' => $user['email']]); // the password itself is never logged
        flash('warning', 'Kata laluan sementara untuk ' . $user['email'] . ': ' . $temporary . ' — berikan kepada pengguna secara bersemuka. Ia hanya dipaparkan sekali dan mesti ditukar semasa log masuk.');
        redirect('/admin/users/' . $user['id']);
    }

    private function findUser(string $id): array
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [route_id($id)]);
        if (!$user) {
            abort(404, 'Pengguna tidak dijumpai.');
        }
        return $user;
    }

    private function activeAdminCount(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
    }
}
