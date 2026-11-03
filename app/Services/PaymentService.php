<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * SIMULATED online payment for demonstration purposes.
 *
 * No payment gateway is contacted and no money moves. The customer chooses
 * "success" or "failure" on the demo payment page; a payment record is stored
 * either way so both paths can be tested. No card or bank details are ever
 * collected.
 */
final class PaymentService
{
    /** Unpaid, still-open seller orders of an online order (what the customer still owes). */
    public static function payable(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT * FROM seller_orders WHERE order_id = ? AND status = 'pending' AND payment_status = 'unpaid'",
            [$orderId]
        );
    }

    /**
     * @return bool true when the simulated payment succeeded
     * @throws CheckoutException
     */
    public static function simulate(int $orderId, int $customerId, bool $succeed): bool
    {
        return Database::transaction(function () use ($orderId, $customerId, $succeed): bool {
            // Lock the order so a double-submitted payment cannot be processed twice.
            $order = Database::fetch('SELECT * FROM orders WHERE id = ?' . Database::forUpdate(), [$orderId]);
            if (!$order || (int) $order['customer_id'] !== $customerId) {
                throw new CheckoutException('Pesanan tidak dijumpai.');
            }
            if ($order['payment_method'] !== 'online_sim') {
                throw new CheckoutException('Pesanan ini dibayar secara tunai semasa pengambilan.');
            }
            $payable = Database::fetchAll(
                "SELECT * FROM seller_orders WHERE order_id = ? AND status = 'pending' AND payment_status = 'unpaid'" . Database::forUpdate(),
                [$orderId]
            );
            if (!$payable) {
                throw new CheckoutException('Tiada bayaran tertunggak untuk pesanan ini.');
            }

            $amountSen = 0;
            foreach ($payable as $so) {
                $amountSen += to_sen($so['subtotal']);
            }
            $timestamp = now();
            Database::insert('payments', [
                'order_id'        => $orderId,
                'seller_order_id' => null,
                'method'          => 'online_sim',
                'amount'          => sen_to_decimal($amountSen),
                'status'          => $succeed ? 'success' : 'failed',
                'reference'       => 'SIM-' . strtoupper(bin2hex(random_bytes(6))),
                'note'            => 'Transaksi simulasi untuk demonstrasi. Tiada wang sebenar dipindahkan.',
                'created_by'      => $customerId,
                'created_at'      => $timestamp,
            ]);

            if (!$succeed) {
                return false;
            }

            foreach ($payable as $so) {
                Database::update('seller_orders', ['payment_status' => 'paid', 'updated_at' => $timestamp], 'id = :id', ['id' => (int) $so['id']]);
                $shopUserId = (int) Database::value('SELECT user_id FROM seller_profiles WHERE id = ?', [(int) $so['seller_id']]);
                Notifier::send($shopUserId, 'payment_received', 'Bayaran (simulasi) diterima',
                    "Pesanan {$so['reference']} telah dibayar. Anda boleh mengesahkan pesanan.", '/seller/orders/' . $so['id']);
            }
            return true;
        });
    }
}
