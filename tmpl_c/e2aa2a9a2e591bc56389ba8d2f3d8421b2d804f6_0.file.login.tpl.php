<?php
/* Smarty version 4.5.2, created on 2025-05-11 19:16:12
  from '/home/assitix/public_html/tmpl/login.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.2',
  'unifunc' => 'content_6820f77c29f0a9_34591219',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'e2aa2a9a2e591bc56389ba8d2f3d8421b2d804f6' => 
    array (
      0 => '/home/assitix/public_html/tmpl/login.tpl',
      1 => 1730887576,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:header.tpl' => 1,
    'file:footer.tpl' => 1,
  ),
),false)) {
function content_6820f77c29f0a9_34591219 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-6 col-lg-5">
                          <?php if ($_smarty_tpl->tpl_vars['alert']->value) {?>
            <div class="<?php echo $_smarty_tpl->tpl_vars['alert_class']->value;?>
">
                <span><?php echo $_smarty_tpl->tpl_vars['alert_message']->value;?>
</span>
            </div>
            <?php }?>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4 fw-bold">Login to Your Account</h3>
                    
                    <form action="<?php echo (($tmp = $_smarty_tpl->tpl_vars['settings']->value['link']['login'] ?? null)===null||$tmp==='' ? 'login' ?? null : $tmp);?>
" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">User ID</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Enter your ID or email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                            <div class="text-end mt-2">
                                <a href="<?php echo (($tmp = $_smarty_tpl->tpl_vars['settings']->value['link']['register'] ?? null)===null||$tmp==='' ? 'recover' ?? null : $tmp);?>
" class="link-primary small">Forgot password?</a>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" name="login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="text-muted">Or continue with:</p>
                        <div class="d-grid gap-2">
                            <?php if ($_smarty_tpl->tpl_vars['settings']->value['site']['loginwithtelegram']) {?>
                            <a href="login?telegram" class="btn btn-outline-primary">
                                <i class="bi bi-telegram me-2"></i>Login with Telegram
                            </a>
                            <?php }?>
                            
                            <?php if ($_smarty_tpl->tpl_vars['settings']->value['site']['loginwithgoogle']) {?>
                            <a href="login?google" class="btn btn-outline-danger">
                                <i class="bi bi-google me-2"></i>Login with Google
                            </a>
                            <?php }?>
                            
                            <?php if ($_smarty_tpl->tpl_vars['settings']->value['site']['loginwithmetamask']) {?>
                            <button class="btn btn-outline-warning" onclick="userLoginOut()">
                                <i class="bi bi-wallet2 me-2"></i>Login with MetaMask
                            </button>
                            <?php }?>
                        </div>
                        
                        <div class="mt-3 small text-muted">
                            Don't have an account? 
                            <a href="register" class="link-primary">Sign up</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo '<script'; ?>
>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye-fill');
        this.querySelector('i').classList.toggle('bi-eye-slash-fill');
    });
});
<?php echo '</script'; ?>
>

<?php if ($_smarty_tpl->tpl_vars['settings']->value['site']['loginwithtelegram']) {
echo '<script'; ?>
 async src="https://telegram.org/js/telegram-widget.js?22" 
        data-telegram-login="<?php echo $_smarty_tpl->tpl_vars['settings']->value['site']['telegram']['username'];?>
" 
        data-size="large" 
        data-auth-url="https://beta.bitders.com/login" 
        data-request-access="write">
<?php echo '</script'; ?>
>
<?php }?>

<?php $_smarty_tpl->_subTemplateRender("file:footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
