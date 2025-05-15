<base href="/" />
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>{$page_title|escape}</title>
{if $seo_description}
<meta name="description" content="{$seo_description|escape}" />
{/if} {if $seo_keywords}
<meta name="keywords" content="{$seo_keywords|escape}" />
{/if} {if $seo_canonical}
<link rel="canonical" href="{$seo_canonical|escape}" />
{/if} {if $seo_robots}
<meta name="robots" content="{$seo_robots|escape}" />
{/if} {if $seo_og_type}
<meta property="og:type" content="{$seo_og_type|escape}" />
{/if} {if $seo_og_url}
<meta property="og:url" content="{$seo_og_url|escape}" />
{/if} {if $seo_og_title}
<meta property="og:title" content="{$seo_og_title|escape}" />
{/if} {if $seo_og_description}
<meta property="og:description" content="{$seo_og_description|escape}" />
{/if} {if $seo_og_image}
<meta property="og:image" content="{$seo_og_image|escape}" />
{/if}
<meta property="og:site_name" content="{$site_name|escape}" />
{if $seo_twitter_card}
<meta name="twitter:card" content="{$seo_twitter_card|escape}" />
{/if} {if $seo_twitter_site}
<meta name="twitter:site" content="{$seo_twitter_site|escape}" />
{/if} {if $seo_twitter_title}
<meta name="twitter:title" content="{$seo_twitter_title|escape}" />
{/if} {if $seo_twitter_description}
<meta name="twitter:description" content="{$seo_twitter_description|escape}" />
{/if} {if $seo_twitter_image}
<meta name="twitter:image" content="{$seo_twitter_image|escape}" />
{/if} {if $seo_lang}
<link rel="alternate" href="{$seo_og_url|escape}" hreflang="en" />
{/if} {if $seo_favicon}
<link rel="icon" type="image/png" href="{$seo_favicon}" sizes="32x32" />
{/if} {if $seo_apple_touch_icon}
<link rel="apple-touch-icon" sizes="180x180" href="{$seo_apple_touch_icon}" />
{/if} {if $seo_mask_icon}
<link rel="mask-icon" href="{$seo_mask_icon}" color="{$seo_theme_color|default:'#000000'}" />
{/if} {if $seo_manifest_url}
<link rel="manifest" href="{$seo_manifest_url}" />
{/if} {if $seo_theme_color}
<meta name="theme-color" content="{$seo_theme_color}" />
{/if} {if $seo_background_color}
<meta name="background-color" content="{$seo_background_color}" />
{/if} {if $seo_schema}
<script type="application/ld+json">
    {$seo_schema}
</script>
{/if}
