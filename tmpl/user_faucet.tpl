{include file="mheader.tpl"}

<div class="container py-5">
    {if $alert}
    <div class="alert {$alert_class} alert-dismissible fade show" role="alert">
        {$alert_message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    {/if}

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title">How to Claim Rewards</h5>
            <p class="card-text">All you need to do is wait for the claim timer countdown and click the "Get Reward" button, and that's it!</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-4">
        {foreach from=$faucets item=p}
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Earn {$p.amount|fiat:$p.cid} Free Every {$p.total_limit} {$p.remain_limit}</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Claim Free faucet amount</p>
                    <form method="post">
                        {if $p.remain_time}
                        <div id="timer{$p.id}" class="alert alert-info">
                            Please Wait for <span id="time{$p.id}" class="fw-bold"></span> to Claim Reward
                        </div>
                        <script>
                            var countDown{$p.id} = new Date("{$p.remain_time}").getTime();
                            var time{$p.id}a = setInterval(function() {
                                var now = new Date().getTime();
                                var distance = countDown{$p.id} - now;
                                var hours = Math.floor(distance / (1000 * 60 * 60));
                                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                var s = "";
                                if(hours) {
                                    s = hours + " hours ";
                                }
                                document.getElementById("time{$p.id}").innerHTML = s + minutes + " minutes " + seconds + " seconds ";
                                if (distance < 0) {
                                    clearInterval(time{$p.id}a);
                                    document.getElementById("claimBtn{$p.id}").classList.remove("d-none");
                                    document.getElementById("timer{$p.id}").classList.add("d-none");
                                }
                            }, 1000);
                        </script>
                        <div id="claimBtn{$p.id}" class="d-none">
                            <input type="hidden" name="faucet" value="{$p.id}" />
                            <button type="submit" name="claim_faucet" class="btn btn-success w-100">Claim Rewards</button>
                        </div>
                        {else}
                        <input type="hidden" name="faucet" value="{$p.id}" />
                        <button type="submit" name="claim_faucet" class="btn btn-success w-100">Claim Rewards</button>
                        {/if}
                    </form>
                </div>
            </div>
        </div>
        {/foreach}
    </div>
</div>

{include file="mfooter.tpl"}