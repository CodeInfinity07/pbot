{include file="header.tpl"}
<section class="vh-100">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card border-dark" style="border-radius: 1rem;">
                    <div class="card-body p-5 text-center">
                        <div class="mb-md-5 mt-md-4 pb-5">
                            {if $alert}
                            <div class="{$alert_class}">
                                <span>{$alert_message}</span>
                            </div>
                            {/if} {if $code}
                            <h2 class="fw-bold mb-2 text-uppercase">Confirm your registration, please:</h2>
                            <p class="mb-5">Enter your 6 digit code sent to your email</p>
                            <form  method="post">
                            <div class="form-outline mb-2">
                                <label class="form-label">Enter Email Code</label>
                                <input type="number" name="email_code" class="form-control" />
                                <input type="button" class="btn btn-sm btn-primary" value="send" id="otp_button" onclick="getotp()" />
                            </div>
                            <button class="btn btn-outline-primary btn-lg px-5" type="submit" name="confirm">Confirm</button>
                            </form>
                            {else}

                            <h3>Registration completed:</h3>
                            <br />

                            Thank you for joining our program.<br />
                            You are now an official member of this program. You can login to your account to start investing with us and use all the services that are available for our members.
                            <br />
                            <br />

                            <b>Important:</b> Do not provide your login and password to anyone! {/if}
                        </div>

                        <div>
                            <p class="mb-0">Login Now <a href="{$settings.link.login|default:'login'}" class="fw-bold">login</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{include file="footer.tpl"}
{literal}
<script>
    function getotp() {
        var element = document.getElementById("otp_button");
        element.disabled = true;
        setTimeout(function () {
            element.disabled = false;
        }, 60000);
        const xmlhttp = new XMLHttpRequest();
        xmlhttp.onload = function () {
            alert(this.responseText);
        };
        xmlhttp.open("GET", "?send_otp");
        xmlhttp.send();
    }
</script>
{/literal}
