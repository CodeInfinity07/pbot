<?php
/* Smarty version 4.5.2, created on 2025-05-11 19:16:12
  from '/home/assitix/public_html/tmpl/header.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.2',
  'unifunc' => 'content_6820f77c2aa725_14261833',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a5d0a746f0fdffa03df3b52969e417ef5bc9ae2b' => 
    array (
      0 => '/home/assitix/public_html/tmpl/header.tpl',
      1 => 1730887476,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:meta.tpl' => 1,
  ),
),false)) {
function content_6820f77c2aa725_14261833 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="en" >

<head>
    

    
<?php $_smarty_tpl->_subTemplateRender("file:meta.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

   <!-- Bootstrap 5.3 (Latest stable version) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <?php echo '<script'; ?>
 src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"><?php echo '</script'; ?>
>

</head>

<body class="d-flex flex-column min-vh-100">


<?php if ($_smarty_tpl->tpl_vars['userinfo']->value['logged'] == 1) {?>
<nav class="navbar navbar-expand-lg navbar-light fixed-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/">
      <img src="bitders/assets/logo.svg" alt="Logo" width="42" class="d-inline-block">
      <span class="fw-semibold"><?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>
</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <div class="server-time ms-lg-4 d-none d-lg-block">
        <i class="bi bi-clock me-1"></i>
        <?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'datef' ][ 0 ], array( $_smarty_tpl->tpl_vars['settings']->value['server_time'],"F j, Y, g:i a" ));?>

      </div>

      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <!-- Quick Actions -->
        <li class="nav-item d-flex gap-2">
          <a class="action-btn btn btn-primary d-flex align-items-center gap-2" href="invest">
            <i class="bi bi-graph-up"></i> Invest
          </a>
          <a class="action-btn btn btn-outline-primary d-flex align-items-center gap-2" href="withdraw">
            <i class="bi bi-cash"></i> Withdraw
          </a>
        </li>

        <!-- Main Navigation Pills -->
        <li class="nav-item px-2">
          <ul class="nav nav-pills">
            <li class="nav-item d-flex gap-2 flex-wrap">
              <a class="nav-link px-3" href="dashboard">
                <i class="bi bi-grid-1x2"></i> Dashboard
              </a>
            </li>
          </ul>
        </li>

        <!-- Actions Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-gear"></i> Actions
          </a>
          <div class="dropdown-menu dropdown-menu-end">
            <div class="p-3">
              <h6 class="dropdown-header text-uppercase fw-semibold mb-2">Quick Actions</h6>
              <div class="d-grid gap-2">
                <a class="dropdown-item rounded d-flex align-items-center gap-2" href="invested">
                  <i class="bi bi-bar-chart-fill text-primary"></i> Active Investments
                </a>
                <?php if ($_smarty_tpl->tpl_vars['settings']->value['deposit']['topup']) {?>
                <a class="dropdown-item rounded d-flex align-items-center gap-2" href="deposit">
                  <i class="bi bi-wallet2 text-primary"></i> Deposit
                </a>
                <?php }?>
              </div>
              <div class="dropdown-divider my-3"></div>
              <h6 class="dropdown-header text-uppercase fw-semibold mb-2">Account</h6>
              <div class="d-grid gap-2">
                <a class="dropdown-item rounded d-flex align-items-center gap-2" href="profile">
                  <i class="bi bi-person text-primary"></i> Profile
                </a>
                <?php if ($_smarty_tpl->tpl_vars['settings']->value['user']['2fa']) {?>
                <a class="dropdown-item rounded d-flex align-items-center gap-2" href="2fa">
                  <i class="bi bi-shield-lock text-primary"></i> 2FA Security
                </a>
                <?php }?>
              </div>
            </div>
          </div>
        </li>

        <!-- User Menu -->
        <li class="nav-item dropdown ms-2">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
              <i class="bi bi-person"></i>
            </div>
          </a>
          <div class="dropdown-menu dropdown-menu-end">
            <div class="px-4 py-3">
              <div class="d-flex gap-3 align-items-center mb-3">
                <div class="avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                  <i class="bi bi-person"></i>
                </div>
                <div>
                  <h6 class="mb-0 fw-semibold">Account</h6>
                  <small class="text-muted">Manage your account</small>
                </div>
              </div>
              <div class="d-grid gap-2">
                <a class="dropdown-item rounded" href="profile">Profile Settings</a>
                <a class="dropdown-item rounded" href="support">Support</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item rounded text-danger d-flex align-items-center gap-2" href="logout" onclick="Telegram.WebApp.close();">
                  <i class="bi bi-box-arrow-right"></i> Logout
                </a>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Spacer for fixed navbar -->
<div style="height: 80px;"></div>

<!-- Breadcrumb -->
<div class="bg-light shadow-sm">
  <div class="container py-2">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="#" class="text-decoration-none text-primary"><?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>
</a>
        </li>
        <li class="breadcrumb-item active"><?php echo $_smarty_tpl->tpl_vars['pagename']->value;?>
</li>
      </ol>
    </nav>
  </div>
</div>

<?php } else { ?>
<!-- Similar styling for non-logged-in state -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top py-2 bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/">
      <img src="bitders/assets/logo.svg" alt="Logo" width="42" class="d-inline-block">
      <span class="fw-semibold"><?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>
</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item d-flex gap-2 flex-wrap">
          <a class="nav-link px-3" href="/">Home</a>
        </li>
        <li class="nav-item d-flex gap-2 flex-wrap">
          <a class="nav-link px-3" href="faqs">FAQs</a>
        </li>
        <li class="nav-item d-flex gap-2 flex-wrap">
          <a class="nav-link px-3" href="news">News</a>
        </li>
        <li class="nav-item d-flex gap-2 flex-wrap">
          <a class="nav-link px-3" href="contact">Contact</a>
        </li>
      </ul>
      
      <div class="d-flex gap-2">
        <a href="login" class="action-btn btn btn-outline-dark">Login</a>
        <a href="register" class="action-btn btn btn-primary">Register</a>
      </div>
    </div>
  </div>
</nav>
<?php }
}
}
