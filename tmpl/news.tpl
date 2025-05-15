{include file="header.tpl"}

<div class="container py-5 mt-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">News Section</h1>
        <p class="lead text-muted">Latest updates from {$settings.site_name}</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="faqs" class="btn btn-primary">
                <i class="bi bi-question-circle me-2"></i>See our FAQs
            </a>
            <a href="contact" class="btn btn-outline-secondary">
                <i class="bi bi-headset me-2"></i>Contact Support
            </a>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        {foreach from=$news key=key item=value}
        <div class="col">
            <div class="card h-100 shadow-sm border-0">
                <div class="position-relative">
                    <img src="{$value.image_url}" 
                         class="card-img-top" 
                         alt="{$value.title}" 
                         style="height: 250px; object-fit: cover;">
                    <div class="position-absolute top-0 end-0 p-2">
                        <span class="badge bg-primary">
                            {$value.datetime|date_format:"%b %d, %Y"}
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title mb-3">{$value.title}</h5>
                    <p class="card-text text-muted">
                        {$value.content|truncate:100:"..."}
                    </p>
                </div>
                
                <div class="card-footer bg-transparent border-0 pb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{$value.link}" class="btn btn-outline-primary btn-sm">
                            Read More 
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        <small class="text-muted d-none d-md-block">
                            <i class="bi bi-clock me-1"></i>
                            {$value.datetime}
                        </small>
                    </div>
                </div>
            </div>
        </div>
        {/foreach}
    </div>


    {if $news|@count == 0}
    <div class="text-center py-5">
        <i class="bi bi-newspaper display-4 text-muted mb-3"></i>
        <h3 class="text-muted">No news available</h3>
        <p class="text-muted">Check back later for updates</p>
    </div>
    {/if}
</div>

{include file="footer.tpl"}