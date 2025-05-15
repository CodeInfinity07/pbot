{include file="mheader.tpl"}
<div class="card border-0 rounded-4 py-4 mb-4">
    <div class="card-headers border-0 py-4 px-4 pb-0 pt-1">
        <h4><i class="fa-solid fa-lock text-primary me-2"></i>2 Factor Authentication</h4>
    </div>
    {if $alert}
    <div class="{$alert_class}">
        <span>{$alert_message}</span>
    </div>
    {/if}


    <div class="card-body px-4">
        {if !$userinfo.2fa}
        <form method="POST" id="security">
            <div class="card-body">
                <p class="card-text">1. Install <a href="https://m.google.com/authenticator">Google Authenticator</a> on your mobile device</p>
                <label><img src="{{$qr_url}}" style="height: 200px;" /></label>
                <p class="card-text">2. Your Secret Code: {{$secret}}</p>
                <p class="card-text">3. Scan and two factor token from Google Authenticator to verify correct setup</p>
                <input type="hidden" name="secret" value="{{$secret}}" />

                <div class="form-group">
                    <input type="text" name="code" class="form-control" placeholder="Code" />
                    <br />
                </div>
            </div>
            <div class="card-footer text-muted">
                <input name="en_2fa" type="submit" class="btn btn-primary me-3" id="en_2fa" value="Enable" />
            </div>
        </form>
        {else}
        <form method="POST" id="security">
            <div class="card-body">
                <p class="card-text">Disable your 2FA Code from your account.</p>
                <div class="form-group">
                    <input type="text" name="code" class="form-control" placeholder="Code" />
                    <br />
                </div>
            </div>
            <div class="card-footer text-muted">
                <input name="dis_2fa" type="submit" class="btn btn-primary me-3" id="dis_2fa" value="Disable" />
            </div>
        </form>
        {/if}
    </div>
</div>

{include file="mfooter.tpl"}
