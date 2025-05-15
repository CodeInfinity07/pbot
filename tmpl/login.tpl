{include file="header.tpl"}

<div class="container py-5 mt-5">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-6 col-lg-5">
                          {if $alert}
            <div class="{$alert_class}">
                <span>{$alert_message}</span>
            </div>
            {/if}
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4 fw-bold">Login to Your Account</h3>
                    
                    <form action="{$settings.link.login|default:'login'}" method="post">
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
                                <a href="{$settings.link.register|default:'recover'}" class="link-primary small">Forgot password?</a>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" name="login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="text-muted">Or continue with:</p>
                        <div class="d-grid gap-2">
                            {if $settings.site.loginwithtelegram}
                            <a href="login?telegram" class="btn btn-outline-primary">
                                <i class="bi bi-telegram me-2"></i>Login with Telegram
                            </a>
                            {/if}
                            
                            {if $settings.site.loginwithgoogle}
                            <a href="login?google" class="btn btn-outline-danger">
                                <i class="bi bi-google me-2"></i>Login with Google
                            </a>
                            {/if}
                            
                            {if $settings.site.loginwithmetamask}
                            <button class="btn btn-outline-warning" onclick="userLoginOut()">
                                <i class="bi bi-wallet2 me-2"></i>Login with MetaMask
                            </button>
                            {/if}
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

<script>
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
</script>

{if $settings.site.loginwithtelegram}
<script async src="https://telegram.org/js/telegram-widget.js?22" 
        data-telegram-login="{$settings.site.telegram.username}" 
        data-size="large" 
        data-auth-url="https://beta.bitders.com/login" 
        data-request-access="write">
</script>
{/if}

{include file="footer.tpl"}