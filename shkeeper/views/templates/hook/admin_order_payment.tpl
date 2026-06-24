{*
 * Back-office order page panel: crypto coin, wallet address and payment progress.
 * Data comes from the private order messages written at checkout and by the IPN
 * callback. Fiat amounts are pre-formatted in PHP.
 *}
<div class="card mt-2" id="shkeeper-payment-panel">
    <div class="card-header">
        <h3 class="card-header-title">SHKeeper crypto payment</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p>
                    <strong>Cryptocurrency:</strong>
                    {$shk_coin_name|escape:'htmlall':'UTF-8'}{if $shk_coin && $shk_coin != $shk_coin_name} ({$shk_coin|escape:'htmlall':'UTF-8'}){/if}
                    <span class="badge bg-{$shk_status_class|escape:'htmlall':'UTF-8'} text-white">{$shk_status_label|escape:'htmlall':'UTF-8'}</span>
                </p>
                {if $shk_wallet}
                    <div class="form-group mb-2">
                        <label><strong>Wallet address</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shkeeper-wallet-address" value="{$shk_wallet|escape:'htmlall':'UTF-8'}" readonly onclick="this.select();" />
                            <button type="button" class="btn btn-outline-secondary" onclick="var el=document.getElementById('shkeeper-wallet-address');el.select();try{ldelim}navigator.clipboard.writeText(el.value);{rdelim}catch(e){ldelim}document.execCommand('copy');{rdelim}">Copy</button>
                        </div>
                    </div>
                {/if}
            </div>
            <div class="col-md-6">
                <table class="table">
                    <thead>
                        <tr><th></th><th>Crypto</th><th>Fiat</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Required</td>
                            <td>{if $shk_required_crypto !== null}{$shk_required_crypto|escape:'htmlall':'UTF-8'} {$shk_coin|escape:'htmlall':'UTF-8'}{else}&mdash;{/if}</td>
                            <td>{$shk_required_fiat nofilter}</td>
                        </tr>
                        <tr>
                            <td>Received</td>
                            <td>{$shk_received_crypto|escape:'htmlall':'UTF-8'} {$shk_coin|escape:'htmlall':'UTF-8'}</td>
                            <td>{$shk_received_fiat nofilter}</td>
                        </tr>
                        {if $shk_shortfall_crypto !== null && $shk_shortfall_crypto > 0}
                            <tr class="text-danger">
                                <td><strong>Still required</strong></td>
                                <td>{$shk_shortfall_crypto|escape:'htmlall':'UTF-8'} {$shk_coin|escape:'htmlall':'UTF-8'}</td>
                                <td>{$shk_shortfall_fiat nofilter}</td>
                            </tr>
                        {/if}
                        {if $shk_overpaid_fiat}
                            <tr class="text-info">
                                <td>Overpaid</td>
                                <td></td>
                                <td>{$shk_overpaid_fiat nofilter}</td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
