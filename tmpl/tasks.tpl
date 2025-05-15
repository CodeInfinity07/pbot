{include file="header.tpl"}

{foreach from=$tasks key=key item=value}


<div class="col-md-6">
    <div class="card">
        <div class="card-header">{$value.name}</div>
        <div class="card-body">
            <p>{$value.content}</p>
            <hr>
            <h5>Reward</h5>
         <div class="d-flex bd-highlight mb-3">
  <div class="me-auto p-2 bd-highlight">From: {$value.amount_min|fiat}</div>
  <div class="p-2 bd-highlight">To: {$value.amount_max|fiat}</div>
</div></div>
        <div class="card-footer">
 <form method=post action="{$settings.link.apply|default:'apply'}?id={$value.id}">          
<button class="btn btn-primary" type="submit">Apply</button>
</form>
        </div>
    
 

</div>
</div>

{/foreach}