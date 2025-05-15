<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   {include file="meta.tpl"}
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link:hover {
            color: #007bff;
        }
        .sidebar .nav-link.active {
            color: #007bff;
        }
        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: #007bff;
        }
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
        }
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
        }
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
        .btn-sidebar {
            text-align: left;
            padding-left: 0;
            padding-right: 0;
        }
        @media (max-width: 767.98px) {
            #sidebarMenu {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 1000;
                overflow-y: auto;
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/">{$settings.site_name}</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav d-none d-md-block">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="logout">Sign out</a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
             <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="dashboard">
                                <i class="bi bi-house-door me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="invest">
                                <i class="bi bi-cash-coin me-2"></i>
                                Invest
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="invested"><i class="bi bi-graph-up me-2"></i>Active Investments</a></li>
                        {if $settings.deposit.topup}<li class="nav-item"><a class="nav-link" href="deposit"><i class="bi bi-wallet me-2"></i>Topup </a></li>{/if}
                        {if $settings.exchange.enable}<li class="nav-item"><a class="nav-link" href="exchange"><i class="bi bi-currency-exchange me-2"></i>Exchange</a></li>{/if}
                        {if $settings.transfer.enable}<li class="nav-item"><a class="nav-link" href="transfer"><i class="bi bi-arrow-left-right me-2"></i>Transfer</a></li>{/if}
                      
                        {if $settings.deposit.faucet}<li class="nav-item"><a class="nav-link" href="faucet"><i class="bi bi-droplet me-2"></i>Faucet</a></li>{/if}
                       
                     <li class="nav-item"><a class="nav-link" href="affiliates"><i class="bi bi-megaphone me-2"></i>Affiliates</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="withdraw">
                                <i class="bi bi-cash-stack me-2"></i>
                                Withdraw
                            </a>
                        </li>
{if $settings.games.enable}
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#actions-collapse" aria-expanded="false">
                                <i class="bi bi-controller me-2"></i>
                                Games
                            </a>
                            <div class="collapse" id="actions-collapse">
                                <ul class="nav flex-column ms-3">
                                   <li class="nav-item"><a class="nav-link" href="head_tail"><i class="bi bi-coin me-2"></i>Head & Tail Game</a></li>
                                </ul>
                            </div>
                        </li>
                        {/if}
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#transactions-collapse" aria-expanded="false">
                                <i class="bi bi-list-ul me-2"></i>
                                Transactions
                            </a>
                            <div class="collapse" id="transactions-collapse">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item"><a class="nav-link" href="investments"><i class="bi bi-graph-up-arrow me-2"></i>Investments</a></li>
                                    <li class="nav-item"><a class="nav-link" href="earnings"><i class="bi bi-piggy-bank me-2"></i>Earnings</a></li>
                                    <li class="nav-item"><a class="nav-link" href="withdrawals"><i class="bi bi-cash me-2"></i>Withdrawals</a></li>
                                    {if $settings.referral.enable}<li class="nav-item"><a class="nav-link" href="commissions"><i class="bi bi-briefcase me-2"></i>Commissions</a></li>{/if}
                                    {if $settings.referral.enable}<li class="nav-item"><a class="nav-link" href="affiliates"><i class="bi bi-people me-2"></i>Affiliates</a></li>{/if}
                                    {if $settings.transfer.enable}<li class="nav-item"><a class="nav-link" href="transfers"><i class="bi bi-arrow-left-right me-2"></i>Transfers</a></li>{/if}
                                    {if $settings.exchange.enable}<li class="nav-item"><a class="nav-link" href="exchanges"><i class="bi bi-currency-exchange me-2"></i>Exchanges</a></li>{/if}
                                </ul>
                            </div>
                        </li>
                        {if $settings.user.support}<li class="nav-item"><a class="nav-link" href="support"><i class="bi bi-question-circle me-2"></i>Support</a></li>{/if}
                        <li class="nav-item"><a class="nav-link" href="profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                        {if $settings.user.wallets}<li class="nav-item"><a class="nav-link" href="wallets"><i class="bi bi-wallet2 me-2"></i>Wallets</a></li>{/if}
                        {if $settings.user.2fa}<li class="nav-item"><a class="nav-link" href="2fa"><i class="bi bi-shield-lock me-2"></i>2FA</a></li>{/if}
                        {if $settings.kyc.enable}<li class="nav-item"><a class="nav-link" href="kyc"><i class="bi bi-person-badge me-2"></i>KYC</a></li>{/if}
                        {if $settings.user.logs}<li class="nav-item"><a class="nav-link" href="logs"><i class="bi bi-journal-text me-2"></i>Logs</a></li>{/if}
                        {if $settings.tasks.enable}
                        <li class="nav-item"><a class="nav-link" href="tasks"><i class="bi bi-person me-2"></i>Tasks</a></li>
                         <li class="nav-item"><a class="nav-link" href="bounty"><i class="bi bi-person me-2"></i>Bounty</a></li>
                         {/if}
                        <li class="nav-item">
                            <a class="nav-link" href="logout">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">{$pagename}</h1>
                </div>
             