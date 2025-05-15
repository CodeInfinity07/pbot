{if !$settings.$action.confirmation}
{if $settings.$action.pin_code}
<tr>
    <th>Pin Code:</th>
    <td><input type="number" class="form-control mb-0" id="pin_code" name="pin_code" placeholder="Enter Pin Code" required="" /></td>
</tr>
{/if} {if $settings.$action.2fa_code}
<tr>
    <th>2FA Code:</th>
    <td><input type="number" class="form-control mb-0" id="2fa_code" name="2fa_code" placeholder="Enter 2FA Code" required="" /></td>
</tr>
{/if} {if $settings.$action.email_code}
<tr>
    <th>Email Code:</th>
    <td><input type="number" class="form-control mb-0" id="email_code" name="email_code" placeholder="Enter Code sent to your email" required="" /> <input type="button" value="send" id="otp_button" onclick="getotp()"></td>
</tr>
{/if}
{/if}

{literal}
<script>
function getotp() {
var element = document.getElementById("otp_button");
    element.disabled = true;
    setTimeout(function(){  
        element.disabled = false;
    }, 60000);
    const xmlhttp = new XMLHttpRequest();
    xmlhttp.onload = function() {
    	alert(this.responseText);
	}
	xmlhttp.open("GET", "?send_otp");
	xmlhttp.send();
}
</script>
{/literal}