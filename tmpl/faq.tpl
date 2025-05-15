{include file="header.tpl"}

<div class="container py-5 mt-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Frequently Asked Questions</h1>
        <p class="lead text-muted">Get quick answers about {$settings.site_name}</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="news" class="btn btn-primary">
                <i class="bi bi-newspaper me-2"></i>See Our News
            </a>
            <a href="contact" class="btn btn-outline-secondary">
                <i class="bi bi-headset me-2"></i>Contact Support
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4 d-none d-lg-block">
            <div class="sticky-top pt-3">
                <h5 class="mb-3">FAQ Categories</h5>
                <nav class="nav flex-column nav-pills">
                    {foreach from=$categorized_faqs key=category item=category_faqs}
                        <a class="nav-link" href="/faqs#category-{$category|replace:' ':'-'}">
                            {$category} 
                            <span class="badge bg-secondary float-end">
                                {$category_faqs|@count}
                            </span>
                        </a>
                    {/foreach}
                </nav>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="faq-content">
                {foreach from=$categorized_faqs key=category item=category_faqs}
                    <div class="category-section mb-5" id="category-{$category|replace:' ':'-'}">
                        <h2 class="border-bottom pb-2 mb-4">
                            <i class="bi bi-question-circle me-2 text-primary"></i>
                            {$category}
                        </h2>

                        <div class="accordion accordion-flush" id="accordion{$category|replace:' ':'-'}">
                            {foreach from=$category_faqs item=faq name=faq_loop}
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {if !$smarty.foreach.faq_loop.first}collapsed{/if}" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse-{$faq.id}" 
                                                aria-expanded="{if $smarty.foreach.faq_loop.first}true{else}false{/if}" 
                                                aria-controls="collapse-{$faq.id}">
                                            {$faq.question|replace:'[site_name]':$settings.site_name}
                                        </button>
                                    </h3>
                                    <div id="collapse-{$faq.id}" 
                                         class="accordion-collapse collapse {if $smarty.foreach.faq_loop.first}show{/if}" 
                                         data-bs-parent="#accordion{$category|replace:' ':'-'}">
                                        <div class="accordion-body">
                                            <div class="faq-answer">
                                                {$faq.answer|replace:'[site_name]':$settings.site_name}
                                            </div>
                                            
                                            {if $faq.tags}
                                                <div class="faq-tags mt-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-tags me-2 text-muted"></i>
                                                        <small class="text-muted">
                                                            {foreach explode(',', $faq.tags) as $tag}
                                                                <span class="badge bg-light text-dark me-1">
                                                                    {trim($tag)}
                                                                </span>
                                                            {/foreach}
                                                        </small>
                                                    </div>
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                {/foreach}
            </div>

            {* Search and Filter Section *}
            <div class="faq-search-section mt-5">
                <h3 class="mb-3">Can't find what you're looking for?</h3>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="faq-search" 
                           placeholder="Search frequently asked questions...">
                </div>
            </div>
        </div>
    </div>
</div>

{include file="footer.tpl"}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('faq-search');
    const accordionItems = document.querySelectorAll('.accordion-item');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        accordionItems.forEach(item => {
            const question = item.querySelector('.accordion-button').textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
            
            if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Fix for category navigation links
    const categoryLinks = document.querySelectorAll('.nav-link[href^="#"]');
    
    categoryLinks.forEach(link => {
    link.addEventListener('click', function() {
        // Remove active class from all links
        categoryLinks.forEach(l => l.classList.remove('active'));
        // Add active class to clicked link
        this.classList.add('active');
    });
});
});
</script>