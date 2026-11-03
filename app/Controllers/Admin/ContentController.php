<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;
use App\Services\AuditLog;

/** Reviews, product reports, announcements and system settings. */
final class ContentController extends Controller
{
    // ------------------------------------------------------------ Reviews
    public function reviews(): void
    {
        $status = in_array(query('status'), ['published', 'hidden'], true) ? query('status') : '';
        $where = $status !== '' ? 'WHERE r.status = :status' : '';
        $params = $status !== '' ? ['status' => $status] : [];
        $total = (int) Database::value("SELECT COUNT(*) FROM reviews r $where", $params);
        $pagination = paginate($total, (int) config('pagination.dashboard'));
        $reviews = Database::fetchAll(
            "SELECT r.*, u.name AS reviewer, p.name AS product_name, p.slug AS product_slug
             FROM reviews r JOIN users u ON u.id = r.user_id JOIN products p ON p.id = r.product_id
             $where ORDER BY r.created_at DESC LIMIT " . (int) $pagination['per_page'] . ' OFFSET ' . (int) $pagination['offset'],
            $params
        );
        $this->dashboard('admin/reviews', ['title' => 'Ulasan', 'reviews' => $reviews, 'pagination' => $pagination, 'status' => $status]);
    }

    public function moderateReview(string $id): void
    {
        $review = Database::fetch('SELECT * FROM reviews WHERE id = ?', [route_id($id)]);
        if (!$review) {
            abort(404, 'Ulasan tidak dijumpai.');
        }
        $to = $review['status'] === 'published' ? 'hidden' : 'published';
        $note = mb_substr(input('note'), 0, 255);
        if ($to === 'hidden' && $note === '') {
            flash('error', 'Sila nyatakan sebab ulasan disembunyikan.');
            back('/admin/reviews');
        }
        Database::update('reviews', ['status' => $to, 'moderation_note' => $note !== '' ? $note : null, 'updated_at' => now()], 'id = :id', ['id' => (int) $review['id']]);
        AuditLog::record('review.' . $to, 'review', (int) $review['id'], ['note' => $note]);
        flash('success', $to === 'hidden' ? 'Ulasan disembunyikan daripada umum.' : 'Ulasan diterbitkan semula.');
        back('/admin/reviews');
    }

    // ------------------------------------------------------------ Product reports
    public function reports(): void
    {
        $status = in_array(query('status'), ['open', 'resolved', 'dismissed'], true) ? query('status') : 'open';
        $reports = Database::fetchAll(
            'SELECT r.*, u.name AS reporter, p.name AS product_name, p.id AS pid, p.approval_status, s.shop_name, a.name AS resolver
             FROM product_reports r JOIN users u ON u.id = r.reporter_id JOIN products p ON p.id = r.product_id
             JOIN seller_profiles s ON s.id = p.seller_id LEFT JOIN users a ON a.id = r.resolved_by
             WHERE r.status = ? ORDER BY r.created_at DESC LIMIT 100',
            [$status]
        );
        $this->dashboard('admin/reports', ['title' => 'Laporan produk', 'reports' => $reports, 'status' => $status]);
    }

    public function resolveReport(string $id): void
    {
        $report = Database::fetch("SELECT * FROM product_reports WHERE id = ? AND status = 'open'", [route_id($id)]);
        if (!$report) {
            abort(404, 'Laporan tidak dijumpai atau sudah diselesaikan.');
        }
        $to = input('status') === 'dismissed' ? 'dismissed' : 'resolved';
        $note = mb_substr(input('note'), 0, 255);
        if ($note === '') {
            flash('error', 'Sila tulis nota penyelesaian.');
            redirect('/admin/reports');
        }
        Database::update('product_reports', ['status' => $to, 'resolution_note' => $note, 'resolved_by' => Auth::id(), 'resolved_at' => now()], 'id = :id', ['id' => (int) $report['id']]);
        AuditLog::record('report.' . $to, 'product_report', (int) $report['id'], ['product_id' => (int) $report['product_id'], 'note' => $note]);
        flash('success', 'Laporan ditandakan sebagai ' . approval_label($to) . '.');
        redirect('/admin/reports');
    }

    // ------------------------------------------------------------ Announcements
    public function announcements(): void
    {
        $this->dashboard('admin/announcements', [
            'title'         => 'Pengumuman',
            'announcements' => Database::fetchAll('SELECT * FROM announcements ORDER BY created_at DESC'),
            'editing'       => query_int('edit') > 0 ? Database::fetch('SELECT * FROM announcements WHERE id = ?', [query_int('edit')]) : null,
        ]);
    }

    public function saveAnnouncement(): void
    {
        $id = input('id') !== '' ? route_id(input('id')) : null;
        $v = new Validator($_POST, [
            'title'     => 'required|min:3|max:150',
            'body'      => 'required|min:5|max:500',
            'starts_at' => 'max:16',
            'ends_at'   => 'max:16',
        ], ['title' => 'Tajuk', 'body' => 'Kandungan', 'starts_at' => 'Mula', 'ends_at' => 'Tamat']);
        $data = $v->validated();
        $starts = $this->dateTime($data['starts_at'] ?? '');
        $ends = $this->dateTime($data['ends_at'] ?? '');
        if (($data['starts_at'] ?? '') !== '' && $starts === null) {
            $v->addError('starts_at', 'Tarikh mula tidak sah.');
        }
        if (($data['ends_at'] ?? '') !== '' && $ends === null) {
            $v->addError('ends_at', 'Tarikh tamat tidak sah.');
        }
        if ($starts && $ends && $ends < $starts) {
            $v->addError('ends_at', 'Tarikh tamat mesti selepas tarikh mula.');
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), '/admin/announcements' . ($id ? '?edit=' . $id : ''));
        }
        $fields = [
            'title' => $data['title'], 'body' => $data['body'], 'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            'starts_at' => $starts, 'ends_at' => $ends, 'updated_at' => now(),
        ];
        if ($id) {
            if (!Database::value('SELECT id FROM announcements WHERE id = ?', [$id])) {
                abort(404, 'Pengumuman tidak dijumpai.');
            }
            Database::update('announcements', $fields, 'id = :id', ['id' => $id]);
            AuditLog::record('announcement.updated', 'announcement', $id, ['title' => $data['title']]);
        } else {
            $id = Database::insert('announcements', $fields + ['created_by' => Auth::id(), 'created_at' => now()]);
            AuditLog::record('announcement.created', 'announcement', $id, ['title' => $data['title']]);
        }
        flash('success', 'Pengumuman disimpan.');
        redirect('/admin/announcements');
    }

    public function deleteAnnouncement(string $id): void
    {
        $a = Database::fetch('SELECT * FROM announcements WHERE id = ?', [route_id($id)]);
        if (!$a) {
            abort(404, 'Pengumuman tidak dijumpai.');
        }
        Database::query('DELETE FROM announcements WHERE id = ?', [(int) $a['id']]);
        AuditLog::record('announcement.deleted', 'announcement', (int) $a['id'], ['title' => $a['title']]);
        flash('success', 'Pengumuman dipadam.');
        redirect('/admin/announcements');
    }

    private function dateTime(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $value);
        return $dt ? $dt->format('Y-m-d H:i:00') : null;
    }

    // ------------------------------------------------------------ Settings
    private const SETTINGS = [
        'require_product_approval' => ['label' => 'Produk baharu perlu kelulusan pentadbir', 'type' => 'bool'],
        'allow_online_sim_payment' => ['label' => 'Benarkan bayaran dalam talian (simulasi)', 'type' => 'bool'],
        'low_stock_threshold'      => ['label' => 'Had stok rendah (unit)', 'type' => 'int', 'min' => 0, 'max' => 1000],
        'max_quantity_per_item'    => ['label' => 'Kuantiti maksimum setiap produk dalam satu pesanan', 'type' => 'int', 'min' => 1, 'max' => 999],
        'contact_email'            => ['label' => 'E-mel hubungan', 'type' => 'email'],
        'contact_phone'            => ['label' => 'Telefon hubungan', 'type' => 'text', 'max' => 20],
        'collection_info'          => ['label' => 'Maklumat pengambilan (dipaparkan semasa membuat pesanan)', 'type' => 'textarea', 'max' => 500],
    ];

    public function settings(): void
    {
        $values = [];
        foreach (Database::fetchAll('SELECT setting_key, setting_value FROM system_settings') as $r) {
            $values[$r['setting_key']] = $r['setting_value'];
        }
        $this->dashboard('admin/settings', ['title' => 'Tetapan sistem', 'definitions' => self::SETTINGS, 'values' => $values]);
    }

    public function saveSettings(): void
    {
        $errors = [];
        $clean = [];
        foreach (self::SETTINGS as $key => $def) {
            $raw = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
            switch ($def['type']) {
                case 'bool':
                    $clean[$key] = !empty($_POST[$key]) ? '1' : '0';
                    break;
                case 'int':
                    if (!preg_match('/^\d+$/', $raw) || (int) $raw < $def['min'] || (int) $raw > $def['max']) {
                        $errors[$key] = $def['label'] . " mesti nombor antara {$def['min']} dan {$def['max']}.";
                    }
                    $clean[$key] = $raw;
                    break;
                case 'email':
                    if (!filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = 'E-mel hubungan tidak sah.';
                    }
                    $clean[$key] = $raw;
                    break;
                default:
                    if ($raw === '' || mb_strlen($raw) > $def['max']) {
                        $errors[$key] = $def['label'] . " diperlukan (maksimum {$def['max']} aksara).";
                    }
                    $clean[$key] = $raw;
            }
        }
        if ($errors) {
            back_with_errors($errors, '/admin/settings');
        }
        $changed = [];
        foreach ($clean as $key => $value) {
            $old = Database::value('SELECT setting_value FROM system_settings WHERE setting_key = ?', [$key]);
            if ($old === null) {
                Database::insert('system_settings', ['setting_key' => $key, 'setting_value' => $value, 'updated_at' => now()]);
                $changed[$key] = $value;
            } elseif ($old !== $value) {
                Database::update('system_settings', ['setting_value' => $value, 'updated_at' => now()], 'setting_key = :k', ['k' => $key]);
                $changed[$key] = ['from' => $old, 'to' => $value];
            }
        }
        if ($changed) {
            AuditLog::record('settings.updated', 'settings', null, $changed);
        }
        flash('success', $changed ? 'Tetapan disimpan.' : 'Tiada perubahan.');
        redirect('/admin/settings');
    }
}
