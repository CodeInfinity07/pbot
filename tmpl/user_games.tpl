{include file="header.tpl"}
<div class="container-fluid vh-100">

      {if $alert}
      <div class="{$alert_class}">
         <span>{$alert_message}</span>
      </div>
      {/if}
 
      <div class="col-md-12 text-left">
          <div class="row">
              
{foreach from=$games key=key item=value}

<div class="col-md-6">
    <div class="card">
        <div class="card-header">{$value.name}</div>
        <div class="card-body">
            <p>{$value.instructions}</p>
            <hr>
            <h5>Reward</h5>
         </div>
        <div class="card-footer">
 <form action="{$value.link}">          
<button class="btn btn-primary" type="submit">Play</button>
</form>
</div>

</div>
</div>

{/foreach}
<h3>My Applications</h3>
<table class="table table-sm">
    <thead>
    <tr>
        <td> Task Name</td>
         <td> Result</td>
          <td> Comment</td>
           <td> Status</td>
             <td> DateTime</td>
    </tr>
    </thead>

{foreach from=$gameslog key=key item=value}
<tr>
        <td> {$value.name}</td>
         <td> {$value.result}</td>
          <td> {$value.comment}</td>
           <td> {if $value.status == '0'} Under Review {else} Processed {/if}</td>
            <td> {$value.timestamp}</td>
    </tr>
{foreachelse}
<tr>
    <td> You have not played yet.</td>
</tr>
{/foreach}
</table>
          </div>

{include file="footer.tpl"}
