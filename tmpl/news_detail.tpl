 {include file="header.tpl"}

    <main class="container py-5">
        <article class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Post Meta -->
                <div class="d-flex gap-4 mb-3 text-muted">
                    <div>
                        <i class="bi bi-calendar"></i>
                        <span>{$news_detail.datetime}</span>
                    </div>
                    <div>
                        <i class="bi bi-folder"></i>
                        <span>
                            {foreach $news_detail.category as $category}
                                <a href="{$siteURL}/category/{$category.slug}" class="text-decoration-none text-muted">{$category.name}</a>
                            {/foreach}
                        </span>
                    </div>
                    <div>
                        <i class="bi bi-clock"></i>
                        <span>5 min read</span>
                    </div>
                </div>

                <!-- Post Title -->
                <h1 class="mb-4">{$news_detail.title}</h1>

                <!-- Featured Image -->
                {if $news_detail.image_url}
                    <img src="{$news_detail.image_url}" alt="{$news_detail.title}" class="img-fluid rounded mb-4">
                {/if}

                <!-- Post Content -->
                <div class="mb-5">
                    {$news_detail.content}
                </div>

                <!-- FAQs Section -->
                {if $bfaqs}
                    <div class="card mb-5">
                        <div class="card-header">
                            <h3 class="mb-0">Frequently Asked Questions</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="faqAccordion">
                                {foreach $bfaqs as $index => $faq}
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button {if $index != 0}collapsed{/if}" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faqCollapse{$index}">
                                                {$faq.question}
                                            </button>
                                        </h2>
                                        <div id="faqCollapse{$index}" class="accordion-collapse collapse {if $index == 0}show{/if}" 
                                             data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                {$faq.answer}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                {/if}

                <!-- Author Box -->
                <div class="card mb-5">
                    <div class="card-body d-flex gap-4">
                        <div class="flex-shrink-0">
                            {if $news_detail.author_photo}
                                <img src="{$news_detail.author_photo}" class="rounded-circle" width="100" alt="{$news_detail.author_name}">
                            {else}
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="width: 100px; height: 100px">
                                    <span class="fs-1">{$news_detail.author_name|truncate:1:""}</span>
                                </div>
                            {/if}
                        </div>
                        <div>
                            <h5>
                                <a href="{$siteURL}/authors/{$news_detail.author_slug}" class="text-decoration-none">
                                    {$news_detail.author_name|default:"$siteName Team"}
                                </a>
                            </h5>
                            {if $news_detail.author_title}
                                <p class="text-muted mb-2">{$news_detail.author_title}</p>
                            {/if}
                            {if $news_detail.author_bio}
                                <p class="mb-2">{$news_detail.author_bio|truncate:200}</p>
                            {/if}
                            <a href="{$siteURL}/authors/{$news_detail.author_slug}" class="btn btn-outline-primary btn-sm">
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Search -->

                <!-- Categories -->
                <div class="mb-5">
                    <h4>Categories</h4>
                    {if $categories}
                        <div class="list-group">
                            {foreach from=$categories item=category}
                                <a href="{$siteURL}/category/{$category.slug}" 
                                   class="list-group-item d-flex justify-content-between align-items-center">
                                    {$category.name}
                                    <span class="badge bg-primary rounded-pill">{$category.post_count}</span>
                                </a>
                            {/foreach}
                        </div>
                    {else}
                        <p class="text-muted">No categories found</p>
                    {/if}
                </div>

                <!-- Recent Posts -->
                <div class="mb-5">
                    <h4>Recent Posts</h4>
                    {if $recent_posts}
                        <div class="list-group">
                            {foreach from=$recent_posts item=post}
                                <a href="{$siteURL}/blog/{$post.slug}" class="list-group-item list-group-item-action">
                                    <div class="row g-3">
                                        {if $post.image_url}
                                            <div class="col-3">
                                                <img src="{$post.image_url}" class="img-fluid rounded" alt="{$post.title}">
                                            </div>
                                        {/if}
                                        <div class="col">
                                            <h6 class="mb-1">{$post.title}</h6>
                                            <small class="text-muted">{$post.short_description|truncate:60}</small>
                                        </div>
                                    </div>
                                </a>
                            {/foreach}
                        </div>
                    {else}
                        <p class="text-muted">No recent posts found</p>
                    {/if}
                </div>
            </div>
        </article>

        <!-- Related Articles -->
        {if $related_news}
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Related Articles</h3>
                    <a href="{$siteURL}/news" class="btn btn-link">View All Articles</a>
                </div>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    {foreach from=$related_news item=article}
                        <div class="col">
                            <div class="card h-100">
                                {if $article.image_url}
                                    <img src="{$article.image_url}" class="card-img-top" alt="{$article.title}">
                                {/if}
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="{$siteURL}/blog/{$article.slug}" class="text-decoration-none">
                                            {$article.title}
                                        </a>
                                    </h5>
                                    <p class="card-text">{$article.short_description|truncate:120}</p>
                                    <div class="text-muted">
                                        <small>
                                            By <a href="{$siteURL}/authors/{$article.author_slug}">{$article.author_name}</a>
                                            on {$article.datetime|date_format:"%b %d %Y"}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </section>
        {/if}


    </main>

    <!-- Footer Include -->
    {include file="footer.tpl"}