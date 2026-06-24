<?php
// disable loading outside prestashop
if (!defined("_PS_VERSION_")) {
    exit();
}

class ShkeeperWalletModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        $cryptoCurrency = (string) Tools::getValue('currency');

        if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $cryptoCurrency)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid currency']);
            exit;
        }

        $cart = $this->context->cart;
        $currency = $this->context->currency;

        $order_data = [
            "external_id"   => $cart->id,
            "fiat"          => $currency->iso_code,
            "amount"        => $cart->getOrderTotal(true, Cart::BOTH),
            "callback_url"  => $this->context->link->getModuleLink('shkeeper', 'callback', ['ajax' => true]),
        ];

        $walletAddress = $this->postData('/' . rawurlencode($cryptoCurrency) . '/payment_request', $order_data);
        $info = json_decode($walletAddress, true);

        if (!is_array($info) || empty($info['wallet'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unable to get wallet address']);
            exit;
        }

        $this->context->cookie->__set('shkeeper_wallet', $info['wallet']);
        $this->context->cookie->__set('shkeeper_amount', $info['amount']);
        $this->context->cookie->__set('shkeeper_crypto', $info['display_name']);
        $this->context->cookie->__set('shkeeper_crypto_code', $cryptoCurrency);

        header("Content-Type: application/json");
        echo $walletAddress;
        exit;
    }

    private function postData(string $url, array $data = [])
    {
        $headers = [
            "X-Shkeeper-Api-Key: " . Configuration::get('SHKEEPER_APIKEY'),
        ];

        $base_url = rtrim(Configuration::get('SHKEEPER_APIURL'), '/');

        $options = [
            CURLOPT_URL => $base_url . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}