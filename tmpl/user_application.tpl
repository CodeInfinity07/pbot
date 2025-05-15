{include file="mheader.tpl"}
<div class="container-fluid vh-100">

      {if $alert}
      <div class="{$alert_class}">
         <span>{$alert_message}</span>
      </div>
      {/if}
 <form method=post>
     <input type=hidden name=action value=apply>
      <div class="col-md-12 text-left">
            <div class="card">
        <div class="card-header">{$name}</div>
        <div class="card-body">
            <p>{$content}</p>
            <hr>
            <h5>Reward</h5>
         <div class="d-flex bd-highlight mb-3">
  <div class="me-auto p-2 bd-highlight">From: {$amount_min|fiat}</div>
  <div class="p-2 bd-highlight">To: {$amount_max|fiat}</div>
</div>
<hr>
{$instructions}
<label class="form-label">Enter the URL</label>
<input type="text" value="" name="result" class="form-control" required="">
<label class="form-label">Enter Comment for Admin.</label>
<input type="text" value="" name="comment" class="form-control">
</div>
        <div class="card-footer">
           
<button class="btn btn-primary" name=submit type="submit">Apply</button>
        </div>

</div>
          </div>
          </form>
</div>          
          
{include file="mfooter.tpl"}