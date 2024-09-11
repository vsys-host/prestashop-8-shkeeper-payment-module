<?php
/**
 * @author vsys-host <support@shkeeper.io>
 */

// disable loading outside prestashop
if (!defined("_PS_VERSION_")) {
    exit();
}

class Shkeeper extends PaymentModule
{
    public bool $is_configurable;

    public function __construct()
    {
        $this->name = "shkeeper";
        $this->tab = "payments_gateways";
        $this->version = "1.0.1";
        $this->author = "vsys-host";
        $this->author_uri = "https://shkeeper.io";
        $this->need_instance = 0;
        $this->is_configurable = 1;
        $this->module_key = '39d6992dd2c0a5ed263be1e98e70a898';
        $this->ps_versions_compliancy = [
            "min" => "1.7",
            "max" => _PS_VERSION_,
        ];

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans(
            "SHKeeper",
            [],
            "Modules.Shkeeper.Admin"
        );
        $this->description = $this->trans(
            "SHKeeper Cryptocurrencies Payment Gateway",
            [],
            "Modules.Shkeeper.Admin"
        );

        $this->confirmUninstall = $this->trans(
            "Are you sure you want to uninstall?",
            [],
            "Modules.Shkeeper.Admin"
        );
    }

    public function install()
    {
        // Enable module in case multiple stores enabled
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        // register payment hooks
        return parent::install() &&
            $this->registerHook("PaymentOptions") &&
            $this->registerHook("displayHeader") &&
            // && $this->registerHook('PaymentReturn')
            Configuration::updateValue("SHKEEPER", "shkeeper") &&
            $this->addOrderState("shkeeper");
    }

    public function uninstall()
    {
        // clear configuration store
        Configuration::deleteByName("SHKEEPER");
        Configuration::deleteByName("SHKEEPER_INSTRUCTION");
        Configuration::deleteByName("SHKEEPER_APIKEY");
        Configuration::deleteByName("SHKEEPER_APIURL");

        return parent::uninstall();
    }

    /**
     * Handles module configuration page
     * @return string
     */
    public function getContent()
    {
        $output = "";

        if (Tools::isSubmit("submit" . $this->name)) {
            $configInstruction = (string) Tools::getValue(
                "SHKEEPER_INSTRUCTION"
            );
            $configKey = Tools::getValue("SHKEEPER_APIKEY");
            $configURL = Tools::getValue("SHKEEPER_APIURL");

            // auto validate the URL
            $configURL = $this->addURLSchema($configURL);
            //$configURL = $this->addURLSeparator($configURL);

            // check that the value is valid
            if (empty($configKey) || empty($configURL)) {
                // invalid value, show an error
                $output = $this->displayError(
                    $this->trans(
                        "Invalid Configuration value",
                        [],
                        "Modules.Shkeeper.Admin"
                    )
                );
            } else {
                // value is ok, update it and display a confirmation message
                Configuration::updateValue(
                    "SHKEEPER_INSTRUCTION",
                    $configInstruction
                );
                Configuration::updateValue("SHKEEPER_APIKEY", $configKey);
                Configuration::updateValue("SHKEEPER_APIURL", $configURL);
                $output = $this->displayConfirmation(
                    $this->trans(
                        "Settings updated",
                        [],
                        "Modules.Shkeeper.Admin"
                    )
                );
            }
        }

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $form = [
            "form" => [
                "legend" => [
                    "title" => $this->trans("Settings"),
                    "icon" => "icon-cogs",
                ],
                "input" => [
                    [
                        "type" => "textarea",
                        "label" => $this->trans(
                            "Instruction",
                            [],
                            "Modules.Shkeeper.Admin"
                        ),
                        "name" => "SHKEEPER_INSTRUCTION",
                        "desc" => "Instruction for Customer",
                        "required" => false,
                    ],
                    [
                        "type" => "text",
                        "label" => $this->trans(
                            "API Key",
                            [],
                            "Modules.Shkeeper.Admin"
                        ),
                        "name" => "SHKEEPER_APIKEY",
                        "desc" => "API Key",
                        "required" => true,
                    ],
                    [
                        "type" => "text",
                        "label" => $this->trans(
                            "API URL",
                            [],
                            "Modules.Shkeeper.Admin"
                        ),
                        "name" => "SHKEEPER_APIURL",
                        "desc" => "API URL",
                        "required" => true,
                    ],
                ],
                "submit" => [
                    "title" => $this->trans(
                        "Save",
                        [],
                        "Modules.Shkeeper.Admin"
                    ),
                    "class" => "btn btn-default pull-right",
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite("AdminModules");
        $helper->currentIndex =
            AdminController::$currentIndex .
            "&" .
            http_build_query(["configure" => $this->name]);
        $helper->submit_action = "submit" . $this->name;

        // Default language
        $helper->default_form_language = (int) Configuration::get(
            "PS_LANG_DEFAULT"
        );

        // Load current value into the form
        $helper->fields_value["SHKEEPER_INSTRUCTION"] = Tools::getValue(
            "SHKEEPER_INSTRUCTION",
            Configuration::get("SHKEEPER_INSTRUCTION")
        );
        $helper->fields_value["SHKEEPER_APIKEY"] = Tools::getValue(
            "SHKEEPER_APIKEY",
            Configuration::get("SHKEEPER_APIKEY")
        );
        $helper->fields_value["SHKEEPER_APIURL"] = Tools::getValue(
            "SHKEEPER_APIURL",
            Configuration::get("SHKEEPER_APIURL")
        );

        return $helper->generateForm([$form]);
    }

    /**
     * Enable translator at backoffice
     * @return bool
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerJavascript(
            "shkeeper-js",
            "modules/" . $this->name . "/views/js/shkeeper.js",
            [
                "position" => "bottom",
            ]
        );
    }

    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        if (
            !Configuration::get("SHKEEPER_APIKEY") ||
            !Configuration::get("SHKEEPER_APIURL")
        ) {
            return;
        }

        // get currencies
        $this->smarty->assign($this->getAvailableCurrencies());

        $paymetnOptions = [$this->getShkeeperOptions()];

        return $paymetnOptions;
    }

    public function getShkeeperOptions()
    {
        $shkeeper = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $shkeeper->setModuleName($this->name);
        $shkeeper->setCallToActionText(
            $this->trans(
                "Pay with SHKeeper",
                [],
                "Module.Shkeeper.Shop"
            )
        );

        $shkeeper->setAction(
            $this->context->link->getModuleLink(
                $this->name,
                "validation",
                [],
                true
            )
        );
        $shkeeper->setAdditionalInformation(
            $this->fetch(
                "module:shkeeper/views/templates/front/payment_info.tpl"
            )
        );

        return $shkeeper;
    }

    private function getAvailableCurrencies()
    {
        $instructions = Configuration::get("SHKEEPER_INSTRUCTION");
        $currencies = $this->getData("/crypto");

        return [
            "instructions" => $instructions,
            "status" => $currencies["status"],
            "currencies" => $currencies["crypto_list"],
            "wallet_controller" => Context::getContext()->link->getModuleLink(
                "shkeeper",
                "wallet",
                ["ajax" => true]
            ),
            "entry_request_address" => $this->trans(
                "Get address",
                [],
                "Module.Shkeeper.Shop"
            ),
            "entry_address" => $this->trans(
                "Wallet Address",
                [],
                "Module.Shkeeper.Shop"
            ),
            "entry_amount" => $this->trans(
                "Amount",
                [],
                "Module.Shkeeper.Shop"
            ),
        ];
    }

    /**
     * Validate adding separator directory at the end of the link
     * @param string $url
     * @return string
     */
    private function addURLSeparator(string $url): string
    {
        if (!str_ends_with($url, "/")) {
            return $url .= DIRECTORY_SEPARATOR;
        }

        return $url;
    }

    /**
     * Validate adding schema at the start of the link
     * @param string $url
     * @return string
     */
    private function addURLSchema(string $url): string
    {
        if (!str_contains($url, "http")) {
            return "https://" . $url;
        }

        return $url;
    }

    private function getData(string $url)
    {
        $headers = [
            "X-Shkeeper-Api-Key: " . Configuration::get("SHKEEPER_APIKEY"),
        ];

        $base_url = rtrim(Configuration::get("SHKEEPER_APIURL"), '/');

        $options = [
            CURLOPT_URL => $base_url . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    private function addOrderState(string $moduleName)
    {
        // If the state does not exist, we create it.
        if (!Configuration::get("PS_OS_SHKEEPER_PENDING")) {
            // create new order state
            $orderState = new OrderState();
            $orderState->color = "#52add7";
            $orderState->send_email = false;
            $orderState->module_name = $moduleName;
            $orderState->unremovable = true;
            $orderState->logable = false;
            $orderState->name = [];
            $languages = Language::getLanguages();

            foreach ($languages as $language) {
                $orderState->name[$language["id_lang"]] = $this->trans(
                    "SHKeeper Awaiting payment",
                    [],
                    "Modules.Shkeeper.Admin"
                );
            }

            // save new order state
            $orderState->add();

            Configuration::updateValue(
                "PS_OS_SHKEEPER_PENDING",
                (int) $orderState->id
            );
        }

        if (!Configuration::get("PS_OS_SHKEEPER_PARTIAL_PAYMENT")) {
            // create new order state
            $orderState = new OrderState();
            $orderState->color = "#F4BB44";
            $orderState->send_email = false;
            $orderState->module_name = $moduleName;
            $orderState->unremovable = true;
            $orderState->logable = true;
            $orderState->name = [];
            $languages = Language::getLanguages();

            foreach ($languages as $language) {
                $orderState->name[$language["id_lang"]] = $this->trans(
                    "SHKeeper Partial Payment Received",
                    [],
                    "Modules.Shkeeper.Admin"
                );
            }

            // save new order state
            $orderState->add();

            Configuration::updateValue(
                "PS_OS_SHKEEPER_PARTIAL_PAYMENT",
                (int) $orderState->id
            );
        }

        if (!Configuration::get("PS_OS_SHKEEPER_ACCEPTED")) {
            // create new order state
            $orderState = new OrderState();
            $orderState->color = "#88D66C";
            $orderState->send_email = false;
            $orderState->module_name = $moduleName;
            $orderState->unremovable = true;
            $orderState->logable = true;
            $orderState->name = [];
            $languages = Language::getLanguages();

            foreach ($languages as $language) {
                $orderState->name[$language["id_lang"]] = $this->trans(
                    "SHKeeper Accepted Payment",
                    [],
                    "Modules.Shkeeper.Admin"
                );
            }

            // save new order state
            $orderState->add();

            Configuration::updateValue(
                "PS_OS_SHKEEPER_ACCEPTED",
                (int) $orderState->id
            );
        }

        if (Configuration::get("PS_OS_SHKEEPER_PENDING") && Configuration::get("PS_OS_SHKEEPER_PARTIAL_PAYMENT") && Configuration::get("PS_OS_SHKEEPER_ACCEPTED")) {
            return true;
        }

        return false;
    }
}
