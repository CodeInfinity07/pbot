{include file="mheader.tpl"}

    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}
    {if $notice}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    <div class="row g-4">
        <!-- Account Information -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Name</span>
                            <strong>{$userinfo.fullname} {$userinfo.tier}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Username</span>
                            <strong>{$userinfo.username}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Member Since</span>
                            <strong>{$userinfo.created_at|date_format}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Last Access</span>
                            <strong>{$userinfo.last_access}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Last Access IP</span>
                            <strong>{$userinfo.last_access_ip}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Upline</span>
                            <strong>{$userinfo.sponsor|default:"N/A"}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>2FA</span>
                            <strong>{if !$user.2fa} {if !$userinfo.2fa}Not Enabled{else}Enabled{/if}{/if}</strong>
                        </li>
                        {if $userinfo.level}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Account Affiliate Level</span>
                            <strong>{$userinfo.level}</strong>
                        </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>

        <!-- Account Balance -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Account Balance (${$userinfo.accountbalance|number_format:2})</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {section name=p loop=$ps}
                        {if $ps[p].balance > 0 }
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">{$ps[p].name}</h6>
                                    <p class="card-text">{$ps[p].balance|fiat:{$ps[p].id}}</p>
                                    <small class="text-muted">~${$ps[p].balance|currencytousd:{$ps[p].symbol}}</small>
                                </div>
                            </div>
                        </div>
                        {/if}
                        {/section}
                    </div>
                </div>
            </div>
        </div>

        <!-- Referrals -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Referrals</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Referrals / Active Referrals</span>
                            <strong>{$userinfo.affiliates} / {$userinfo.affiliates_active}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Commission / Total Invested</span>
                            <strong>{$userinfo.affiliates_total|fiat} / {$userinfo.affiliates_investment|fiat}</strong>
                        </li>
                        <li class="list-group-item">
                            <span>Referral link</span>
                            <div class="input-group mt-2">
                                <input type="text" class="form-control" value="{$settings.site_url}/?ref={$userinfo.username}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">Copy</button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Investments -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Investments</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Active</span>
                            <strong>{$userinfo.investments_active|fiat}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total</span>
                            <strong>{$userinfo.investments_total|fiat}</strong>
                        </li>
                        {if $investment_last}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Last</span>
                            <div class="text-end">
                                <strong>{$investment_last|fiat:{$investment_last_cid}}</strong>
                                <small class="d-block text-muted">{$investment_last_date|default:"n/a"}</small>
                            </div>
                        </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>

        <!-- Earnings -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Earnings</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total</span>
                            <strong>{$userinfo.earnings_total|fiat}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Today</span>
                            <strong>{$userinfo.earnings_today|fiat}</strong>
                        </li>
                        {if $earning_last}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Last</span>
                            <div class="text-end">
                                <strong>{$earning_last|fiat:{$earning_last_cid}}</strong>
                                <small class="d-block text-muted">{$earning_last_date|default:"n/a"}</small>
                            </div>
                        </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>

        <!-- Withdrawals -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Withdrawals</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total</span>
                            <strong>{$userinfo.withdrawals_total|fiat}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Pending</span>
                            <strong>{$userinfo.withdrawals_pending|fiat}</strong>
                        </li>
                        {if $withdrawal_last}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Last</span>
                            <div class="text-end">
                                <strong>{$withdrawal_last|fiat:{$withdrawal_last_cid}}</strong>
                                <small class="d-block text-muted">{$withdrawal_last_date|default:"n/a"}</small>
                            </div>
                        </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>

        <!-- Active Plans -->
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Active Plans {$settings.infobox.dpackages}</h5>
                </div>
                <div class="card-body">
                    {section name=l loop=$activeplans}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div>Deposit Starts: <span class="fw-bold">{$activeplans[l].datetime}</span></div>
                                <div>Next Earning: <span class="fw-bold">{$activeplans[l].next_earning|default:"Crediting"}</span></div>
                                <div>Accurals left: <span class="fw-bold">{$activeplans[l].duration-$activeplans[l].avail}</span></div>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: {$activeplans[l].duration-$activeplans[l].avail}%" aria-valuenow="{$activeplans[l].avail}" aria-valuemin="0" aria-valuemax="{$activeplans[l].duration}"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>Plan: <span class="fw-bold">{$activeplans[l].name}</span></div>
                                <div>Investment: <span class="fw-bold">{$activeplans[l].amount|fiat}</span></div>
                                <div>Profit: <span class="fw-bold">{$activeplans[l].earned|fiat}</span></div>
                            </div>
                        </div>
                    </div>
                    {sectionelse}
                    <div class="alert alert-info">No Active Investments</div>
                    {/section}
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Logs {$settings.infobox.dlogs}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        {section name=l loop=$logs}
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">{$logs[l].date|time_elapsed_string}</small>
                                <span class="badge bg-primary">{$logs[l].title}</span>
                            </div>
                            <div>{$logs[l].content}</div>
                        </li>
                        {/section}
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                {section name=l loop=$transactions}
                                <tr>
                                    <td>{$transactions[l].date}</td>
                                    <td><span class="badge bg-primary">{$transactions[l].txn_type}</span></td>
                                    <td>
                                        {$transactions[l].amount|fiat:$transactions[l].cid}
                                        <small class="text-muted d-block">~${$transactions[l].amount|currencytousd:{$transactions[l].symbol}}</small>
                                    </td>
                                    <td>{$transactions[l].detail}</td>
                                </tr>
                                {sectionelse}
                                <tr>
                                    <td colspan="4" class="text-center">No Transactions Record Found</td>
                                </tr>
                                {/section}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


{include file="mfooter.tpl"}

<script>
function copyToClipboard(button) {
    var input = button.previousElementSibling;
    input.select();
    document.execCommand("copy");
    button.textContent = "Copied!";
    setTimeout(function() {
        button.textContent = "Copy";
    }, 2000);
}
</script>