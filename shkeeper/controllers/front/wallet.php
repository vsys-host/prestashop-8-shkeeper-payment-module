<?php
// disable loading outside prestashop
if (!defined("_PS_VERSION_")) {
    exit();
}

class ShkeeperWalletModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        $cryptoCurrency = Tools::getValue('currency');

        if (empty($cryptoCurrency)) {
            return false;
        }

        $cart = $this->context->cart;
        $currency = $this->context->currency;

        $order_data = [
            "external_id"   => $cart->id,
            "fiat"          => $currency->iso_code,
            "amount"        => $cart->getOrderTotal(true, Cart::BOTH),
            "callback_url"  => $this->context->link->getModuleLink('shkeeper', 'callback', ['ajax' => true]),
        ];

        $walletAddress = $this->postData("/$cryptoCurrency/payment_request", $order_data);
        $info = json_decode($walletAddress, true);
        $this->context->cookie->__set('shkeeper_wallet', $info['wallet']);
        $this->context->cookie->__set('shkeeper_amount', $info['amount']);
        $this->context->cookie->__set('shkeeper_crypto', $info['display_name']);

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
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}