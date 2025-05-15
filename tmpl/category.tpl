{include file="header.tpl"}
<h1>All Categories</h1>
<ul>
    {foreach $allCategories as $category}
        <li><a href="{$siteURL}/category/{$category.slug}">{$category.name}</a></li>
    {/foreach}
</ul>
{include file="footer.tpl"}