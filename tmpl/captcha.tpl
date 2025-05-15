{if $settings.captcha.captcha_id == '4' && $settings.captcha.page.$action}
<div class="input-group my-3">
  <span class="input-group-text" id="captcha"><img src="captcha_image" /></span>
  <input type="text" class="form-control form-control-sm"name="captcha" placeholder="Enter Captcha" aria-describedby="captcha">
</div>
{elseif $settings.captcha.captcha_id == '1' && $settings.captcha.page.$action}
<div class="g-recaptcha" data-sitekey="{$settings.captcha.google.gsitekey}"></div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
{elseif $settings.captcha.captcha_id == '3' && $settings.captcha.page.$action}
<div class="cf-turnstile" data-sitekey="{$settings.captcha.cloudflare.csitekey}"></div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
{elseif  $settings.captcha.captcha_id == '2' && $settings.captcha.page.$action}
<div class="h-recaptcha" data-sitekey="{$settings.captcha.hcaptch.hsitekey}"></div>
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
{/if}