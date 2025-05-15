{include file="mheader.tpl"}
{if $alert}
<div class="{$alert_class}">
    {$alert_message}
</div>
{/if}
{foreach from=$rows key=key item=value}
<div class="card" id="pagelist_container">
    <div class="card-header">
        Withdraw
    </div>
    <div class="card-body">
        <table>
            <tr>
                <th>Amount</th>
                <td>{$value.amount|fiat:$value.cid}</td>
            </tr>
            <tr>
                <th>Currency</th>
                <td>{$value.currency}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td>{$value.address}</td>
            </tr>
            {if $value.txn_id}
            <tr>
                <th>Amount</th>
                <td>{$value.txn_id}</td>
            </tr>
            {/if}
            {if $value.txn_url}
            <tr>
                <th>Amount</th>
                <td>{$value.txn_url}</td>
            </tr>
            {/if}
            <tr>
                <th>Status</th>
                <td>{if $value.status == '0'}<span class="badge bg-warning"> Pending </span>{elseif $value.status == '1'}<span class="badge bg-success"> Completed </span>{/if} {$value.delay_time} </td>
            </tr>
            {if $settings.withdraw.delay_instant_withdraw}
            <tr>
                <th>Time to Process</th>
                <td> <span class="badge bg-warning" id="time{$value.id}"></span> </td>
            </tr>
            <script>
                            function addMinutes(date, minutes) {
                                return new Date(date.getTime() + minutes*60000);
                            }
                            //var countDown{$value.id} = {$value.delay_timer}*1000;
                            var d = addMinutes(new Date("{$value.delay_time}"), 2);
                            var countDown{$value.id} = d.getTime();
                            var time{$value.id}a = setInterval(function() {
                              var now = new Date().getTime();
                              var distance = countDown{$value.id} - now;
                              var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                              var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                              document.getElementById("time{$value.id}").innerHTML = minutes + " minutes " + seconds + " seconds ";
                              console.log(distance);
                              if (distance < 0) {
                                clearInterval(time{$value.id}a);
                                document.getElementById("time{$value.id}").innerHTML = "Processing";
                              }
                            }, 1000);
                          </script>
            {/if}
            
        </table>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-end">
            <button>Button</button>
           {if $settings.withdraw.cancel_withdraw && $value.status == '0'}  <a class="btn btn-warning" href="{$settings.link.cancel_withdraw|default:'cancel_withdraw'}?id={$value.id}">Cancel</a> {/if}
        </div>
    </div>
</div>
{/foreach}


{include file="mfooter.tpl"}

