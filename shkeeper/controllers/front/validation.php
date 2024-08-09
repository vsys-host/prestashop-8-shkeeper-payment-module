<?php
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

        // save address with amount required for this order

        $walletAddress = $this->context->cookie->__get("shkeeper_wallet");
        $amount = $this->context->cookie->__get("shkeeper_amount");

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

        // messages
        $message = "Wallet: " . $this->context->cookie->__get("shkeeper_wallet") . " - ";
        $message .= "Amount: " . $this->context->cookie->__get("shkeeper_amount") . " ";
        $message .= $this->context->cookie->__get("shkeeper_crypto");

        // save address and amout to order messages
        $this->addOrderMessage($orderId, $message, $cart->id_customer);

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

    private function addOrderMessage($orderId, $message, $cutomerId)
    {
        if (version_compare(_PS_VERSION_, "1.7.0", ">")) {
            // Add this message in the customer thread
            $customer_thread = new CustomerThread();
            $customer_thread->id_contact = 0;
            $customer_thread->id_customer = (int) $cutomerId;
            $customer_thread->id_shop = (int) $this->context->shop->id;
            $customer_thread->id_order = (int) $orderId;
            $customer_thread->id_lang = (int) $this->context->language->id;
            $customer_thread->token = Tools::passwdGen(12);
            $customer_thread->add();

            $customer_message = new CustomerMessage();
            $customer_message->id_customer_thread = $customer_thread->id;
            $customer_message->id_employee = 0;
            $customer_message->message = $message;
            $customer_message->private = 1;
            $customer_message->add();
        } else {
            $orderMessage = new Message();
            $orderMessage->id_order = $orderId;
            $orderMessage->message = $message;
            $orderMessage->private = true;
            $orderMessage->save();
        }
    }
}
