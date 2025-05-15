{include file="header.tpl"}
<section class="hero-section text-center text-white" style="
    background: linear-gradient(135deg, var(--bs-primary), var(--bs-secondary));
    position: relative;
    overflow: hidden;
    padding: 5rem 0;
">
    <div class="container hero-content position-relative z-1">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">
                    {$content.home.title} {$settings.site_name}
                </h1>
                <p class="lead mb-4 mb-md-5 text-white-75 animate__animated animate__fadeInUp animate__delay-1s">
                    {$content.home.content}
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3">
<a href="register" class="btn btn-light btn-lg w-100 w-sm-auto hero-btn px-3 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" style="
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
">
    <i class="bi bi-person-plus me-1"></i>
    Register Now
    <i class="bi bi-arrow-right ms-1"></i>
</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="investment-plans py-5 bg-light" aria-labelledby="plans-heading">
    <div class="container">
        <h1 id="plans-heading" class="h2 text-center mb-4 fw-bold">Investment Plans</h1>
        <p class="text-center mb-5">Choose the investment plan that best suits your goals</p>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            {foreach from=$index_plans item=p}
            <div class="col">
                <article class="card h-100 shadow-sm border-0 overflow-hidden" style="
                    transition: all 0.3s ease;
                    transform-style: preserve-3d;
                ">
                    <div class="card-header bg-primary bg-gradient text-white py-3 position-relative" style="
                        background: linear-gradient(45deg, var(--bs-primary), var(--bs-secondary)) !important;
                    ">
                        <h2 class="h5 card-title text-center mb-0 fw-bold">
                            {$p.name|escape:html}
                        </h2>
                    </div>

                    {if $p.plans}
                    {foreach from=$p.plans item=o}
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <span class="display-6 fw-bold text-primary">
                                {if $p.etype == 0}{$o.percent}
                                {elseif $p.etype == 1 || $p.etype == 2}{$o.percent_min}~{$o.percent_max}{/if}
                            </span>
                            <p class="text-muted mb-0">{$p.frequency}{if $p.period > 1}s{/if}</p>
                        </div>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Minimum Deposit</span>
                                <strong aria-label="Minimum deposit amount: {$o.min_deposit}">${$o.min_deposit}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Maximum Deposit</span>
                                <strong aria-label="Maximum deposit amount: {$o.max_deposit}">${$o.max_deposit}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Accrual Period</span>
                                <strong aria-label="Accrual period: {$p.accurals} times">{$p.accurals}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Duration</span>
                                <strong aria-label="Duration: {$p.days} days">{$p.days} Days</strong>
                            </li>
                        </ul>
                    </div>
                    {/foreach}
                    {/if}

                    <div class="card-footer bg-transparent text-center border-0 pb-4">
                        {if !$userinfo.logged}
                        <a href="register" class="btn btn-primary btn-lg w-75" role="button" style="
                            transition: all 0.3s ease;
                            transform: perspective(300px);
                        ">
                            <span class="d-flex align-items-center justify-content-center gap-2">
                                Start Investing
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" aria-hidden="true" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                                </svg>
                            </span>
                        </a>
                        {else}
                        <a href="invest" class="btn btn-primary btn-lg w-75" role="button" style="
                            transition: all 0.3s ease;
                            transform: perspective(300px);
                        ">
                            <span class="d-flex align-items-center justify-content-center gap-2">
                                Invest Now
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" aria-hidden="true" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                                </svg>
                            </span>
                        </a>
                        {/if}
                    </div>
                </article>
            </div>
            {/foreach}
        </div>
    </div>
</section>

<style>
@media (max-width: 576px) {
    .hero-section .btn, 
    .investment-plans .btn {
        width: 100% !important;
        margin-bottom: 0.5rem !important;
    }
    
    .hero-section .d-flex {
        flex-direction: column !important;
        gap: 0.75rem !important;
    }
}

.card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
}

.btn:hover {
    transform: translateY(-5px) scale(1.05);
}
</style>
<section class="bg-light py-5" aria-labelledby="stats-heading">
    <div class="container">
        <h2 id="stats-heading" class="text-center mb-4 fw-bold">Company Statistics</h2>
        <p class="text-center mb-5">Track our growth and success metrics</p>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 g-4">
            <!-- Days Online -->
            <div class="col">
                <div class="stat-card h-100 rounded-3 p-4 bg-white shadow-sm" tabindex="0">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="stat-icon mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-calendar-check text-primary" aria-hidden="true" viewBox="0 0 16 16">
                                <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                        </div>
                        <h3 class="display-6 fw-bold mb-1" aria-label="Days Online: {$settings.site_days_online_generated}">
                            {$settings.site_days_online_generated}
                        </h3>
                        <p class="text-muted mb-0">Days Online</p>
                    </div>
                </div>
            </div>

            <!-- Start Date -->
            <div class="col">
                <div class="stat-card h-100 rounded-3 p-4 bg-white shadow-sm" tabindex="0">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="stat-icon mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-flag text-primary" aria-hidden="true" viewBox="0 0 16 16">
                                <path d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12.435 12.435 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A19.626 19.626 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a19.587 19.587 0 0 0 1.349-.476l.019-.007.004-.002h.001"/>
                            </svg>
                        </div>
                        <h3 class="h5 fw-bold mb-1" aria-label="Started Date: {$settings.site_start_month_str_generated} {$settings.site_start_day}, {$settings.site_start_year}">
                            {$settings.site_start_month_str_generated} {$settings.site_start_day}, {$settings.site_start_year}
                        </h3>
                        <p class="text-muted mb-0">Started Date</p>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="col">
                <div class="stat-card h-100 rounded-3 p-4 bg-white shadow-sm" tabindex="0">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="stat-icon mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-people text-primary" aria-hidden="true" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                            </svg>
                        </div>
                        <h3 class="display-6 fw-bold mb-1" aria-label="Total Users: {$settings.info_box_total_accounts_generated}">
                            {$settings.info_box_total_accounts_generated}
                        </h3>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="col">
                <div class="stat-card h-100 rounded-3 p-4 bg-white shadow-sm" tabindex="0">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="stat-icon mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-check text-primary" aria-hidden="true" viewBox="0 0 16 16">
                                <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514ZM11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                                <path d="M8.256 14a4.474 4.474 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10c.26 0 .507.009.74.025.226-.341.496-.65.804-.918C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4s1 1 1 1h5.256Z"/>
                            </svg>
                        </div>
                        <h3 class="display-6 fw-bold mb-1" aria-label="Active Users: {$settings.info_box_total_accounts_generated}">
                            {$settings.info_box_total_accounts_generated}
                        </h3>
                        <p class="text-muted mb-0">Active Users</p>
                    </div>
                </div>
            </div>

            <!-- Total Invested -->
            <div class="col">
                <div class="stat-card h-100 rounded-3 p-4 bg-white shadow-sm" tabindex="0">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="stat-icon mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-graph-up-arrow text-primary" aria-hidden="true" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5Z"/>
                            </svg>
                        </div>
                        <h3 class="display-6 fw-bold mb-1" aria-label="Total Invested: {$settings.info_box_invest_funds_generated|fiat}">
                            {$settings.info_box_invest_funds_generated|fiat}
                        </h3>
                        <p class="text-muted mb-0">Total Invested</p>
                    </div>
                </div>
            </div>

            <!-- Total Withdrawn -->
            <div class="col">
                <div class="stat-card h-100 rounded-3 p-4 bg-white shadow-sm" tabindex="0">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="stat-icon mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cash-stack text-primary" aria-hidden="true" viewBox="0 0 16 16">
                                <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                                <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                            </svg>
                        </div>
                        <h3 class="display-6 fw-bold mb-1" aria-label="Total Withdrawn: {$settings.info_box_withdraw_funds_generated|fiat}">
                            {$settings.info_box_withdraw_funds_generated|fiat}
                        </h3>
                        <p class="text-muted mb-0">Total Withdrawn</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="transactions-section py-5" aria-labelledby="transactions-heading">
    <div class="container">
        <h2 id="transactions-heading" class="text-center mb-4 fw-bold">Latest Transactions</h2>
        <p class="text-center mb-5">Real-time overview of recent platform activities</p>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="transactionsTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Type</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter text-muted" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
                                    </svg>
                                </div>
                            </th>
                            <th scope="col" class="py-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Date</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar text-muted" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                    </svg>
                                </div>
                            </th>
                            <th scope="col" class="py-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Username</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person text-muted" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4Zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10Z"/>
                                    </svg>
                                </div>
                            </th>
                            <th scope="col" class="py-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Payment Method</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card text-muted" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z"/>
                                        <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z"/>
                                    </svg>
                                </div>
                            </th>
                            <th scope="col" class="py-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Amount</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash text-muted" aria-hidden="true" viewBox="0 0 16 16">
                                        <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                                        <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2H3z"/>
                                    </svg>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$last_transactions item=s}
                        <tr>
                            <td class="py-3">
                                <span class="badge bg-{if $s.type == 'deposit'}success{else}primary{/if} rounded-pill">
                                    {$s.type}
                                </span>
                            </td>
                            <td class="py-3" aria-label="Transaction date">
                                <div class="d-flex align-items-center">
                                    <span>{$s.datetime|time_elapsed_string}</span>
                                </div>
                            </td>
                            <td class="py-3" aria-label="Username">
                                <div class="d-flex align-items-center">
                                    <div class="avatar rounded-circle bg-light text-primary me-2" style="width: 32px; height: 32px;">
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            {$s.username|truncate:1:""}
                                        </div>
                                    </div>
                                    <span>{$s.username}</span>
                                </div>
                            </td>
                            <td class="py-3" aria-label="Payment method: {$s.currency}">
                                <div class="d-flex align-items-center">
                                    <img width="24" height="24" src="images/icons/{$s.cid}.svg" alt="{$s.currency} icon" class="me-2" />
                                    <span>{$s.currency}</span>
                                </div>
                            </td>
                            <td class="py-3 fw-bold" aria-label="Amount: {$currency_sign}{$s.amount}">
                                <div class="d-flex align-items-center">
                                    <span class="text-{if $s.type == 'deposit'}success{else}primary{/if}">
                                        {$currency_sign}{$s.amount}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

   <section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4 fw-bold">Latest News and Updates</h2>
        <p class="text-center mb-5">Stay informed about our latest developments</p>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            {foreach from=$news key=key item=value}
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="{$value.image_url}" class="card-img-top" alt="{$value.title}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-title h5 mb-0">{$value.title}</h3>
                            <small class="text-muted">{$value.datetime}</small>
                        </div>
                        <p class="card-text flex-grow-1">{$value.content|truncate:100}</p>
                        <a href="{$value.link}" class="btn btn-primary mt-auto">Read more</a>
                    </div>
                </div>
            </div>
            {foreachelse}
            <div class="col-12">
                <div class="alert alert-info text-center">
                    No news found! Visit this page regularly to keep yourself updated about the latest company news & updates.
                </div>
            </div>
            {/foreach}
        </div>
    </div>
</section>
{if $last_reviews}
<section class="reviews-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h2 fw-bold mb-3">What Our Clients Say</h2>
            <p class="lead text-muted">Hear directly from our satisfied customers</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            {foreach from=$last_reviews item=s}
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="avatar mx-auto mb-2" style="width: 64px; height: 64px; background-color: var(--bs-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                {$s.uname|substr:0:1|upper}
                            </div>
                            <h3 class="card-title h5 mb-1">{$s.uname|escape:html}</h3>
                            <small class="text-muted">{$s.datetime|time_elapsed_string}</small>
                        </div>
                        <p class="card-text fst-italic">"{$s.review|escape:htmlall}"</p>
                    </div>
                </div>
            </div>
            {/foreach}
        </div>

        <div class="text-center mt-5">
            <a href="reviews" class="btn btn-primary btn-lg">View More Reviews</a>
        </div>
    </div>
</section>
{/if}
<section class="affiliate-section py-5" aria-labelledby="affiliate-heading">
    <div class="container">
        <div class="text-center mb-5">
            <h2 id="affiliate-heading" class="h2 fw-bold mb-3">Affiliate Program</h2>
            <p class="lead text-muted">
                {$settings.referral.levels} Level Affiliate Program with Transparent Tiers
            </p>
        </div>

        <div class="card border-0 shadow-sm overflow-auto">
            <table class="table table-hover align-middle mb-0" aria-label="Affiliate Program Tiers">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="py-3">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Tier</span>
                                <i class="bi bi-trophy text-muted"></i>
                            </div>
                        </th>
                        <th scope="col" class="py-3">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Name</span>
                                <i class="bi bi-tag text-muted"></i>
                            </div>
                        </th>
                        {for $i=1 to $settings.referral.levels}
                        <th scope="col" class="py-3">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Level {$i}</span>
                                <i class="bi bi-diagram-3 text-muted"></i>
                            </div>
                        </th>
                        {/for}
                        {if $settings.referral.system == 'ranges'}
                        <th scope="col" class="py-3">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Required Investment</span>
                                <i class="bi bi-cash-stack text-muted"></i>
                            </div>
                        </th>
                        {/if}
                    </tr>
                </thead>
                <tbody>
                    {foreach $settings.referral.tier as $tierId => $tier}
                    <tr class="tier-row" tabindex="0">
                        <td class="py-3">
                            <span class="badge bg-primary rounded-pill">{$tierId}</span>
                        </td>
                        <td class="py-3 fw-medium">{$tier.name}</td>
                        {foreach $tier.level as $value}
                        <td class="py-3">
                            <span class="text-success fw-medium">{$value}%</span>
                        </td>
                        {/foreach}
                        {if $settings.referral.system == 'ranges'}
                        <td class="py-3">{$tier.invested|fiat}</td>
                        {/if}
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</section>

{if $games}        <section>
        <div class="container my-5">
            <h2 class="display-6 text-center mb-2 pt-5">Games </h2>
            <div class="row">
                {foreach from=$games item=s}
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="https://via.placeholder.com/50" class="rounded-circle mx-auto mt-3" alt="Client Image">
                        <div class="card-body text-center">
                            <h3 class="card-title">{$s.uname|escape:html}</h3>
                            <h6>{$s.datetime|time_elapsed_string}</h6>
                            <p class="card-text">"{$s.review|escape:htmlall}"</p>
                        </div>
                    </div>
                </div>
                {foreachelse}
                <div class="col">
                    <div class="alert alert-info text-center">
                        No Reviews are posted yet.
                    </div>
                </div>
                {/foreach}
            </div>
            <div class="text-center">
                <a href="reviews" class="btn btn-primary">View More</a>
            </div>
        </div>
    </section>
    {/if}
<section class="payment-methods-section bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h2 fw-bold">Available Currencies</h2>
            <p class="lead text-muted">We support multiple payment methods for your convenience</p>
        </div>

        <div class="row g-4 justify-content-center">
            {section name=p loop=$ps}
            <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center">
                <div class="d-flex flex-column align-items-center">
                    <div class="mb-2 p-3 bg-white rounded-circle shadow-sm" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <img 
                            src="images/icons/{$ps[p].id}.svg" 
                            class="img-fluid" 
                            width="50" 
                            height="50" 
                            alt="{$ps[p].name} payment method logo"
                        />
                    </div>
                    <span class="text-muted fw-medium">{$ps[p].name}</span>
                </div>
            </div>
            {/section}
        </div>
    </div>
</section>

{include file="footer.tpl"}
