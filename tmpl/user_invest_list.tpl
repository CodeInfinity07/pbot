{include file="mheader.tpl"}
        {if $alert}
<div class="{$alert_class}">
    <span>{$alert_message}</span>
</div>
{/if}


    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        {foreach from=$rows item=value}
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">{$value.name}</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="card-text"><strong>Amount:</strong> {$value.amount|fiat:$value.cid}</p>
                        <p class="card-text"><strong>Active Date:</strong> {$value.datetime}</p>
                        <p class="card-text"><strong>Expiry:</strong> {$value.expiry}</p>
                        <p class="card-text">
                            <strong>Status:</strong> 
                            {if $value.status == '1'}
                                <span class="badge bg-success">Active</span>
                            {elseif $value.status == '0'}
                                <span class="badge bg-danger">Expired</span>
                            {/if}
                        </p>
                        <div class="mt-auto">
                            {if $value.allowprincipal == '1'}
                                <a href="release_investment?id={$value.id}" class="btn btn-sm btn-outline-primary">Release Capital</a>
                            {/if}
                            {if $value.reinvest == '1'}
                                {if $value.auto_reinvest == '1'}
                                    <a href="invested?reinvest={$value.id}" class="btn btn-sm btn-outline-danger">Disable Reinvest</a>
                                {else}
                                    <a href="invested?reinvest={$value.id}" class="btn btn-sm btn-outline-success">Enable Reinvest</a>
                                {/if}
                            {/if}
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="modal" data-bs-target="#modal{$value.id}">
                            View Details
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal for each investment -->
            <div class="modal fade" id="modal{$value.id}" tabindex="-1" aria-labelledby="modalLabel{$value.id}" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalLabel{$value.id}">{$value.name} Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Transaction:</strong> {$value.txn_id}</p>
                                    <p><strong>Accurals:</strong> {$value.avail} / {$value.duration}</p>
                                    <p><strong>Remaining Accurals:</strong> {$value.duration - $value.avail}</p>
                                    <p><strong>Next Earning:</strong> {$value.next_earning}</p>
                                    <p><strong>Next Earning Time:</strong> {$value.next_earning_time}</p>
                                    <p><strong>Last Earning Time:</strong> {$value.last_earning_time}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Profit:</strong> {$value.profit|fiat} - {$value.total_profit|fiat}</p>
                                    <p><strong>Profit Percentage:</strong> {$value.percentage}%</p>
                                    <p><strong>Total Profit Percentage:</strong> {$value.total_percentage}%</p>
                                    <p><strong>Total Profit Earned:</strong> {$value.earned}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p><strong>Profit %</strong></p>
                                <div class="progress mb-3" style="height: 20px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {$value.percentage * $value.avail}%;" aria-valuenow="{$value.percentage * $value.avail}" aria-valuemin="0" aria-valuemax="{$value.total_percentage}">{$value.percentage * $value.avail}%</div>
                                </div>
                                <p><strong>Profit $</strong></p>
                                <div class="progress mb-3" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {$value.profit * $value.avail}%;" aria-valuenow="{$value.profit * $value.avail}" aria-valuemin="0" aria-valuemax="{$value.total_profit}">${$value.profit * $value.avail}</div>
                                </div>
                                <p><strong>Profit Accurals</strong></p>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: {$value.avail / $value.duration * 100}%" aria-valuenow="{$value.avail}" aria-valuemin="0" aria-valuemax="{$value.duration}">{$value.avail} / {$value.duration}</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        {foreachelse}
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No transactions found
                </div>
            </div>
        {/foreach}
    </div>


                {$paginator}
           
{include file="mfooter.tpl"}
