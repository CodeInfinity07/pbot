<?php
/* Smarty version 4.5.2, created on 2025-05-11 19:16:13
  from '/home/assitix/public_html/tmpl/404.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.2',
  'unifunc' => 'content_6820f77da87d20_63690670',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '805303d776d8b2b923c5a36eed4cccdd76f7d098' => 
    array (
      0 => '/home/assitix/public_html/tmpl/404.tpl',
      1 => 1729797798,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:meta.tpl' => 1,
  ),
),false)) {
function content_6820f77da87d20_63690670 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $_smarty_tpl->_subTemplateRender("file:meta.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .container {
            text-align: center;
        }
        h1 {
            font-size: 48px;
            color: #333;
        }
        p {
            font-size: 18px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Oops! Page not found.</p>
        <a href="/" class="button">Return Home</a>
    </div>
</body>
</html><?php }
}
