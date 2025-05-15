<?php
/* Smarty version 4.5.2, created on 2025-05-11 19:16:12
  from '/home/assitix/public_html/tmpl/meta.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.2',
  'unifunc' => 'content_6820f77c2c5a25_74321077',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fd7945836a94ad1f4308bfa778de0c39981f8d7f' => 
    array (
      0 => '/home/assitix/public_html/tmpl/meta.tpl',
      1 => 1729797802,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6820f77c2c5a25_74321077 (Smarty_Internal_Template $_smarty_tpl) {
?><base href="/" />
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['page_title']->value, ENT_QUOTES, 'UTF-8', true);?>
</title>
<?php if ($_smarty_tpl->tpl_vars['seo_description']->value) {?>
<meta name="description" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_description']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_keywords']->value) {?>
<meta name="keywords" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_keywords']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_canonical']->value) {?>
<link rel="canonical" href="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_canonical']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_robots']->value) {?>
<meta name="robots" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_robots']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_og_type']->value) {?>
<meta property="og:type" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_og_type']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_og_url']->value) {?>
<meta property="og:url" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_og_url']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_og_title']->value) {?>
<meta property="og:title" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_og_title']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_og_description']->value) {?>
<meta property="og:description" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_og_description']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_og_image']->value) {?>
<meta property="og:image" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_og_image']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?>
<meta property="og:site_name" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['site_name']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php if ($_smarty_tpl->tpl_vars['seo_twitter_card']->value) {?>
<meta name="twitter:card" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_twitter_card']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_twitter_site']->value) {?>
<meta name="twitter:site" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_twitter_site']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_twitter_title']->value) {?>
<meta name="twitter:title" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_twitter_title']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_twitter_description']->value) {?>
<meta name="twitter:description" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_twitter_description']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_twitter_image']->value) {?>
<meta name="twitter:image" content="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_twitter_image']->value, ENT_QUOTES, 'UTF-8', true);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_lang']->value) {?>
<link rel="alternate" href="<?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['seo_og_url']->value, ENT_QUOTES, 'UTF-8', true);?>
" hreflang="en" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_favicon']->value) {?>
<link rel="icon" type="image/png" href="<?php echo $_smarty_tpl->tpl_vars['seo_favicon']->value;?>
" sizes="32x32" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_apple_touch_icon']->value) {?>
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $_smarty_tpl->tpl_vars['seo_apple_touch_icon']->value;?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_mask_icon']->value) {?>
<link rel="mask-icon" href="<?php echo $_smarty_tpl->tpl_vars['seo_mask_icon']->value;?>
" color="<?php echo (($tmp = $_smarty_tpl->tpl_vars['seo_theme_color']->value ?? null)===null||$tmp==='' ? '#000000' ?? null : $tmp);?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_manifest_url']->value) {?>
<link rel="manifest" href="<?php echo $_smarty_tpl->tpl_vars['seo_manifest_url']->value;?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_theme_color']->value) {?>
<meta name="theme-color" content="<?php echo $_smarty_tpl->tpl_vars['seo_theme_color']->value;?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_background_color']->value) {?>
<meta name="background-color" content="<?php echo $_smarty_tpl->tpl_vars['seo_background_color']->value;?>
" />
<?php }?> <?php if ($_smarty_tpl->tpl_vars['seo_schema']->value) {
echo '<script'; ?>
 type="application/ld+json">
    <?php echo $_smarty_tpl->tpl_vars['seo_schema']->value;?>

<?php echo '</script'; ?>
>
<?php }
}
}
