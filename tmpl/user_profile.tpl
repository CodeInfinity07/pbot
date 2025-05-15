{include file="mheader.tpl"}
    <div class="row content">
 {if $alert}
    <div class="{$alert_class}">
        <span>{$alert_message}</span>
    </div>
    {/if}
    
    {if $alert}
<script>
    var alertMessage = '{$alert_message|escape:"js"}';
    Telegram.WebApp.showAlert(alertMessage);
</script>
{/if}
        <div class="col-12 text-left">
            <div class="card my-4">
                <h5 class="card-header ">Profile</h5>
                <form method="POST">
                    <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Username</td>
                            <td>{$userinfo.username}</td>
                        </tr>
                        <tr>
                            <td>Registration date:</td>
                            <td>{$userinfo.created_at}</td>
                        </tr>
                        <tr>
                            <td>Sponsor</td>
                            <td>{$userinfo.sponsor|default:" No Sponsor "}</td>
                        </tr>
                        <tr>
                            <td>Fullname</td>
                            <td><input type="text" name="fullname" value="{$userinfo.fullname}" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><input type="text" name="email" value="{$userinfo.email}" {if !($settings.user.can_change_email)} readonly {/if} class="form-control form-control-sm"></td>
                        </tr>
                        {if $settings.register.questionanswer}
                         <tr>
                        <td>Question</td>
                            <td>
                                <input type="text" class="form-control" name="question" placeholder="Enter Security Question" value="{$userinfo.question}"/>
                            </td>
                        </tr>
                         <tr>
                            <td>Answer</td>
                            <td>
                                <input type="text" class="form-control" name="answer" placeholder="Enter Security Answer" value="{$userinfo.answer}"/>
                            </td>
                        </tr>
                         {/if}
                         {if $settings.register.phone}
                        <tr>
                            <td>Phone Number</td>
                            <td> <input type="text" name="phone" value="{$userinfo.phone}" class="form-control form-control-sm"></td>
                        </tr>
                         {/if}
                         {if $settings.register.location}
                        <tr>
                            <td>Address</td>
                            <td><input type="text" name="address" value="{$userinfo.address}" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>City</td>
                            <td><input type="text" name="city" value="{$userinfo.city}" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>State</td>
                            <td><input type="text" name="state" value="{$userinfo.state}" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>Zip</td>
                            <td><input type="text" name="zip" value="{$userinfo.zip}" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>Country</td>
                            <td><select name=country  class="form-select"> 
                            <option value="">--SELECT--</option>
                            {foreach from=$countries item=c}
                            <option {if $c.name == $userinfo.country}selected{/if}>{$c.name|escape:html}</option>
                            {/foreach}
                         </select></td>
                        </tr>
                         {/if}
                         {if (!$settings.user.wallets)}
                          {foreach from=$ps item=$pm}
                        <tr>
                            <td>{{$pm.name}} Wallet Address</td>
                            <td><input type="text" id="{{$pm.field}}" value="{{$userinfo.wallets[$pm.field]}}" {if !($settings.user.can_change_wallet_acc) && ($userinfo.wallets[$pm.field])} readonly {/if} class="form-control form-control-sm" placeholder="" name="payment[{{$pm.field}}]"></td>
                        </tr>
                        {if ($pm.name == 'Ripple')}
                        <tr>
                            <td>{{$pm.name}} Wallet Tag</td>
                            <td> <input type="text" id="ripple_tag" value="{{$userinfo.wallets.ripple_tag}}" {if !($settings.user.can_change_wallet_acc) && ($userinfo.wallets.ripple_tag)} readonly {/if} class="form-control form-control-sm" placeholder="" name="payment[ripple_tag]"></td>
                        </tr>
                        {/if}
                        {/foreach}
                        {/if}
                       
                        <tr>
                            <td>New Password</td>
                            <td><input type="password" name="password[password]" value="" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>Retype Password</td>
                            <td><input type="password" name="password[rpassword]" value="" class="form-control form-control-sm"></td>
                        </tr>
                        {if $settings.register.pin_code}
                    
                                 
                        <tr>
                            <td>New Transaction Code</td>
                            <td><input type="password" name="transaction[code]" value="" class="form-control form-control-sm"></td>
                        </tr>
                        <tr>
                            <td>Retype Transaction Code </td>
                            <td><input type="password" name="transaction[rcode]" value="" class="form-control form-control-sm"></td>
                        </tr>
                       
                        {/if}
                        {include file="auth.tpl" action="user"}
                    </table>
                       
                    </div>
                    <div class="card-footer text-muted">
                        <input type="submit" value="Update" class="btn btn-primary ml-auto" name="submit">

                    </div>

                </form>

            </div>
  

       

{include file="mfooter.tpl"}
                
       
            
     
