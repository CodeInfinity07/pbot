{include file="mheader.tpl"}

<div class="container py-5">
    <h1 class="mb-4 text-center">Your Affiliate Dashboard</h1>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Affiliates</h5>
                    <p class="card-text fs-4">{$userinfo.affiliates} / {$userinfo.affiliates_active}</p>
                    <small>Total / Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Referrals Invested</h5>
                    <p class="card-text fs-4">{$userinfo.affiliates_investment|fiat}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Commission/Rewards</h5>
                    <p class="card-text fs-4">{$userinfo.affiliates_total|fiat}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title">Earnings by Currency</h5>
                    <ul class="list-unstyled">
                        {section name=p loop=$ref_pms}
                        <li>{$ref_pms[p].am|fiat:{$ref_pms[p].payment_method_id}}</li>
                        {/section}
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mb-4">Affiliate Levels</h2>
    <div class="row g-4 mb-5">
        {foreach from=$levels key=key item=s}
        <div class="col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Level {$key+1}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Users
                            <span class="badge bg-primary rounded-pill">{$s.users}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Users
                            <span class="badge bg-success rounded-pill">{$s.active_users}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Earnings
                            <span class="badge bg-info rounded-pill">{$s.earning}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Deposit
                            <span class="badge bg-warning rounded-pill">{$s.deposit}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        {/foreach}
    </div>

    <h2 class="mb-4">Affiliate List</h2>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Date Joined</th>
                            <th>Username</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$rows key=key item=value}
                        <tr>
                            <td>{$value.created_at}</td>
                            <td>{$value.username}</td>
                            <td>{$value.deposit|fiat} Currency ID Currency Name</td>
                            <td>
                                {if $value.deposit}
                                <span class="badge bg-success">Active</span>
                                {else}
                                <span class="badge bg-danger">Inactive</span>
                                {/if}
                            </td>
                            <td>{$value.level}</td>
                        </tr>
                        {foreachelse}
                        <tr>
                            <td colspan="5" class="text-center">No Affiliates found</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {$paginator}
        </div>
    </div>
</div>

{include file="mfooter.tpl"}