{include file="header.tpl"}
<div class="d-flex align-items-center justify-content-center vh-100">
    <div class="card w-25">
        <div class="card-header">Enter 2FA to access</div>
        <div class="card-body">
            {if $alert}
            <div class="{$alert_class}">
                <span>{$alert_message}</span>
            </div>
            {/if}
            <form action="tfa" method="post">
          
                <label>Enter 2FA</label>
                <input type="text" class="form-control" name="2fa" placeholder="Enter 6 Digit 2FA" required />

                <a class="link fw-medium text-primary" href="contact">Forgot 2FA?</a>
                {include file="captcha.tpl" action="recover"}
                <button type="submit" class="btn btn-primary w-100 me-5" type="submit" name="submit">Log in</button>
            </form>
        </div>
    </div>
</div>

{include file="footer.tpl"}



                    
            
          

               