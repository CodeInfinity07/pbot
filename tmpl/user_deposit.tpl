{include file="mheader.tpl"}


        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Top Up Your Account</h3>
                    </div>
                    <div class="card-body">
                        {if $alert}
                        <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
                            {$alert_message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        {/if}

                        <form method="post" name="spendform" action="deposit">
                            <input type="hidden" name="action" value="deposit_request">
                            <input type="hidden" name="exchange" id="exchange" value="1">

                            <div class="mb-4">
                                <label class="form-label fw-bold">Payment Method</label>
                                <div class="row g-3">
                                    {section name=p loop=$ps}
                                        {if $ps[p].status}
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method_id" value='{$ps[p].id}' {if ($ps[p].id == 13)} checked {/if} id="card-{$ps[p].id}">
                                                <label class="form-check-label d-flex align-items-center" for="card-{$ps[p].id}">
                                                    <img src="images/icons/{$ps[p].id}.svg" alt="{$ps[p].name}" width="24" height="24" class="me-2">
                                                    <span>{$ps[p].name}</span>
                                                </label>
                                            </div>
                                        </div>
                                        {/if}
                                    {/section}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold" for="amount">Amount in USD</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input class="form-control" type="text" id="amount" name="amount" onkeyup="calcu()" placeholder="Enter amount" required>
                                </div>
                            </div>

                            <div class="text-center">
                                <button class="btn btn-primary btn-lg px-5" type="submit" name="submit" value="submit">
                                    <i class="bi bi-credit-card me-2"></i>Make Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
 

{include file="mfooter.tpl"}