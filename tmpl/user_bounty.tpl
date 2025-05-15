{include file="header.tpl"}
<section class="py-5 text-center container">
    <div class="row py-lg-5">
      <div class="col-lg-6 col-md-8 mx-auto">
        <h1 class="fw-light">Bounty</h1>
        <p class="lead text-muted">Do simple tasks to win bounty prizes.</p>
        <p>
          <a href="news" class="btn btn-primary my-2">See our News</a>
          <a href="contact" class="btn btn-secondary my-2">Contact Support</a>
        </p>
      </div>
    </div>
  </section>
<div class="container ">

      {if $alert}
      <div class="{$alert_class}">
         <span>{$alert_message}</span>
      </div>
      {/if}
 
      <div class="col-md-12 text-left">
          <div class="row">
              
{foreach from=$bounty key=key item=value}


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
 {if $userinfo.logged == 1}
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

{foreach from=$applications key=key item=value}
<tr>
        <td> {$value.name}</td>
         <td> {$value.result}</td>
          <td> {$value.comment}</td>
           <td> {if $value.status == '0'} Under Review {else} Processed {/if}</td>
            <td> {$value.timestamp}</td>
    </tr>
{foreachelse}
<tr>
    <td> You have not applied yet.</td>
</tr>
{/foreach}
</table>
{/if}
          </div>

{include file="footer.tpl"}
