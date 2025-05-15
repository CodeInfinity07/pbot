{include file="mheader.tpl"}

<h3>Withdraw Cancel</h3><br><br>

<form method=post name=>
<input type=hidden name=action value=cancel_withdraw>
<table class="table table-bordered table-striped">
<tr>
 <th>Withdraw Amount</th>
 <td>{$withdraw|fiat}</td>
</tr>
<tr>
 <th>Currency</th>
 <td>{$currency}</td>
</tr>
<tr>
 <th>Credit</th>
 <td>{$credit|fiat}</td>
</tr>

<tr>
 <td>&nbsp;</td>
 <td><input type=submit value="Cancel Withdraw" name=submit class=sbmt></td>
</tr></table>
</form>

{include file="mfooter.tpl"}