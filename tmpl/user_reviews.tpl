{include file="header.tpl"}
<section class="py-5 text-center container">
    <div class="row py-lg-5">
      <div class="col-lg-6 col-md-8 mx-auto">
        <h1 class="fw-light">See what our Clients Says</h1>
        <p class="lead text-muted">Testimonials and reviews of {$settings.site_name}.</p>
        <p>
          <a href="news" class="btn btn-primary my-2">See our News</a>
          <a href="contact" class="btn btn-secondary my-2">Contact Support</a>
        </p>
      </div>
    </div>
  </section>
 <div class="container"> 
 {if $userinfo.logged == 1}

 {if $alert}
                        <div class="{$alert_class}">
                            <span>{$alert_message}</span>
                        </div>
                        {/if}
<form method=post>
<h3>Leave Review:</h3>

<table cellspacing=0 cellpadding=2 border=0 width=100%>
<tr>
 <td>Your name:</td>
 <td><input type=text name=uname value="{$frm.uname|default:$userinfo.username|escape:htmlall}" class=inpts></td>
</tr>
<tr>
 <td>Your review:</td>
 <td><textarea name=review class=inpts style="width:100%">{$frm.review|escape:html}</textarea></td>
</tr>
<tr>
 <td>&nbsp;</td>
 <td><input type=submit class=submit name="submit" value="Leave Review"> </td>
</tr>
</table>
</form>





<h3>My Reviews</h3>


<table cellspacing=1 cellpadding=2 border=0 width=100%>

{foreach from=$reviews key=key item=value}
<tr>
 <td align=justify><i>{$value.review|escape:htmlall}</i><br>
  {$value.uname|escape:html} - {$value.datetime} - {$value.status}<br>
 </td>
</tr>
{foreachelse}
<tr>
 <td colspan=3 align=center>No reviews found</td>
</tr>
{/foreach}
</table>
{/if}

<h3>All Reviews </h3>
<table cellspacing=1 cellpadding=2 border=0 width=100%>
{foreach from=$last_reviews item=s}
                    <tr>
                         <td>{$s.review}</td>
                    <td>{$s.datetime|time_elapsed_string}</td>
                        <td>{$s.uname}</td>

                    </tr>
{/foreach}
</table>

</div>


{include file="footer.tpl"}
