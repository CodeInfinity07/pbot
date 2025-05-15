{include file="mheader.tpl"}

<div class="container py-5">
    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    {if $confirm}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Please Confirm Exchange</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="bg-light">From:</th>
                                <td>
                                    <img src="images/icons/{$from_id}.svg" alt="{$from_currency}" class="me-2" style="width: 24px;">
                                    {$from_currency} {$settings.symbol} {$from_amount}
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">To:</th>
                                <td>
                                    <img src="images/icons/{$to_id}.svg" alt="{$to_currency}" class="me-2" style="width: 24px;">
                                    {$to_currency} {$settings.symbol} {$to_amount}
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Commission:</th>
                                <td>{$rate}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                {include file="auth.tpl" action="exchange"}
                <div class="mt-3">
                    <button type="submit" name="submit" class="btn btn-success me-2">Confirm</button>
                    <button type="submit" class="btn btn-outline-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    {else}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Exchange Currency Rates</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                {section name=p loop=$exchange}
                                <tr>
                                    <td>
                                        <img src="images/icons/{$exchange[p].from_currency}.svg" alt="{$exchange[p].fcurrency}" class="me-2" style="width: 24px;">
                                        {$exchange[p].fcurrency}
                                    </td>
                                    <td>
                                        <img src="images/icons/{$exchange[p].to_currency}.svg" alt="{$exchange[p].tcurrency}" class="me-2" style="width: 24px;">
                                        {$exchange[p].tcurrency}
                                    </td>
                                    <td>{amount_format($exchange[p].rate, 2)}%</td>
                                </tr>
                                {/section}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Exchange Currency</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Select Currency you want to send</label>
                            <select name="from_currency" onchange="change_payment()" required class="form-select">
                                <option value="">Select From Currency</option>
                                {section name=p loop=$ps}
                                {if $ps[p].balance > 0 and $ps[p].status == 1}
                                <option value="{$ps[p].id}" data-balance="{$ps[p].balance}">
                                    {$ps[p].name} {$settings.symbol}{$ps[p].balance|amount_format}
                                </option>
                                {/if}
                                {/section}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount you want to send</label>
                            <input type="number" onkeyup="getrate()" step="0.00000001" min="0.00000001" class="form-control" required name="from_amount" placeholder="Enter amount">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Currency you want to receive</label>
                            <select name="to_currency" required class="form-select">
                                <option value="">Select To Currency</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount you will receive</label>
                            <input type="number" onkeyup="getrate('to')" step="0.00000001" min="0.00000001" class="form-control" name="to_amount" placeholder="Received amount" readonly>
                        </div>
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" name="submit" class="btn btn-primary">Exchange Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {/if}
</div>

{include file="mfooter.tpl"}

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script type="text/javascript">
function change_payment() {
    var f = $("select[name=from_currency]").val();
    if(f) {
        $.ajax({
            url: 'index.php',
            type: 'get',
            data: "check_exchange="+f,
            success: function(data) {
                if(data == '') {
                    $("select[name=to_currency]").html('<option value="">Select To Currency</option>');
                    alert('No data for this currency found');
                } else {
                    $("select[name=to_currency]").html(data);
                    getrate();
                }
            }
        });
    }
}

function getrate(type = 'from') {
    var rate = $("select[name=to_currency]").find(':selected').data('rate');
    var from_id = $("select[name=from_currency]").val();
    var to_id = $("select[name=to_currency]").val();
    var balance = $("select[name=from_currency]").find(':selected').data('balance');
    var from = $("input[name=from_amount]").val();
    var to = $("input[name=to_amount]").val();
    
    if(from_id && to_id) {
        if(type == 'from' && from != '' && from > 0) {
            var to_amount = from * ((100 - rate) / 100);
            $("input[name=to_amount]").val(to_amount.toFixed(8));
        } else if(type == 'to' && to != '' && to > 0) {
            var from_amount = (to / (100 - rate)) * 100;
            $("input[name=from_amount]").val(from_amount.toFixed(8));
        }
    }
}
</script>