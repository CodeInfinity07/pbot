   {include file="header.tpl"}

    <main class="container py-5">
        <!-- Author Profile Card -->
        <div class="card mb-5">
            <div class="card-body">
                <div class="row">
                    <!-- Author Photo -->
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        {if $author.photo}
                            <img src="{$author.photo}" 
                                 alt="{$author.fullname}" 
                                 class="rounded-circle img-fluid"
                                 style="max-width: 160px;">
                        {else}
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                                 style="width: 160px; height: 160px;">
                                <span class="display-4">{$author.fullname|truncate:1:""}</span>
                            </div>
                        {/if}
                    </div>

                    <!-- Author Info -->
                    <div class="col-md-9">
                        <div class="mb-4">
                            <h2 class="mb-3">{$author.fullname}</h2>
                            
                            {if $author.email}
                                <div class="mb-3">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <span class="text-muted">{$author.email}</span>
                                </div>
                            {/if}
                        </div>

                        {if $author.bio}
                            <div class="p-3 border rounded bg-light">
                                <h5 class="text-muted mb-2">Bio</h5>
                                <p class="mb-0">{$author.bio}</p>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        <!-- Author's Posts -->
        <div class="card">
            <div class="card-header bg-white">
                <h3 class="mb-0">Author's Posts</h3>
            </div>
            
            <div class="card-body">
                {if $blogs}
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        {foreach from=$blogs item=blog}
                            <div class="col">
                                <div class="card h-100">
                                    {if $blog.image}
                                        <a href="{$blog.url}" class="text-decoration-none">
                                            <img src="{$blog.image}" 
                                                 class="card-img-top" 
                                                 alt="{$blog.title}"
                                                 style="height: 200px; object-fit: cover;">
                                        </a>
                                    {/if}
                                    
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="{$blog.url}" class="text-decoration-none text-dark">
                                                {$blog.title}
                                            </a>
                                        </h5>
                                        
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {$blog.date|date_format}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {else}
                    <div class="alert alert-info mb-0">
                        No posts found for this author.
                    </div>
                {/if}
            </div>
        </div>
    </main>

    <!-- Footer Include -->
    {include file="footer.tpl"}