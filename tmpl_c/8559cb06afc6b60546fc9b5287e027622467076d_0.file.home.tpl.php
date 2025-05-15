<?php
/* Smarty version 4.5.2, created on 2025-05-11 19:28:09
  from '/home/assitix/public_html/tmpl/home.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.2',
  'unifunc' => 'content_6820fa49c550e3_84822508',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8559cb06afc6b60546fc9b5287e027622467076d' => 
    array (
      0 => '/home/assitix/public_html/tmpl/home.tpl',
      1 => 1731323880,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:header.tpl' => 1,
    'file:footer.tpl' => 1,
  ),
),false)) {
function content_6820fa49c550e3_84822508 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/home/assitix/public_html/includes/smarty/plugins/modifier.truncate.php','function'=>'smarty_modifier_truncate',),));
$_smarty_tpl->_subTemplateRender("file:header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
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
                    <?php echo $_smarty_tpl->tpl_vars['content']->value['home']['title'];?>
 <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>

                </h1>
                <p class="lead mb-4 mb-md-5 text-white-75 animate__animated animate__fadeInUp animate__delay-1s">
                    <?php echo $_smarty_tpl->tpl_vars['content']->value['home']['content'];?>

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
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['index_plans']->value, 'p');
$_smarty_tpl->tpl_vars['p']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['p']->value) {
$_smarty_tpl->tpl_vars['p']->do_else = false;
?>
            <div class="col">
                <article class="card h-100 shadow-sm border-0 overflow-hidden" style="
                    transition: all 0.3s ease;
                    transform-style: preserve-3d;
                ">
                    <div class="card-header bg-primary bg-gradient text-white py-3 position-relative" style="
                        background: linear-gradient(45deg, var(--bs-primary), var(--bs-secondary)) !important;
                    ">
                        <h2 class="h5 card-title text-center mb-0 fw-bold">
                            <?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['p']->value['name'], ENT_QUOTES, 'UTF-8', true);?>

                        </h2>
                    </div>

                    <?php if ($_smarty_tpl->tpl_vars['p']->value['plans']) {?>
                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['p']->value['plans'], 'o');
$_smarty_tpl->tpl_vars['o']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['o']->value) {
$_smarty_tpl->tpl_vars['o']->do_else = false;
?>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <span class="display-6 fw-bold text-primary">
                                <?php if ($_smarty_tpl->tpl_vars['p']->value['etype'] == 0) {
echo $_smarty_tpl->tpl_vars['o']->value['percent'];?>

                                <?php } elseif ($_smarty_tpl->tpl_vars['p']->value['etype'] == 1 || $_smarty_tpl->tpl_vars['p']->value['etype'] == 2) {
echo $_smarty_tpl->tpl_vars['o']->value['percent_min'];?>
~<?php echo $_smarty_tpl->tpl_vars['o']->value['percent_max'];
}?>
                            </span>
                            <p class="text-muted mb-0"><?php echo $_smarty_tpl->tpl_vars['p']->value['frequency'];
if ($_smarty_tpl->tpl_vars['p']->value['period'] > 1) {?>s<?php }?></p>
                        </div>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Minimum Deposit</span>
                                <strong aria-label="Minimum deposit amount: <?php echo $_smarty_tpl->tpl_vars['o']->value['min_deposit'];?>
">$<?php echo $_smarty_tpl->tpl_vars['o']->value['min_deposit'];?>
</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Maximum Deposit</span>
                                <strong aria-label="Maximum deposit amount: <?php echo $_smarty_tpl->tpl_vars['o']->value['max_deposit'];?>
">$<?php echo $_smarty_tpl->tpl_vars['o']->value['max_deposit'];?>
</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Accrual Period</span>
                                <strong aria-label="Accrual period: <?php echo $_smarty_tpl->tpl_vars['p']->value['accurals'];?>
 times"><?php echo $_smarty_tpl->tpl_vars['p']->value['accurals'];?>
</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Duration</span>
                                <strong aria-label="Duration: <?php echo $_smarty_tpl->tpl_vars['p']->value['days'];?>
 days"><?php echo $_smarty_tpl->tpl_vars['p']->value['days'];?>
 Days</strong>
                            </li>
                        </ul>
                    </div>
                    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                    <?php }?>

                    <div class="card-footer bg-transparent text-center border-0 pb-4">
                        <?php if (!$_smarty_tpl->tpl_vars['userinfo']->value['logged']) {?>
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
                        <?php } else { ?>
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
                        <?php }?>
                    </div>
                </article>
            </div>
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
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
                        <h3 class="display-6 fw-bold mb-1" aria-label="Days Online: <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_days_online_generated'];?>
">
                            <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_days_online_generated'];?>

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
                        <h3 class="h5 fw-bold mb-1" aria-label="Started Date: <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_start_month_str_generated'];?>
 <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_start_day'];?>
, <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_start_year'];?>
">
                            <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_start_month_str_generated'];?>
 <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_start_day'];?>
, <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_start_year'];?>

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
                        <h3 class="display-6 fw-bold mb-1" aria-label="Total Users: <?php echo $_smarty_tpl->tpl_vars['settings']->value['info_box_total_accounts_generated'];?>
">
                            <?php echo $_smarty_tpl->tpl_vars['settings']->value['info_box_total_accounts_generated'];?>

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
                        <h3 class="display-6 fw-bold mb-1" aria-label="Active Users: <?php echo $_smarty_tpl->tpl_vars['settings']->value['info_box_total_accounts_generated'];?>
">
                            <?php echo $_smarty_tpl->tpl_vars['settings']->value['info_box_total_accounts_generated'];?>

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
                        <h3 class="display-6 fw-bold mb-1" aria-label="Total Invested: <?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'fiat' ][ 0 ], array( $_smarty_tpl->tpl_vars['settings']->value['info_box_invest_funds_generated'] ));?>
">
                            <?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'fiat' ][ 0 ], array( $_smarty_tpl->tpl_vars['settings']->value['info_box_invest_funds_generated'] ));?>

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
                        <h3 class="display-6 fw-bold mb-1" aria-label="Total Withdrawn: <?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'fiat' ][ 0 ], array( $_smarty_tpl->tpl_vars['settings']->value['info_box_withdraw_funds_generated'] ));?>
">
                            <?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'fiat' ][ 0 ], array( $_smarty_tpl->tpl_vars['settings']->value['info_box_withdraw_funds_generated'] ));?>

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
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['last_transactions']->value, 's');
$_smarty_tpl->tpl_vars['s']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['s']->value) {
$_smarty_tpl->tpl_vars['s']->do_else = false;
?>
                        <tr>
                            <td class="py-3">
                                <span class="badge bg-<?php if ($_smarty_tpl->tpl_vars['s']->value['type'] == 'deposit') {?>success<?php } else { ?>primary<?php }?> rounded-pill">
                                    <?php echo $_smarty_tpl->tpl_vars['s']->value['type'];?>

                                </span>
                            </td>
                            <td class="py-3" aria-label="Transaction date">
                                <div class="d-flex align-items-center">
                                    <span><?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'time_elapsed_string' ][ 0 ], array( $_smarty_tpl->tpl_vars['s']->value['datetime'] ));?>
</span>
                                </div>
                            </td>
                            <td class="py-3" aria-label="Username">
                                <div class="d-flex align-items-center">
                                    <div class="avatar rounded-circle bg-light text-primary me-2" style="width: 32px; height: 32px;">
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['s']->value['username'],1,'');?>

                                        </div>
                                    </div>
                                    <span><?php echo $_smarty_tpl->tpl_vars['s']->value['username'];?>
</span>
                                </div>
                            </td>
                            <td class="py-3" aria-label="Payment method: <?php echo $_smarty_tpl->tpl_vars['s']->value['currency'];?>
">
                                <div class="d-flex align-items-center">
                                    <img width="24" height="24" src="images/icons/<?php echo $_smarty_tpl->tpl_vars['s']->value['cid'];?>
.svg" alt="<?php echo $_smarty_tpl->tpl_vars['s']->value['currency'];?>
 icon" class="me-2" />
                                    <span><?php echo $_smarty_tpl->tpl_vars['s']->value['currency'];?>
</span>
                                </div>
                            </td>
                            <td class="py-3 fw-bold" aria-label="Amount: <?php echo $_smarty_tpl->tpl_vars['currency_sign']->value;
echo $_smarty_tpl->tpl_vars['s']->value['amount'];?>
">
                                <div class="d-flex align-items-center">
                                    <span class="text-<?php if ($_smarty_tpl->tpl_vars['s']->value['type'] == 'deposit') {?>success<?php } else { ?>primary<?php }?>">
                                        <?php echo $_smarty_tpl->tpl_vars['currency_sign']->value;
echo $_smarty_tpl->tpl_vars['s']->value['amount'];?>

                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
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
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['news']->value, 'value', false, 'key');
$_smarty_tpl->tpl_vars['value']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['key']->value => $_smarty_tpl->tpl_vars['value']->value) {
$_smarty_tpl->tpl_vars['value']->do_else = false;
?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="<?php echo $_smarty_tpl->tpl_vars['value']->value['image_url'];?>
" class="card-img-top" alt="<?php echo $_smarty_tpl->tpl_vars['value']->value['title'];?>
">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-title h5 mb-0"><?php echo $_smarty_tpl->tpl_vars['value']->value['title'];?>
</h3>
                            <small class="text-muted"><?php echo $_smarty_tpl->tpl_vars['value']->value['datetime'];?>
</small>
                        </div>
                        <p class="card-text flex-grow-1"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['value']->value['content'],100);?>
</p>
                        <a href="<?php echo $_smarty_tpl->tpl_vars['value']->value['link'];?>
" class="btn btn-primary mt-auto">Read more</a>
                    </div>
                </div>
            </div>
            <?php
}
if ($_smarty_tpl->tpl_vars['value']->do_else) {
?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    No news found! Visit this page regularly to keep yourself updated about the latest company news & updates.
                </div>
            </div>
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </div>
    </div>
</section>
<?php if ($_smarty_tpl->tpl_vars['last_reviews']->value) {?>
<section class="reviews-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h2 fw-bold mb-3">What Our Clients Say</h2>
            <p class="lead text-muted">Hear directly from our satisfied customers</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['last_reviews']->value, 's');
$_smarty_tpl->tpl_vars['s']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['s']->value) {
$_smarty_tpl->tpl_vars['s']->do_else = false;
?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="avatar mx-auto mb-2" style="width: 64px; height: 64px; background-color: var(--bs-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <?php echo mb_strtoupper((string) substr((string) $_smarty_tpl->tpl_vars['s']->value['uname'], (int) 0, (int) 1) ?? '', 'UTF-8');?>

                            </div>
                            <h3 class="card-title h5 mb-1"><?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['s']->value['uname'], ENT_QUOTES, 'UTF-8', true);?>
</h3>
                            <small class="text-muted"><?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'time_elapsed_string' ][ 0 ], array( $_smarty_tpl->tpl_vars['s']->value['datetime'] ));?>
</small>
                        </div>
                        <p class="card-text fst-italic">"<?php echo htmlentities(mb_convert_encoding((string)$_smarty_tpl->tpl_vars['s']->value['review'], 'UTF-8', 'UTF-8'), ENT_QUOTES, 'UTF-8', true);?>
"</p>
                    </div>
                </div>
            </div>
            <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </div>

        <div class="text-center mt-5">
            <a href="reviews" class="btn btn-primary btn-lg">View More Reviews</a>
        </div>
    </div>
</section>
<?php }?>
<section class="affiliate-section py-5" aria-labelledby="affiliate-heading">
    <div class="container">
        <div class="text-center mb-5">
            <h2 id="affiliate-heading" class="h2 fw-bold mb-3">Affiliate Program</h2>
            <p class="lead text-muted">
                <?php echo $_smarty_tpl->tpl_vars['settings']->value['referral']['levels'];?>
 Level Affiliate Program with Transparent Tiers
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
                        <?php
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable(null, $_smarty_tpl->isRenderingCache);$_smarty_tpl->tpl_vars['i']->step = 1;$_smarty_tpl->tpl_vars['i']->total = (int) ceil(($_smarty_tpl->tpl_vars['i']->step > 0 ? $_smarty_tpl->tpl_vars['settings']->value['referral']['levels']+1 - (1) : 1-($_smarty_tpl->tpl_vars['settings']->value['referral']['levels'])+1)/abs($_smarty_tpl->tpl_vars['i']->step));
if ($_smarty_tpl->tpl_vars['i']->total > 0) {
for ($_smarty_tpl->tpl_vars['i']->value = 1, $_smarty_tpl->tpl_vars['i']->iteration = 1;$_smarty_tpl->tpl_vars['i']->iteration <= $_smarty_tpl->tpl_vars['i']->total;$_smarty_tpl->tpl_vars['i']->value += $_smarty_tpl->tpl_vars['i']->step, $_smarty_tpl->tpl_vars['i']->iteration++) {
$_smarty_tpl->tpl_vars['i']->first = $_smarty_tpl->tpl_vars['i']->iteration === 1;$_smarty_tpl->tpl_vars['i']->last = $_smarty_tpl->tpl_vars['i']->iteration === $_smarty_tpl->tpl_vars['i']->total;?>
                        <th scope="col" class="py-3">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Level <?php echo $_smarty_tpl->tpl_vars['i']->value;?>
</span>
                                <i class="bi bi-diagram-3 text-muted"></i>
                            </div>
                        </th>
                        <?php }
}
?>
                        <?php if ($_smarty_tpl->tpl_vars['settings']->value['referral']['system'] == 'ranges') {?>
                        <th scope="col" class="py-3">
                            <div class="d-flex align-items-center">
                                <span class="me-2">Required Investment</span>
                                <i class="bi bi-cash-stack text-muted"></i>
                            </div>
                        </th>
                        <?php }?>
                    </tr>
                </thead>
                <tbody>
                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['settings']->value['referral']['tier'], 'tier', false, 'tierId');
$_smarty_tpl->tpl_vars['tier']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['tierId']->value => $_smarty_tpl->tpl_vars['tier']->value) {
$_smarty_tpl->tpl_vars['tier']->do_else = false;
?>
                    <tr class="tier-row" tabindex="0">
                        <td class="py-3">
                            <span class="badge bg-primary rounded-pill"><?php echo $_smarty_tpl->tpl_vars['tierId']->value;?>
</span>
                        </td>
                        <td class="py-3 fw-medium"><?php echo $_smarty_tpl->tpl_vars['tier']->value['name'];?>
</td>
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['tier']->value['level'], 'value');
$_smarty_tpl->tpl_vars['value']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['value']->value) {
$_smarty_tpl->tpl_vars['value']->do_else = false;
?>
                        <td class="py-3">
                            <span class="text-success fw-medium"><?php echo $_smarty_tpl->tpl_vars['value']->value;?>
%</span>
                        </td>
                        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                        <?php if ($_smarty_tpl->tpl_vars['settings']->value['referral']['system'] == 'ranges') {?>
                        <td class="py-3"><?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'fiat' ][ 0 ], array( $_smarty_tpl->tpl_vars['tier']->value['invested'] ));?>
</td>
                        <?php }?>
                    </tr>
                    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php if ($_smarty_tpl->tpl_vars['games']->value) {?>        <section>
        <div class="container my-5">
            <h2 class="display-6 text-center mb-2 pt-5">Games </h2>
            <div class="row">
                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['games']->value, 's');
$_smarty_tpl->tpl_vars['s']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['s']->value) {
$_smarty_tpl->tpl_vars['s']->do_else = false;
?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="https://via.placeholder.com/50" class="rounded-circle mx-auto mt-3" alt="Client Image">
                        <div class="card-body text-center">
                            <h3 class="card-title"><?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['s']->value['uname'], ENT_QUOTES, 'UTF-8', true);?>
</h3>
                            <h6><?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'time_elapsed_string' ][ 0 ], array( $_smarty_tpl->tpl_vars['s']->value['datetime'] ));?>
</h6>
                            <p class="card-text">"<?php echo htmlentities(mb_convert_encoding((string)$_smarty_tpl->tpl_vars['s']->value['review'], 'UTF-8', 'UTF-8'), ENT_QUOTES, 'UTF-8', true);?>
"</p>
                        </div>
                    </div>
                </div>
                <?php
}
if ($_smarty_tpl->tpl_vars['s']->do_else) {
?>
                <div class="col">
                    <div class="alert alert-info text-center">
                        No Reviews are posted yet.
                    </div>
                </div>
                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
            </div>
            <div class="text-center">
                <a href="reviews" class="btn btn-primary">View More</a>
            </div>
        </div>
    </section>
    <?php }?>
<section class="payment-methods-section bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h2 fw-bold">Available Currencies</h2>
            <p class="lead text-muted">We support multiple payment methods for your convenience</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php
$__section_p_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['ps']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_p_0_total = $__section_p_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_p'] = new Smarty_Variable(array());
if ($__section_p_0_total !== 0) {
for ($__section_p_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_p']->value['index'] = 0; $__section_p_0_iteration <= $__section_p_0_total; $__section_p_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_p']->value['index']++){
?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center">
                <div class="d-flex flex-column align-items-center">
                    <div class="mb-2 p-3 bg-white rounded-circle shadow-sm" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <img 
                            src="images/icons/<?php echo $_smarty_tpl->tpl_vars['ps']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_p']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_p']->value['index'] : null)]['id'];?>
.svg" 
                            class="img-fluid" 
                            width="50" 
                            height="50" 
                            alt="<?php echo $_smarty_tpl->tpl_vars['ps']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_p']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_p']->value['index'] : null)]['name'];?>
 payment method logo"
                        />
                    </div>
                    <span class="text-muted fw-medium"><?php echo $_smarty_tpl->tpl_vars['ps']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_p']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_p']->value['index'] : null)]['name'];?>
</span>
                </div>
            </div>
            <?php
}
}
?>
        </div>
    </div>
</section>

<?php $_smarty_tpl->_subTemplateRender("file:footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
