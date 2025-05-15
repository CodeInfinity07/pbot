{include file="mheader.tpl"}

<div class="container py-5">
    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    {if $confirm}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Confirm Transfer</h5>
        </div>
        <div class="card-body">
            <form method="post" name="internal_transfer_confirm" class="validate">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th>Currency:</th>
                            <td>
                                <img src="icons/{$payment_method_id}.svg" alt="{$system}" class="me-2" style="width: 24px;">
                                {$system}
                            </td>
                        </tr>
                        <tr>
                            <th>Recipient:</th>
                            <td>{$transferto}</td>
                        </tr>
                        <tr>
                            <th>Fee:</th>
                            <td>{$settings.symbol} {$fee}</td>
                        </tr>
                        <tr>
                            <th>Debit Amount:</th>
                            <td>{$settings.symbol}{$amount}</td>
                        </tr>
                        <tr>
                            <th>Credit Amount:</th>
                            <td>{$settings.symbol}{$debit}</td>
                        </tr>
                        <tr>
                            <th>Comment:</th>
                            <td>{$comment}</td>
                        </tr>
                    </tbody>
                </table>
                {include file="auth.tpl" action="transfer"}
                <div class="mt-3">
                    <button type="submit" name="submit" class="btn btn-success me-2">Confirm</button>
                    <button type="submit" class="btn btn-warning">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    {else}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Transfer to Other User Account</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Currency</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            {section name=p loop=$ps}
                            {if $ps[p].balance > 0}
                            <tr class="cursor-pointer">
                                <td>
                                    <input type="radio" class="form-check-input" value='{$ps[p].id}' name="payment_method_id" required>
                                </td>
                                <td>
                                    <img src="images/icons/{$ps[p].id}.svg" alt="{$ps[p].name}" class="me-2" style="width: 24px;">
                                    {$ps[p].name}
                                </td>
                                <td>{$ps[p].balance|fiat}</td>
                            </tr>
                            {/if}
                            {/section}
                        </tbody>
                    </table>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Recipient Username</label>
                            <input type="text" class="form-control" required name="transferto" placeholder="Enter recipient's username">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Amount to Send</label>
                            <input type="number" class="form-control" required name="amount" placeholder="Enter amount">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Enter optional comment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="transfer_submit" class="btn btn-primary">Transfer</button>
                </div>
            </form>
        </div>
    </div>
    {/if}
</div>

{include file="mfooter.tpl"}

<script>
$(function() {
    $('tr.cursor-pointer').click(function(event) {
        if (event.target.type !== "radio") {
            var radio = $(this).find('input:radio');
            radio.prop('checked', !radio.prop('checked'));
        }
    });
});
</script>