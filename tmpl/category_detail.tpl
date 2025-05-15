{include file="header.tpl"}

<!--begin::Content container-->
<div id="kt_app_content_container" class="app-container container-xxl">
    <!--begin::Category header-->
    <div class="card mb-6">
        <div class="card-body pt-9 pb-0">
            <h1 class="text-dark fw-bolder fs-2 mb-3">{$currentCategory.name}</h1>
            {if $currentCategory.description}
                <p class="text-muted fw-semibold fs-5">{$currentCategory.description}</p>
            {/if}
        </div>
    </div>
    <!--end::Category header-->

    {if $categoryProducts}
    <!--begin::Products section-->
    <div class="card mb-8">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">Featured Products</span>
                <span class="text-muted mt-1 fw-semibold fs-7">{count($categoryProducts)} products in this category</span>
            </h3>
        </div>
        <!--begin::Card body-->
        <div class="card-body py-4">
            <div class="row g-10">
                {foreach $categoryProducts as $product}
                <!--begin::Product-->
                <div class="col-md-6 col-xl-4">
                    <div class="card card-xl-stretch mb-xl-8">
                        {if $product.image_url}
                        <!--begin::Product image-->
                        <div class="d-block overlay">
                            <div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover min-h-200px" 
                                 style="background-image:url('{$product.image_url}')">
                            </div>
                            <div class="overlay-layer card-rounded bg-dark bg-opacity-25">
                                <a href="{$siteURL}/products/{$product.slug}" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                        <!--end::Product image-->
                        {/if}
                        
                        <!--begin::Product details-->
                        <div class="card-body px-5 py-7">
                            <div class="fs-3 fw-bold text-dark mb-3">
                                <a href="{$siteURL}/products/{$product.slug}" class="text-gray-900 text-hover-primary">
                                    {$product.name}
                                </a>
                            </div>
                            <div class="fs-6 text-muted mb-5">{$product.short_description|truncate:120}</div>
                            {if $product.price}
                            <div class="fs-2 fw-bold text-primary">{$product.price} {$product.currency|default:'USD'}</div>
                            {/if}
                        </div>
                        <!--end::Product details-->
                    </div>
                </div>
                <!--end::Product-->
                {/foreach}
            </div>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Products section-->
    {/if}

    {if $categoryNews}
    <!--begin::News section-->
    <div class="card">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">Related Articles</span>
                <span class="text-muted mt-1 fw-semibold fs-7">{count($categoryNews)} articles in this category</span>
            </h3>
        </div>
        <!--begin::Card body-->
        <div class="card-body">
            <div class="row g-10">
                {foreach $categoryNews as $news}
                <!--begin::News item-->
                <div class="col-md-6">
                    <div class="card card-xl-stretch me-md-6">
                        {if $news.image_url}
                        <a class="d-block overlay" href="{$siteURL}/blog/{$news.slug}">
                            <div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover min-h-200px" 
                                 style="background-image:url('{$news.image_url}')">
                            </div>
                            <div class="overlay-layer card-rounded bg-dark bg-opacity-25">
                                <i class="bi bi-eye-fill fs-2x text-white"></i>
                            </div>
                        </a>
                        {/if}
                        
                        <div class="card-body px-5 py-7">
                            <div class="fs-3 fw-bold text-dark mb-3">
                                <a href="{$siteURL}/blog/{$news.slug}" class="text-gray-900 text-hover-primary">
                                    {$news.title}
                                </a>
                            </div>
                            <div class="fs-6 text-muted fw-semibold mb-5">
                                {$news.short_description|truncate:160}
                            </div>
                            <div class="fs-7 text-muted">
                                Published: {$news.datetime|date_format:"%B %e, %Y"}
                                {if $news.author_name}
                                <span class="mx-2">|</span>
                                By: {$news.author_name}
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::News item-->
                {/foreach}
            </div>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::News section-->
    {/if}

    {if !$categoryProducts && !$categoryNews}
    <!--begin::No content-->
    <div class="card">
        <div class="card-body p-10 text-center">
            <i class="ki-duotone ki-information-5 fs-5x text-primary mb-5"></i>
            <p class="text-gray-800 fs-4 fw-semibold">
                No content available in this category yet.
            </p>
        </div>
    </div>
    <!--end::No content-->
    {/if}
</div>
<!--end::Content container-->

{include file="footer.tpl"}