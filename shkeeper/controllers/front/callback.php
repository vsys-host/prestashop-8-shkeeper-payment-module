<?php
// disable loading outside prestashop
if (!defined("_PS_VERSION_")) {
    exit();
}

class ShkeeperCallbackModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {

        // collect data stream
        $data = file_get_contents('php://input');
        $headers = getallheaders();

        $message = null;

        // validate if the request singned by SHKeeper API
        if (! $this->isSignedRequest($headers)) {
            $message = 'Unauthorized Request!...';
            $this->response($message, 401);
        }

        $data_collected = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'JSON: ' . json_last_error_msg();
            $this->response($message, 400);
        }

        // ensure the expected payload structure is present
        if (
            !is_array($data_collected) ||
            !isset($data_collected['external_id']) ||
            !array_key_exists('paid', $data_collected) ||
            !isset($data_collected['transactions']) ||
            !is_array($data_collected['transactions'])
        ) {
            $this->response('Malformed payload', 400);
        }

        $externalId = (int) $data_collected['external_id'];

        // fetch order ID by external ID
        $orderId = Order::getIdByCartId($externalId);

        // terminate on Order Not Found
        if (! $orderId) {
            $transactionDate = (string) ($data_collected['transactions'][0]['date'] ?? '');

            // Stop receive confirmation for missing orders
            if ($transactionDate !== '' && $this->getInterval($transactionDate)) {
                $message = 'Please Contact Store Administration';
                $this->response($message, 202);
            }

            $message = 'Wrong Credentials!...';
            $this->response($message, 404);
        }

        $order = new Order($orderId);

        // transaction ids already recorded on this order (idempotency / replay dedup)
        $existingTxids = array_map(
            static function ($payment) {
                return (string) $payment->transaction_id;
            },
            OrderPayment::getByOrderReference($order->reference)
        );

        // collect new transactions and save data on order update
        foreach ($data_collected['transactions'] as $transaction) {
            $txid = isset($transaction['txid']) ? (string) $transaction['txid'] : '';

            // skip non-trigger, malformed, or already-recorded transactions
            if (empty($transaction['trigger']) || $txid === '' || in_array($txid, $existingTxids, true)) {
                continue;
            }

            // Record the payment through the order API only. Order::addOrderPayment()
            // creates and saves its own OrderPayment row internally
            $cryptoAmount = isset($transaction['amount_crypto']) ? ' ' . $transaction['amount_crypto'] : '';
            $paymentMethod = $this->module->name . ' ' . $transaction['crypto'] . $cryptoAmount;

            if ($order->addOrderPayment((float) $transaction['amount_fiat'], $paymentMethod, $txid)) {
                $existingTxids[] = $txid;
            }
        }
        
        $partialState  = (int) Configuration::get('PS_OS_SHKEEPER_PARTIAL_PAYMENT');
        $pendingState  = (int) Configuration::get('PS_OS_SHKEEPER_PENDING');
        $acceptedState = (int) Configuration::get('PS_OS_SHKEEPER_ACCEPTED');
        $currentState  = (int) $order->getCurrentState();

        if ($data_collected['paid']) {
            // fully paid 
            $newOrderStatus = $acceptedState ?: (int) Configuration::get('PS_OS_PAYMENT');
        } else {
            // not fully paid
            $newOrderStatus = $partialState ?: $pendingState;
        }

        if ($newOrderStatus && $currentState !== $acceptedState && $currentState !== $newOrderStatus) {
            $order->setCurrentState($newOrderStatus);
            $order->save();
        }

        // Persist the latest payment progress on the order. The
        // top-level balances are authoritative totals, regardless of which
        // individual transactions were recorded as OrderPayments.
        Shkeeper::writeOrderMeta($order->id, 'Status', [
            'Status'        => (string) ($data_collected['status'] ?? ''),
            'Coin'          => (string) ($data_collected['crypto'] ?? ''),
            'Address'       => (string) ($data_collected['addr'] ?? ''),
            'Received'      => (string) ($data_collected['balance_crypto'] ?? '0'),
            'Received fiat' => (string) ($data_collected['balance_fiat'] ?? '0'),
            'Overpaid'      => (string) ($data_collected['overpaid_fiat'] ?? '0'),
        ]);

        $this->response('Order status updated.', 202);

    }

    private function response(string $message, $responseCode = 200): void
    {
        header("Content-Type: application/json");
        http_response_code($responseCode);
        echo $message;
        exit;
    }

    private function isSignedRequest(array $header = []): bool
    {
        // terminate on empty headers
        if (empty($header)) {
            return false;
        }

        $header = array_change_key_case($header, CASE_LOWER);
        $providedKey = isset($header['x-shkeeper-api-key']) ? (string) $header['x-shkeeper-api-key'] : '';

        // terminate when the request carries no key
        if ($providedKey === '') {
            return false;
        }

        // fetch saved api key
        $shkeeperKey = (string) Configuration::get('SHKEEPER_APIKEY');

        // fail closed when no key is configured
        if ($shkeeperKey === '') {
            return false;
        }

        // constant-time, type-safe comparison (no `0e` type-juggling, no timing side-channel)
        return hash_equals($shkeeperKey, $providedKey);
    }

    /**
     * Calculate the Date interval
     * @param string $date
     * @return bool
     */
    private function getInterval(string $date): bool
    {
        $paymentDate = new DateTimeImmutable($date);
        $today = new DateTimeImmutable();
        return (bool) $paymentDate->diff($today)->format('%a');
    }
}