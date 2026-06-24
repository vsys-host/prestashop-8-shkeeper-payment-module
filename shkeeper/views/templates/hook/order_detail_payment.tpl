{*
 * Customer order-detail panel: lets a customer who closed the checkout page
 * reopen their order and see the address, amount and how much is still owed.
 *}
<div class="card mt-3" id="shkeeper-payment-info">
    <div class="card-header">
        <h3 class="h6 mb-0">{$shk_coin_name|escape:'htmlall':'UTF-8'} payment</h3>
    </div>
    <div class="card-body p-1">
        <p>
            Send the required amount to the address below.
            <span class="badge bg-{$shk_status_class|escape:'htmlall':'UTF-8'} text-white">{$shk_status_label|escape:'htmlall':'UTF-8'}</span>
        </p>
        {if $shk_wallet}
            <div class="form-group mb-2">
                <label><strong>Wallet address</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="shkeeper-customer-address" value="{$shk_wallet|escape:'htmlall':'UTF-8'}" readonly />
                    <button type="button" class="btn btn-secondary shk-copy" data-target="shkeeper-customer-address">Copy</button>
                </div>
            </div>
        {/if}
        <table class="table table-sm">
            <tbody>
                {if $shk_required_crypto !== null}
                    <tr>
                        <th>Amount to pay</th>
                        <td>{$shk_required_crypto|escape:'htmlall':'UTF-8'} {$shk_coin|escape:'htmlall':'UTF-8'} ({$shk_required_fiat nofilter})</td>
                    </tr>
                {/if}
                <tr>
                    <th>Received so far</th>
                    <td>{$shk_received_crypto|escape:'htmlall':'UTF-8'} {$shk_coin|escape:'htmlall':'UTF-8'} ({$shk_received_fiat nofilter})</td>
                </tr>
                {if $shk_shortfall_crypto !== null && $shk_shortfall_crypto > 0}
                    <tr class="text-danger">
                        <th>Remaining to pay</th>
                        <td>{$shk_shortfall_crypto|escape:'htmlall':'UTF-8'} {$shk_coin|escape:'htmlall':'UTF-8'} ({$shk_shortfall_fiat nofilter})</td>
                    </tr>
                {/if}
                {if $shk_overpaid_fiat}
                    <tr class="text-info">
                        <th>Overpaid</th>
                        <td>{$shk_overpaid_fiat nofilter}</td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>
