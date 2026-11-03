<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\Controller;
use App\Core\Auth;

/** Base class for seller pages: gives the current shop's id. */
abstract class SellerController extends Controller
{
    protected function shop(): array
    {
        return Auth::sellerProfile(); // guaranteed approved by the "seller" middleware
    }

    protected function shopId(): int
    {
        return (int) $this->shop()['id'];
    }
}
