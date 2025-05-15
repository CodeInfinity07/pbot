{include file="mheader.tpl"}

<div class="container py-4">
    <h1 class="mb-4">Transactions</h1>

    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    <form action="transactions" method="get" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label for="type" class="form-label">Transaction Type</label>
                <select id="type" name="type" class="form-select" onchange="if (this.value) window.location.href=this.value">
                    <option value="transactions">All transactions</option>
                    {foreach from=$options item=label key=key}
                    <option value="{$label.link}" {if $key == $type}selected{/if}>{$label.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="cid" class="form-label">Currency</label>
                <select id="cid" name="cid" class="form-select">
                    <option value="-1">All eCurrencies</option>
                    {section name=p loop=$ps}
                    <option value="{$ps[p].id}">{$ps[p].name}</option>
                    {/section}
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="from" class="form-label">From Date</label>
                <input type="date" id="from" name="from" value="{$startdate}" class="form-control" />
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="to" class="form-label">To Date</label>
                <input type="date" id="to" name="to" value="{$enddate}" class="form-control" />
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Transaction History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$rows key=key item=value}
                        <tr>
                            <td>{$value.type}</td>
                            <td>{$value.amount|fiat} ~ ${$value.amount}</td>
                            <td>{$value.datetime}</td>
                            <td>
                                {if ($settings.withdraw.delay_instant_withdraw || $settings.withdraw.delay_auto_withdraw) && $value.status == '0'}
                                <span class="badge bg-warning" id="time{$value.id}"></span>
                                <script>
                                    var countDown{$value.id} = new Date("{$value.delay_time}").getTime();
                                    var time{$value.id}a = setInterval(function() {
                                        var now = new Date().getTime();
                                        var distance = countDown{$value.id} - now;
                                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                        document.getElementById("time{$value.id}").innerHTML = minutes + "m " + seconds + "s";
                                        if (distance < 0) {
                                            clearInterval(time{$value.id}a);
                                            document.getElementById("time{$value.id}").innerHTML = "Processing";
                                        }
                                    }, 1000);
                                </script>
                                {elseif $value.status == '0'}
                                <span class="badge bg-primary">Pending</span>
                                {/if}
                                {if $value.status == '1'}<span class="badge bg-success">Completed</span>{/if}
                                {if $value.status == '2'}<span class="badge bg-secondary">Cancelled</span>{/if}
                                {if $settings.withdraw.cancel_withdraw && $value.status == '0'}
                                <a class="badge bg-danger text-decoration-none" href="{$settings.link.cancel_withdraw|default:'cancel_withdraw'}?id={$value.id}">Cancel</a>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-muted">{$value.detail}</td>
                        </tr>
                        {foreachelse}
                        <tr>
                            <td colspan="4" class="text-center">No transactions found</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {$paginator}
    </div>
</div>

{include file="mfooter.tpl"}