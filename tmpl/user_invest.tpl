{include file="mheader.tpl"}

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {if $alert}
            <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
                {$alert_message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            {/if}

            <form method="post" name="spendform" action="invest">
                <input type="hidden" name="action" value="invest_request">
                
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Make an Investment</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="text-primary mb-3">Select Investment Plan</h5>
                            <div class="row">
                                {foreach from=$index_plans item=p}
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 border-primary">
                                        <div class="card-header bg-light">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="package_id" data-min="{$p.plans[0].min_deposit}" value="{$p.id}" id="{$p.id}" {if ($p.a == 1)} checked {/if}>
                                                <label class="form-check-label h5" for="{$p.id}">{$p.name|escape:html}</label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Plan</th>
                                                            <th>Amount Range({$currency_sign})</th>
                                                            <th>Profit (%)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {foreach from=$p.plans item=o}
                                                        <tr>
                                                            <td>{$o.name|escape:html}</td>
                                                            <td><span class="min_deposit">{$o.minimum_deposit}</span> - <span class="max_deposit">{if $o.maximum_deposit == '0'}&infin;{else}{$o.maximum_deposit}{/if}</span></td>
                                                            <td>{$o.percent}</td>
                                                        </tr>
                                                        {/foreach}
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p class="mb-0"><small>{if $p.etype == 0}{$o.percent}{elseif $p.etype == 1 || $p.etype == 2}{$o.percent_min}~{$o.percent_max}{/if} {$p.frequency}{if $p.period > 1}s{/if}</small></p>
                                            
                                            {if $p.reinvest}
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="auto_reinvest" value="1" id="autoReinvest{$p.id}">
                                                <label class="form-check-label" for="autoReinvest{$p.id}">Automatically ReInvest Funds</label>
                                            </div>
                                            {/if}

                                            {if $p.compound}
                                            <div class="mt-2">
                                                <label class="form-label">Compound</label>
                                                <input type="text" name="compound" class="form-control form-control-sm" placeholder="Compound amount">
                                                <small class="text-muted">Min: {$p.compound.compound_min|fiat} - Max: {$p.compound.compound_max|fiat}</small>
                                            </div>
                                            {/if}

                                            {if $p.cashback_bonus_amount || $p.cashback_bonus_percentage}
                                            <div class="mt-2 text-success">
                                                {if $p.cashback_bonus_amount}<p class="mb-0"><i class="fas fa-gift"></i> Get {$p.cashback_bonus_amount|fiat} Cashback Bonus</p>{/if}
                                                {if $p.cashback_bonus_percentage}<p class="mb-0"><i class="fas fa-percentage"></i> Get {$p.cashback_bonus_percentage}% Cashback Bonus</p>{/if}
                                            </div>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                                {/foreach}
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="text-primary mb-3">Payment Method</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Processing</th>
                                            <th>Topup</th>
                                            <th>Balance</th>
                                            <th>Faucet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {section name=p loop=$ps}
                                        <tr>
                                            <td><img src="images/icons/{$ps[p].id}.svg" width="24" height="24" class="me-2"> {$ps[p].name}</td>
                                            <td><input type="radio" name="payment_method_id" value="{$ps[p].id}" {if ($ps[p].id == 1)} checked {/if}></td>
                                            <td>{if $ps[p].balance > 0}<input type="radio" name="payment_method_id" value="account_{$ps[p].id}" {if ($ps[p].id == 1)} checked {/if}> {/if} {$ps[p].balance|fiat}</td>
                                            {if $ps[p].faucet > 0}
                                            <td><input type="radio" name="payment_method_id" value="faucet_{$ps[p].id}" {if ($ps[p].id == 1)} checked {/if}> {$ps[p].faucet|fiat}</td>
                                            {else}
                                            <td>-</td>
                                            {/if}
                                        </tr>
                                        {/section}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Amount to Spend</label>
                            <div class="input-group">
                                <span class="input-group-text">{$currency_sign}</span>
                                <input type="number" step="0.00000001" class="form-control" required name="amount" placeholder="Enter amount">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="submit" name="submit" value="submit" class="btn btn-primary btn-lg">Make Investment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{include file="mfooter.tpl"}

<script type="text/javascript">
$(document).ready(function() {
    $("input[type=radio][name=package_id]").click(function() {
        var minDeposit = $(this).data('min');
        $('input[name=amount]').val(minDeposit);
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>