<?php
// disable loading outside prestashop
if (!defined("_PS_VERSION_")) {
    exit();
}

class ShkeeperValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        if (
            $cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 ||
            !$this->module->active
        ) {
            Tools::redirect("index.php?controller=order&step=1");

            return;
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module["name"] == "shkeeper") {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            exit(
                $this->trans(
                    "This payment method is not available.",
                    [],
                    "Modules.Shkeeper.Shop"
                )
            );
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect("index.php?controller=order&step=1");

            return;
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder(
            $cart->id,
            (int) Configuration::get("PS_OS_SHKEEPER_PENDING"),
            0,
            $this->module->displayName,
            null,
            [],
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        // OrderId
        $orderId = $this->module->currentOrder;

        // Persist the crypto payment details on the order itself 
        // so staff — and the customer — can still see the coin, wallet address and
        // required amount even if the checkout page was closed.
        Shkeeper::writeOrderMeta($orderId, "Info", [
            "Coin"     => (string) $this->context->cookie->__get("shkeeper_crypto_code"),
            "Name"     => (string) $this->context->cookie->__get("shkeeper_crypto"),
            "Required" => (string) $this->context->cookie->__get("shkeeper_amount"),
            "Fiat"     => $total . ' ' . $currency->iso_code,
            "Address"  => (string) $this->context->cookie->__get("shkeeper_wallet"),
        ]);

        Tools::redirect(
            $this->context->link->getPageLink(
                "order-confirmation",
                true,
                (int) $this->context->language->id,
                [
                    "id_cart" => (int) $cart->id,
                    "id_module" => (int) $this->module->id,
                    "id_order" => (int) $this->module->currentOrder,
                    "key" => $customer->secure_key,
                ]
            )
        );
    }

}
