<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Records important actions (approvals, suspensions, overrides, role changes).
 * Never pass passwords, tokens or other secrets in $details.
 */
final class AuditLog
{
    public static function record(string $action, string $entityType, ?int $entityId, array $details = []): void
    {
        Database::insert('audit_logs', [
            'actor_id'    => Auth::id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => client_ip(),
            'created_at'  => now(),
        ]);
    }

    public static function describe(string $action): string
    {
        return [
            'seller.approved'      => 'Meluluskan kedai',
            'seller.rejected'      => 'Menolak permohonan kedai',
            'seller.suspended'     => 'Menggantung kedai',
            'seller.reactivated'   => 'Mengaktifkan semula kedai',
            'user.suspended'       => 'Menggantung akaun',
            'user.activated'       => 'Mengaktifkan akaun',
            'user.role_changed'    => 'Menukar peranan pengguna',
            'user.password_reset'  => 'Menetapkan kata laluan sementara',
            'product.approved'     => 'Meluluskan produk',
            'product.rejected'     => 'Menolak produk',
            'product.suspended'    => 'Menggantung produk',
            'order.override'       => 'Mengubah status pesanan (pentadbir)',
            'order.note'           => 'Menambah nota penyelesaian pesanan',
            'category.created'     => 'Mencipta kategori',
            'category.updated'     => 'Mengemas kini kategori',
            'category.deleted'     => 'Memadam kategori',
            'review.hidden'        => 'Menyembunyikan ulasan',
            'review.published'     => 'Menerbitkan semula ulasan',
            'report.resolved'      => 'Menyelesaikan laporan produk',
            'report.dismissed'     => 'Menolak laporan produk',
            'announcement.created' => 'Mencipta pengumuman',
            'announcement.updated' => 'Mengemas kini pengumuman',
            'announcement.deleted' => 'Memadam pengumuman',
            'settings.updated'     => 'Mengemas kini tetapan sistem',
            'admin.created'        => 'Mencipta akaun pentadbir (CLI)',
        ][$action] ?? $action;
    }
}
