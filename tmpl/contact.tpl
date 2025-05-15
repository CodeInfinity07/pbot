{include file="header.tpl"}

<div class="container py-5 mt-5">
    <div class="row">
        <div class="col-lg-6 mx-auto text-center mb-5">
            <h1 class="display-5 fw-bold">Contact Support</h1>
            <p class="lead text-muted">Get a quick reply from {$settings.site_name} Support Team</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="faqs" class="btn btn-primary">
                    <i class="bi bi-question-circle me-2"></i>See our FAQs
                </a>
                <a href="contact" class="btn btn-outline-secondary">
                    <i class="bi bi-headset me-2"></i>Contact Support
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7 order-2 order-lg-1">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-envelope-paper me-2"></i>Submit a Support Ticket
                    </h4>
                </div>
                <div class="card-body p-4">
                    {if $alert}
                    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
                        {$alert_message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    {/if}

                    <form method="post" id="contactForm" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Your Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           name="name" 
                                           placeholder="Enter Your Name" 
                                           {if $userinfo.logged == 1} 
                                               value="{$userinfo.username}" 
                                               readonly 
                                           {/if} 
                                           required 
                                           pattern="[A-Za-z\s]+"
                                           title="Letters and spaces only"/>
                                    <div class="invalid-feedback">
                                        Please enter your name (letters only).
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Your Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" 
                                           class="form-control" 
                                           name="email" 
                                           placeholder="Enter your email" 
                                           {if $userinfo.logged == 1} 
                                               value="{$userinfo.email}" 
                                               readonly 
                                           {/if} 
                                           required 
                                           />
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Topic</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-chat-text"></i></span>
                                    <select class="form-select" name="topic" required>
                                        <option value="">Select Support Topic</option>
                                        <option value="billing">Billing Inquiry</option>
                                        <option value="technical">Technical Support</option>
                                        <option value="account">Account Issues</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a support topic.
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Message</label>
                                <textarea 
                                    class="form-control" 
                                    placeholder="Describe your issue in detail..." 
                                    name="message" 
                                    rows="6" 
                                    required 
                                    minlength="20"
                                    maxlength="500"></textarea>
                                <div class="invalid-feedback">
                                    Please provide a detailed message (20-500 characters).
                                </div>
                                <div class="form-text text-muted">
                                    <small>
                                        <span id="charCount">0</span>/500 characters
                                    </small>
                                </div>
                            </div>

                            {include file="captcha.tpl" action="contact"}

                            <div class="col-12">
                                <button 
                                    type="submit" 
                                    class="btn btn-primary w-100" 
                                    name="submit">
                                    <i class="bi bi-send me-2"></i>Submit Support Ticket
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 order-1 order-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Support Information
                    </h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock me-2 text-primary"></i>Response Time</span>
                            <span class="badge bg-primary rounded-pill">24 hrs</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-envelope me-2 text-success"></i>Email Support</span>
                            <span></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-telegram me-2 text-info"></i>Telegram Support</span>
                            <span></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="footer.tpl"}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const messageTextarea = form.querySelector('textarea[name="message"]');
    const charCountSpan = document.getElementById('charCount');

    // Character count
    messageTextarea.addEventListener('input', function() {
        charCountSpan.textContent = this.value.length;
        
        if (this.value.length > 500) {
            this.value = this.value.slice(0, 500);
        }
    });

    // Form validation
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});
</script>

<style>
.card-header {
    background-color: var(--bs-primary);
    color: white;
}
</style>