{include file="mheader.tpl"}
 {if $settings.user.wallets}
<div class="dash-wrapsw card border-0 rounded-4 py-4 mb-4">
							<div class="card-headers border-0 py-4 px-4 pb-0 pt-1">
								<h4><i class="fa-regular fa-credit-card text-primary me-2"></i>Crypto Wallet Address</h4>
							</div>
							<div class="card-body px-4">
		{if $alert}
<div class="{$alert_class}">
    <span>{$alert_message}</span>
</div>
{/if}
<form method="POST">
    {foreach from=$ps item=$pm}

    <label>{{$pm.name}} Address</label>
    <input type="text" id="{{$pm.field}}" value="{{$userinfo.wallets[$pm.field]}}" {if !($settings.user.can_change_wallet_acc) && ($userinfo.wallets[$pm.field])} readonly {/if} class="form-control" placeholder="" name="{{$pm.field}}">
    {if ($pm.name == 'Ripple')}

    <label>{{$pm.name}} Tag</label>
    <input type="text" id="ripple_tag" value="{{$userinfo.wallets.ripple_tag}}" {if !($settings.user.can_change_wallet_acc) && ($userinfo.wallets.ripple_tag)} readonly {/if} class="form-control" placeholder="" name="ripple_tag"> {/if}
    {/foreach}

    <button class="btn btn-primary me-3" type="submit" name="payment_settings">Save changes</button>
</form>


							</div>
						</div>
						
						
						
                             
    
                      
                                                       
                  
 {/if}
{include file="mfooter.tpl"}

