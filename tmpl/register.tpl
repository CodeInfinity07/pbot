{include file="header.tpl"}
<div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                            {if $alert}
            <div class="{$alert_class}">
                <span>{$alert_message}</span>
            </div>
            {/if}
                <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-3 fw-bold">Create Your Account</h3>
                    <p class="text-center text-muted mb-4">Please fill out the form to register</p>

                    {if !($settings.register.disable)}
                    <form action="{$settings.link.register|default:'register'}" method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="fullname" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="fullname" name="fullname" 
                                           placeholder="Enter your full name" required 
                                           value="{$post.fullname}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Choose a username" required 
                                           value="{$post.username}">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="email@example.com" required 
                                           value="{$post.email}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="8+ characters" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                                <div class="form-text">Must be at least 8 characters long</div>
                            </div>

                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" placeholder="Confirm password" required>
                                </div>
                            </div>
                {if $settings.register.questionanswer}
                <tr>
                    <td>Question</td>
                    <td>
                        <input type="text" class="form-control" name="question" placeholder="Enter Security Question" value="{$post.question}"/>
                    </td>
                </tr>
                 <tr>
                    <td>Answer</td>
                    <td>
                        <input type="text" class="form-control" name="answer" placeholder="Enter Security Answer" value="{$post.answer}"/>
                    </td>
                </tr>
                {/if}
                 {if $settings.register.phone_field}
                            <div class="col-12">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Enter phone number" value="{$post.phone}">
                                </div>
                            </div>
                            {/if} {if $settings.register.location}
                <tr>
                    <td>Address</td>
                    <td><input type="text" name="address" value="{$userinfo.address}" class="form-control form-control-sm" value="{$post.address}"/></td>
                </tr>
                <tr>
                    <td>City</td>
                    <td><input type="text" name="city" value="{$userinfo.city}" class="form-control form-control-sm" value="{$post.city}"/></td>
                </tr>
                <tr>
                    <td>State</td>
                    <td><input type="text" name="state" value="{$userinfo.state}" class="form-control form-control-sm" value="{$post.state}"/></td>
                </tr>
                <tr>
                    <td>Zip</td>
                    <td><input type="text" name="zip" value="{$userinfo.zip}" class="form-control form-control-sm" value="{$post.zip}"/></td>
                </tr>
                <tr>
                    <td>Country</td>
                    <td>
                        <select name="country" class="form-select">
                            <option value="">--SELECT--</option>
                            {foreach from=$countries item=c} 
                            <option {if $c.name == $country}selected{/if}>{$c.name|escape:html}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                {/if} {if $settings.register.wallets} {foreach from=$payment item=$pm}

                <tr>
                    <td>{$pm.name} Address</td>
                    <td>
                        <input type="text" id="{$pm.field}" value="" class="form-control" placeholder="Enter {$pm.name} Address" name="payment[{$pm.field}]" value="{$post.payment[{$pm.field}]}"/>
                    </td>
                </tr>

                {if ($pm.name == 'Ripple')}
                <td>{$pm.name} Tag</td>
                <td>
                    <input type="text" id="ripple_tag" value="" class="form-control" placeholder="Ripple Tag" name="payment[ripple_tag]" value="{$post.payment[ripple_tag]}"/>
                </td>
                {/if} {/foreach} {/if} {if $settings.register.pin_code}
                <tr>
                    <td>Transaction Pin</td>
                    <td>
                        <input type="number" class="form-control mb-0" name="pin_code" placeholder="Enter Transaction Pin" required=""value="{$post.pin_code}" />
                    </td>
                </tr>
                {/if} {if !empty($ref) or $settings.referral.sponsor_must}
                <tr>
                    <td>Your Sponsor</td>
                    <td>
                        <input type="text" class="form-control" placeholder="" {if $ref} readonly {/if} value="{$ref}">
                    </td>
                </tr>
                {/if} <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="terms">Terms and Conditions</a>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100" name="submit">
                                    <i class="bi bi-person-plus me-2"></i>Create Account
                                </button>
                            </div>
                        </div>
                    </form>
                    {/if}

                    <div class="text-center mt-3">
                        <p class="small text-muted">
                            Already have an account? 
                            <a href="login" class="link-primary">Log in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const togglePassword = document.getElementById('togglePassword');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye-fill');
        this.querySelector('i').classList.toggle('bi-eye-slash-fill');
    });

    // Optional: Password match validation
    confirmPasswordInput.addEventListener('input', function() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    });
});
</script>

{include file="footer.tpl"}
