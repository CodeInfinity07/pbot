{include file="mheader.tpl"}

<h3>Withdraw Principal:</h3><br><br>

{if $confirm}
<form method=post>

<input type=hidden name=action value=release_deposit>
<input type=hidden name=amount value={$amount}>
<input type=hidden name=id value={$investment['id']}>

<table cellspacing=0 cellpadding=2 border=0>
<tr>
 <th>Deposit Amount</th>
 <td>{$amount}</td>
</tr>
<tr>
 <th>Deposit Plan</th>
 <td>{$package['name']}</td>
</tr>
<tr>
 <th>Release Amount</th>
 <td>{$amount}</td>
</tr>
<tr>
 <th>Fee</th>
 <td>{$fee} ({$feep}%)</td>
</tr>
<tr>
 <th>Credit Amount</th>
 <td><b>{$credit}</b></td>
</tr>
<tr>
 <td><br><input type=submit value="Confirm" name="submit" class=sbmt></td>
</tr></table>
</form>

{else}

<script>
var max_amount = new Number('{$amount}');
var percent = new Number('{$feep}');
var currency_pow = {$settings['round']};

{literal}
function withdraw() {
  if (!document.withdraw_form.amount) return;
  var out_val = new Number(document.withdraw_form.amount.value.replace(",","."));
  if (isNaN(out_val))
  { out_val = 0; }
  out_val = out_val;

  if (out_val > max_amount) {
    out_val = max_amount;
    document.withdraw_form.amount.value = out_val.toFixed(currency_pow);
  }

  if (out_val < 0) {
    document.withdraw_form.amount.value = '';
    document.withdraw_form.quote.value = 0;
  } else {
    var fee = out_val * (percent/ 100);
    if (fee <= 0) fee = 0;
    out_val = out_val - fee;
    if (out_val < 0) out_val = 0;
    document.withdraw_form.quote.value = out_val.toFixed(currency_pow);
  }
}
{/literal}
</script>

<form method=post name=withdraw_form>
<input type=hidden name=action value=confirm_release>
<input type=hidden name=id value={$investment['id']}>

<table cellspacing=0 cellpadding=2 border=0>
<tr>
 <th>Deposit Amount</th>
 <td>{$amount|amount_format}</td>
</tr>
<tr>
 <th>Deposit Earned</th>
 <td>{$earned|amount_format}</td>
</tr>
<tr>
 <th>Deposit Plan</th>
 <td>{$package['name']}</td>
</tr>
<tr>
 <th>Fee</th>
 <td>{$feep}%</td>
</tr>
<tr>
 <th>Release Amount:</td>
 <td> <input type=text name=amount value="{$amount}" class=inpts size=15 onkeyup="withdraw()"></td>
</tr>
<tr>
 <th>Receive Amount:</th>
 <td> <input type=text name=quote readonly class=inpts size=15></td>
</tr>
<tr>
 <td>&nbsp;</td>
 <td><input type=submit value="Release Deposit" name=submit class=sbmt></td>
</tr></table>
</form>

<script>
withdraw();
</script>
{/if}


{include file="mfooter.tpl"}