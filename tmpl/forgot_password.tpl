{include file="header.tpl"}

<div class="container py-5 mt-5">
        <div class="row d-flex justify-content-center align-items-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card border-dark" style="border-radius: 1rem;">
                    <div class="card-body p-5 ">
                        <div class="mb-md-5 mt-md-4 pb-5">
                            {if $alert}
                            <div class="{$alert_class}">
                                <span>{$alert_message}</span>
                            </div>
                            {/if}
                            
                    {if $code}
                    <h2 class="text-center mb-4 fw-bold">Confirm Your Registration</h2>
                    <p class="text-center text-muted mb-4">Enter the 6-digit code sent to your email</p>
                    
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="email_code" class="form-label">Email Verification Code</label>
                                <div class="input-group">
                                    <input type="number" id="email_code" name="email_code" 
                                           class="form-control" 
                                           placeholder="Enter 6-digit code" 
                                           required 
                                           maxlength="6">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            id="otp_button" 
                                            onclick="getotp()">
                                        Resend
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" name="password" 
                                           class="form-control" 
                                           placeholder="8+ characters required" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                                <div class="form-text">Must be at least 8 characters long</div>
                            </div>

                            <div class="col-12">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="form-control" 
                                           placeholder="Confirm password" 
                                           required>
                                </div>
                            </div>

                            <div class="col-12">
                                <button class="btn btn-primary w-100" type="submit" name="confirm">
                                    Confirm
                                </button>
                            </div>
                        </div>
                    </form>

                    {elseif $pass}
                    <h2 class="text-center mb-4 fw-bold">Change Your Password</h2>
                    
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="new_password" name="password" 
                                           class="form-control" 
                                           placeholder="8+ characters required" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                                <div class="form-text">Must be at least 8 characters long</div>
                            </div>

                            <div class="col-12">
                                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="confirm_new_password" name="confirm_password" 
                                           class="form-control" 
                                           placeholder="Confirm password" 
                                           required>
                                </div>
                            </div>

                            <div class="col-12">
                                <button class="btn btn-primary w-100" type="submit" name="change_password">
                                    Change Password
                                </button>
                            </div>
                        </div>
                    </form>

                    {else}
                    <h2 class="text-center mb-4 fw-bold">Recover Access</h2>
                    <p class="text-center text-muted mb-4">Enter the email you used while registering</p>
                    
                    <form action="{$settings.link.recover|default:'recover'}" method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="forget_email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="forget_email" name="forget_email" 
                                           class="form-control" 
                                           placeholder="Enter your registered email" 
                                           required>
                                </div>
                            </div>

                            {include file="captcha.tpl" action="forgot"}

                            <div class="col-12">
                                <button class="btn btn-primary w-100" type="submit" name="submit">
                                    Recover Account
                                </button>
                            </div>
                        </div>
                    </form>
                    {/if}

                    <div class="text-center mt-3">
                        <p class="small text-muted">
                            Don't have an account? 
                            <a href="{$settings.link.register|default:'register'}" class="link-primary">Sign Up</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    function setupPasswordToggle(passwordInputId, toggleButtonId) {
        const passwordInput = document.getElementById(passwordInputId);
        const toggleButton = document.getElementById(toggleButtonId);
        
        toggleButton.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye-fill');
            this.querySelector('i').classList.toggle('bi-eye-slash-fill');
        });
    }

    // Setup password toggle for different scenarios
    {if $code}
    setupPasswordToggle('password', 'togglePassword');
    
    // Password match validation
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    confirmPasswordInput.addEventListener('input', function() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    });
    {/if}

    {if $pass}
    setupPasswordToggle('new_password', 'toggleNewPassword');
    
    // Password match validation
    const newPasswordInput = document.getElementById('new_password');
    const confirmNewPasswordInput = document.getElementById('confirm_new_password');
    
    confirmNewPasswordInput.addEventListener('input', function() {
        if (newPasswordInput.value !== confirmNewPasswordInput.value) {
            confirmNewPasswordInput.setCustomValidity('Passwords do not match');
        } else {
            confirmNewPasswordInput.setCustomValidity('');
        }
    });
    {/if}
});
</script>

{include file="footer.tpl"}


               