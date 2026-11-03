<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Core\Database;
use App\Core\ImageUpload;
use App\Core\Validator;

final class ShopController extends SellerController
{
    public function edit(): void
    {
        $this->dashboard('seller/shop', ['title' => 'Profil kedai', 'shop' => $this->shop()]);
    }

    public function update(): void
    {
        $shop = $this->shop();
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
        if (!$v->fails() && Database::value('SELECT id FROM seller_profiles WHERE shop_name = ? AND id <> ?', [$data['shop_name'], (int) $shop['id']])) {
            $v->addError('shop_name', 'Nama kedai ini sudah digunakan.');
        }
        $logo = $shop['logo_path'];
        if (!$v->fails() && !empty($_FILES['logo']['name'])) {
            try {
                $logo = ImageUpload::store($_FILES['logo'], 'shops');
            } catch (\InvalidArgumentException $e) {
                $v->addError('logo', $e->getMessage());
            }
        }
        if ($v->fails()) {
            back_with_errors($v->errors(), '/seller/shop');
        }

        Database::update('seller_profiles', [
            'shop_name'           => $data['shop_name'],
            'campus_name'         => $data['campus_name'],
            'slug'                => $data['shop_name'] !== $shop['shop_name'] ? unique_slug('seller_profiles', $data['shop_name'], (int) $shop['id']) : $shop['slug'],
            'description'         => $data['description'],
            'phone'               => $data['phone'],
            'collection_location' => $data['collection_location'],
            'collection_hours'    => $data['collection_hours'] !== '' ? $data['collection_hours'] : null,
            'logo_path'           => $logo,
            'updated_at'          => now(),
        ], 'id = :id', ['id' => (int) $shop['id']]);
        if ($logo !== $shop['logo_path']) {
            ImageUpload::delete($shop['logo_path']);
        }
        flash('success', 'Profil kedai dikemas kini.');
        redirect('/seller/shop');
    }
}
