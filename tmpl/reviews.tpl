{include file="header.tpl"}
<section class="py-5 text-center container">
    <div class="row py-lg-5">
        <div class="col-lg-6 col-md-8 mx-auto">
            <h1 class="fw-light">Customer Reviews</h1>
            <p class="lead text-muted">See what our valued customers are saying about {$settings.site_name}.</p>
            <p>
                <a href="write-review" class="btn btn-primary my-2">Share Your Experience</a>
                <a href="contact" class="btn btn-secondary my-2">Contact Us</a>
            </p>
        </div>
    </div>
</section>

<div class="container">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="fw-light">Latest Reviews</h2>
                
            </div>
        </div>
    </div>

    <div class="row">
        {foreach from=$last_reviews item=s}
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                   <div class="text-center" style="margin-top: -40px;">
                        <div class="position-relative" style="display: inline-block;">
                            <img src="{$s.photo|default:'https://via.placeholder.com/50'}" 
                                 class="rounded-circle border border-3 border-white shadow"
                                 alt="{$s.uname|escape:html}'s profile picture"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">{$s.uname|escape:html}</h5>
                        <div class="mb-2 text-muted small">
                            <i class="bi bi-clock"></i> {$s.datetime|time_elapsed_string}
                        </div>
                        <div class="mb-3">
                            {* Assuming a 5-star rating system *}
                            {if isset($s.rating)}
                                {for $i=1 to 5}
                                    <i class="bi bi-star{if $i <= $s.rating}-fill{/if} text-warning"></i>
                                {/for}
                            {/if}
                        </div>
                        <p class="card-text">"{$s.review|escape:htmlall}"</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center">
                        {if isset($s.verified) && $s.verified}
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill"></i> Verified Purchase
                            </span>
                        {/if}
                    </div>
                </div>
            </div>
        {foreachelse}
            <div class="col-12">
                <div class="alert alert-info text-center p-5">
                    <i class="bi bi-chat-square-text fs-1 d-block mb-3"></i>
                    <h4 class="alert-heading">No Reviews Yet</h4>
                    <p>Be the first to share your experience with {$settings.site_name}!</p>
                    <hr>
                    <a href="write-review" class="btn btn-primary">Write a Review</a>
                </div>
            </div>
        {/foreach}
    </div>

   
</div>
{include file="footer.tpl"}