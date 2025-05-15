{include file="mheader.tpl"}



    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>{$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    <div class="card shadow-sm">
        {if $confirm}
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">Confirm Withdrawal</h2>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="withdraw" />
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Currency</p>
                        <p class="h5">{$system}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Wallet</p>
                        <p class="h5 text-break">{$address}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Amount</p>
                        <p class="h5">{$amount|fiat}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Fee</p>
                        <p class="h5">{if $fee > 0}{$fee|fiat}{else}<span class="text-success">No fee</span>{/if}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">You Receive</p>
                        <p class="h5 text-success">{$debit|fiat}</p>
                    </div>
                    <div class="col-12">
                        <p class="mb-1 text-muted">In {$symbol}</p>
                        <p class="h5">{$credit}</p>
                    </div>
                </div>
                {include file="auth.tpl" action="withdraw"}
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100" name="submit">
                        <i class="bi bi-check-circle me-2"></i>Confirm Withdrawal
                    </button>
                </div>
            </form>
        </div>
        {else}
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">Request Withdrawal</h2>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="with_submit" />
                <h3 class="h5 mb-3">Select Currency</h3>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
                    {section name=p loop=$ps}
                    <div class="col">
                        <div class="card h-100 {if $ps[p].balance <= 0}bg-light{/if}">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="ec" value="{$ps[p].id}" id="currency_{$ps[p].id}" {if $ps[p].balance <= 0}disabled{/if}>
                                    <label class="form-check-label w-100" for="currency_{$ps[p].id}">
                                        <img src="images/icons/{$ps[p].id}.svg" alt="{$ps[p].name}" class="mb-2" width="40" height="40">
                                        <p class="mb-1 fw-bold">{$ps[p].name}</p>
                                        <p class="mb-1 text-success">{$ps[p].balance|fiat}</p>
                                        <small class="text-muted">Min: {$ps[p].with_min|fiat}</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    {/section}
                </div>
                {if $userinfo.accountbalance > 0}
                <div class="mb-3">
                    {if (!$settings.withdraw.address)}
                    <label for="address" class="form-label">Wallet Address</label>
                    <input type="text" class="form-control form-control-lg" id="address" name="address" required>
                    {/if}
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount to Withdraw</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="amount" name="amount" placeholder="0.00" required>
                    </div>
                </div>
                {include file="captcha.tpl" action="withdrawal"}
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100" name="submit">
                        <i class="bi bi-send me-2"></i>Withdraw
                    </button>
                </div>
                {else}
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>You have no funds to withdraw.
                </div>
                {/if}
            </form>
        </div>
        {/if}
    </div>

{include file="mfooter.tpl"}