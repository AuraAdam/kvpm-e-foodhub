<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ImageUpload;
use App\Core\Validator;
use App\Services\Notifier;

/**
 * A signed-in customer applies to open a shop. An administrator must approve
 * the application before the account becomes a seller. Applying never grants
 * administrator rights.
 */
final class SellerApplicationController extends Controller
{
    public function show(): void
    {
        $this->render('customer/seller_apply', [
            'title'   => 'Buka kedai',
            'profile' => Auth::sellerProfile(),
        ]);
    }

    public function submit(): void
    {
        $profile = Auth::sellerProfile();
        if ($profile && in_array($profile['status'], ['pending', 'approved', 'suspended'], true)) {
            flash('info', 'Anda tidak boleh menghantar permohonan baharu sekarang.');
            redirect('/seller/apply');
        }

        $v = new Validator($_POST, [
            'shop_name'           => 'required|min:3|max:100',
            'campus_name'         => 'required|min:3|max:150',
            'description'         => 'required|min:20|max:1000',
            'phone'               => 'required|phone',
            'collection_location' => 'required|min:3|max:150',
            'collection_hours'    => 'max:100',
        ], [
            'shop_name' => 'Nama kedai', 'campus_name' => 'Nama kolej/kampus', 'description' => 'Penerangan kedai', 'phone' => 'Telefon kedai',
            'collection_location' => 'Lokasi pengambilan', 'collection_hours' => 'Waktu operasi',
        ]);
        $data = $v->validated();
        if (!$v->fails()) {
            $dup = Database::value('SELECT id FROM seller_profiles WHERE shop_name = ? AND user_id <> ?', [$data['shop_name'], Auth::id()]);
            if ($dup) {
                $v->addError('shop_name', 'Nama kedai ini sudah digunakan.');
            }
        }
        if (empty($_POST['agree'])) {
            $v->addError('agree', 'Sila sahkan bahawa maklumat adalah benar dan anda akan mematuhi terma.');
        }

        $logoPath = $profile['logo_path'] ?? null;
        if (!$v->fails() && !empty($_FILES['logo']['name'])) {
            try {
                $logoPath = ImageUpload::store($_FILES['logo'], 'shops');
            } catch (\InvalidArgumentException $e) {
                $v->addError('logo', $e->getMessage());
            }
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), '/seller/apply');
        }

        $fields = [
            'shop_name'           => $data['shop_name'],
            'campus_name'         => $data['campus_name'],
            'description'         => $data['description'],
            'phone'               => $data['phone'],
            'collection_location' => $data['collection_location'],
            'collection_hours'    => $data['collection_hours'] !== '' ? $data['collection_hours'] : null,
            'logo_path'           => $logoPath,
            'status'              => 'pending',
            'review_note'         => null,
            'updated_at'          => now(),
        ];
        if ($profile) {
            // Re-applying after a rejection.
            $fields['slug'] = unique_slug('seller_profiles', $data['shop_name'], (int) $profile['id']);
            Database::update('seller_profiles', $fields, 'id = :id', ['id' => (int) $profile['id']]);
        } else {
            $fields += ['user_id' => Auth::id(), 'slug' => unique_slug('seller_profiles', $data['shop_name']), 'created_at' => now()];
            Database::insert('seller_profiles', $fields);
        }
        Notifier::admins('seller_application', 'Permohonan kedai baharu', '"' . $data['shop_name'] . '" memohon untuk membuka kedai.', '/admin/sellers?status=pending');
        flash('success', 'Permohonan anda telah dihantar. Pentadbir akan menyemaknya.');
        redirect('/seller/apply');
    }
}
