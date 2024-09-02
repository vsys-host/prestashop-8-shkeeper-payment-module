<div class="row">
    {if $status}
        <div class="col-sm-12">
            <div class="payment-container">
                {if $instructions}
                    <p>{$instructions|escape:'htmlall':'UTF-8'}</p>
                {/if}

                <div class="crypto-icons">
                    <select id="shkeeper-currency" name="shkeeper-currency">
                    {foreach $currencies as $currency}
                        <option value="{$currency.name|escape:'htmlall':'UTF-8'}">{$currency.display_name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                    </select>
                </div>
                <input type="button" value="{$entry_request_address|escape:'htmlall':'UTF-8'}" id="get-address" class="btn btn-danger" style="margin-top: 2vh;" />
            </div>
        </div>

        <div class="col-sm-12 pull-right" style="margin-top: 3vh;">
            <div class="alert alert-danger error-container" role="alert" hidden>
                Can't get cryptocurrency address. Choose another payment method.
            </div>
            <div class="pay-container" hidden>
                <strong>{$entry_address|escape:'htmlall':'UTF-8'}:</strong>
                <p id="wallet-address"></p>
            </div>
            <div class="pay-container" hidden>
                <strong>{$entry_amount|escape:'htmlall':'UTF-8'}:</strong>
                <p id="amount"></p>
            </div>
            <div id="qrcode"></div>
        </div>
        <div class="col-sm-12" hidden>
            <div id="post-url" hidden>{$wallet_controller|escape:'htmlall':'UTF-8'}</div>
            <input type="text" id="wallet_address" name="wallet_address" value="" hidden/>
            <input type="text" id="wallet_amount" name="wallet_amount" value="" hidden/>
        </div>
    </div>
{else}
    <div class="col-sm-12">
        <div class="alert alert-danger" role="alert">
            Can't get available cryptocurrencies for pay
        </div>
    </div>
{/if}
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>