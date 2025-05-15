{include file="mheader.tpl"}

<section class="bg-light py-5" id="result">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Payment Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-muted mb-3">Transaction Summary</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Credit Amount:</span>
                                        <strong>{$credit}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Currency:</span>
                                        <strong>{$payment_method}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Amount:</span>
                                        <strong>{$symbol} {$amount}</strong>
                                    </li>
                                    {if $tag}
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Tag:</span>
                                        <strong>{$tag}</strong>
                                    </li>
                                    {/if}
                                </ul>
                            </div>
                            {if $address}
                            <div class="col-md-6 text-center">
                                <h5 class="text-muted mb-3">QR Code</h5>
                                <img class="img-fluid mb-3" alt="qrcode" src="{$img}" style="max-width: 200px;">
                                <p class="mb-1">Amount: <strong>{$amount} {$symbol}</strong></p>
                                <p class="mb-0">Address for {$payment_method}:</p>
                                <p class="text-break"><small>{$address}</small></p>
                            </div>
                            {/if}
                        </div>

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
      window.location.replace('/deposits');
    }
  }
  xhttp.open("GET", "index.php?topup={$address}");
  xhttp.send();
}
</script>        
{/if}