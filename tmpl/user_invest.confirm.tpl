{include file="mheader.tpl"}

<section class="bg-light py-5" id="result">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Investment Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h5 class="text-muted mb-3">Plan Information</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Plan:</span>
                                        <strong>{$plan_name}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Principal Return:</span>
                                        <strong>{$principal}{if $principal_hold > 0}, With {$principal_hold}% fee {/if}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Credit Amount:</span>
                                        <strong>{$credit|fiat}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Currency:</span>
                                        <strong>{$payment_method}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Profit:</span>
                                        <strong>{$profit}{if $period > 1}s{/if} {if $etype == 1}(Mon-Fri){/if}</strong>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h5 class="text-muted mb-3">Additional Details</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <span class="fw-bold">Principal Withdraw:</span><br>
                                        {if $allowprincipal}
                                            Available with<br>
                                            {foreach from=$details.depositduration item=t key=key} 
                                                {if $t} 
                                                    {$details.withdrawalfee.$key}% fee after {$t} days<br>
                                                {/if} 
                                            {/foreach} 
                                        {else}
                                            Not available
                                        {/if}
                                    </li>
                                    {if $compound}
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Compound:</span>
                                        <strong>{$deposit.compound|number_format}%</strong>
                                    </li>
                                    {/if}
                                    {if $fee}
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Deposit Fee:</span>
                                        <strong>{$fee|fiat}</strong>
                                    </li>
                                    {/if}
                                    {if $auto_reinvest}
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Auto Re-invest:</span>
                                        <strong>Yes</strong>
                                    </li>
                                    {/if}
                                    {if $tag}
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Tag:</span>
                                        <strong>{$tag}</strong>
                                    </li>
                                    {/if}
                                </ul>
                            </div>
                        </div>

                        {if $address}
                        <div class="row mt-4">
                            <div class="col-md-6 text-center mb-4">
                                <h5 class="text-muted mb-3">QR Code</h5>
                                <img class="img-fluid mb-3" alt="qrcode" src="{$img}" style="max-width: 200px;">
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-muted mb-3">Payment Information</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Amount in {$symbol}:</span>
                                        <strong>{$amount}</strong>
                                    </li>
                                    <li class="list-group-item">
                                        <span>Address for {$payment_method}:</span><br>
                                        <small class="text-break">{$address}</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        {/if}

                        {if $form}
                        <div class="mt-4">
                            <h5 class="text-muted mb-3">Additional Actions</h5>
                            {$form}
                        </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{include file="mfooter.tpl"}

{if $address}
<script>
var myVar = setInterval(alertFunc, 10000);

function alertFunc() {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {
    if(this.responseText == '0') {
      console.log("Waiting for payment")
    } else if(this.responseText == '1') {
      window.location.replace('/invested');
    }
  }
  xhttp.open("GET", "index.php?address={$address}");
  xhttp.send();
}
</script>        
{/if}