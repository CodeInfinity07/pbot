<!DOCTYPE html>
<html>
<head>
    <title>{$settings.site_name}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
         <link rel="shortcut icon" href="bitders/assets/logo.svg" />
    <link rel="apple-touch-icon-precomposed" href="bitders/assets/logo.svg" />
   
<body>
<style>
.loading-container {
    text-align: center;
    padding: 20px;
}
.loading-container img {
    max-width: 100px;
}
#error {
    text-align: center;
    padding: 20px;
    color: #cf1322;
}
</style>

<div id="error"></div>
<div id="loading" class="loading-container">
    <img src="/bitders/assets/logo.svg" />
</div>

{literal}
<script>
const TG_APP = {
    processAuth() {
        const webapp = window.Telegram.WebApp;
        webapp.expand();
        webapp.setHeaderColor('#11150f');
        webapp.setBackgroundColor('#11150f');
        
        const urlParams = new URLSearchParams(webapp.initData);
        const hash = urlParams.get("hash");
        urlParams.delete("hash");
        urlParams.sort();

        fetch('oauth', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                dataCheckString: Array.from(urlParams.entries())
                    .map(([key, value]) => `${key}=${value}`)
                    .join('\n'),
                hash,
                platform: webapp.platform
            }),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success === false) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').innerText = data.message || 'Authentication failed';
                return;
            }
            
            if (data.result && data.redirect) {
                webapp.HapticFeedback.impactOccurred('medium');
                window.location.href = data.redirect;
            } else {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').innerText = 'Authentication failed';
            }
        })
        .catch(error => {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('error').innerText = error.message || 'Network error occurred';
        });
    }
};

document.addEventListener('DOMContentLoaded', () => TG_APP.processAuth());
</script>
{/literal}
</body>
</html>
