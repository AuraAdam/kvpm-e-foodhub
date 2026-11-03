<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Services\AuditLog;
use App\Services\Notifier;

/** Seller applications and shop status. */
final class SellerController extends Controller
{
    private const DECISIONS = [
        // action => [allowed from statuses, new status, audit action, requires reason]
        'approve'    => [['pending'], 'approved', 'seller.approved', false],
        'reject'     => [['pending'], 'rejected', 'seller.rejected', true],
        'suspend'    => [['approved'], 'suspended', 'seller.suspended', true],
        'reactivate' => [['suspended'], 'approved', 'seller.reactivated', false],
    ];

    public function index(): void
    {
        $status = in_array(query('status'), ['pending', 'approved', 'rejected', 'suspended'], true) ? query('status') : 'pending';
        $shops = Database::fetchAll(
            "SELECT s.*, u.name AS owner_name, u.email AS owner_email, u.status AS owner_status,
                (SELECT COUNT(*) FROM products p WHERE p.seller_id = s.id AND p.archived_at IS NULL) AS product_count
             FROM seller_profiles s JOIN users u ON u.id = s.user_id
             WHERE s.status = ? ORDER BY s.updated_at DESC",
            [$status]
        );
        $counts = [];
        foreach (Database::fetchAll('SELECT status, COUNT(*) AS n FROM seller_profiles GROUP BY status') as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }
        $this->dashboard('admin/sellers', ['title' => 'Permohonan & Kedai', 'shops' => $shops, 'status' => $status, 'counts' => $counts]);
    }

    public function decision(string $id): void
    {
        $shop = Database::fetch('SELECT s.*, u.role, u.id AS owner_id FROM seller_profiles s JOIN users u ON u.id = s.user_id WHERE s.id = ?', [route_id($id)]);
        if (!$shop) {
            abort(404, 'Kedai tidak dijumpai.');
        }
        $action = input('action');
        $note = mb_substr(input('note'), 0, 255);
        if (!isset(self::DECISIONS[$action])) {
            abort(400, 'Tindakan tidak sah.');
        }
        [$from, $to, $auditAction, $needsReason] = self::DECISIONS[$action];
        if (!in_array($shop['status'], $from, true)) {
            flash('error', 'Tindakan ini tidak sah untuk kedai berstatus "' . approval_label($shop['status']) . '".');
            redirect('/admin/sellers?status=' . $shop['status']);
        }
        if ($needsReason && $note === '') {
            flash('error', 'Sila nyatakan sebab.');
            redirect('/admin/sellers?status=' . $shop['status']);
        }
        if ($shop['role'] === 'admin') {
            flash('error', 'Akaun pentadbir tidak boleh menjadi penjual.');
            redirect('/admin/sellers?status=' . $shop['status']);
        }

        Database::transaction(function () use ($shop, $to, $note): void {
            Database::update('seller_profiles', [
                'status'      => $to,
                'review_note' => $note !== '' ? $note : null,
                'reviewed_by' => \App\Core\Auth::id(),
                'reviewed_at' => now(),
                'updated_at'  => now(),
            ], 'id = :id', ['id' => (int) $shop['id']]);
            // The account becomes a seller only when the shop is approved.
            if ($to === 'approved' && $shop['role'] === 'customer') {
                Database::update('users', ['role' => 'seller', 'updated_at' => now()], 'id = :id', ['id' => (int) $shop['owner_id']]);
            }
        });
        AuditLog::record($auditAction, 'seller_profile', (int) $shop['id'], ['shop' => $shop['shop_name'], 'note' => $note]);

        [$title, $message, $link] = match ($to) {
            'approved'  => ['Kedai anda telah diluluskan', '"' . $shop['shop_name'] . '" kini aktif. Mula tambah produk di Dashboard Penjual.', '/seller'],
            'rejected'  => ['Permohonan kedai ditolak', 'Sebab: ' . $note . '. Anda boleh mengemas kini dan memohon semula.', '/seller/apply'],
            'suspended' => ['Kedai anda digantung', 'Sebab: ' . $note . '. Produk anda tidak dipaparkan buat sementara.', '/seller/apply'],
        };
        Notifier::send((int) $shop['owner_id'], 'seller_' . $to, $title, $message, $link);
        flash('success', 'Kedai "' . $shop['shop_name'] . '": ' . approval_label($to) . '.');
        redirect('/admin/sellers?status=' . ($shop['status'] === 'pending' ? 'pending' : $to));
    }
}
