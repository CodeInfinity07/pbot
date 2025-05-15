<?php
/* Smarty version 4.5.2, created on 2025-05-11 19:16:12
  from '/home/assitix/public_html/tmpl/footer.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.2',
  'unifunc' => 'content_6820f77c2cb532_08141523',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '022ecf5cd75ad7e28c701f69cd6f4ad26ad19352' => 
    array (
      0 => '/home/assitix/public_html/tmpl/footer.tpl',
      1 => 1732115148,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6820f77c2cb532_08141523 (Smarty_Internal_Template $_smarty_tpl) {
?>
<footer class="footer mt-auto py-5 bg-light border-top">
    <div class="container">
        <div class="row g-4 align-items-center">
            <!-- Logo Column -->
          <div class="col-lg-4 col-md-12 text-center text-lg-start mb-4 mb-lg-0">
    <a href="/" class="d-inline-flex align-items-center mb-3 text-decoration-none" title="<?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>
">
        <img src="bitders/assets/logo.svg" alt="<?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>
" width="40" class="me-3">
        <span class="fs-5 fw-semibold text-dark"><?php echo $_smarty_tpl->tpl_vars['settings']->value['site_name'];?>
</span>
    </a>
    <p class="text-muted small mt-3 mb-0 lh-base">
        Providing reliable investment solutions since <?php echo '<script'; ?>
>document.write(new Date().getFullYear())<?php echo '</script'; ?>
>
    </p>
</div>

            <!-- Quick Links -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-dark mb-3">Important Links</h6>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2">
                        <a href="terms" class="text-muted text-decoration-none hover-primary">
                            <i class="bi bi-chevron-right small"></i> Terms and Conditions
                        </a>
                    </li>
                    <!--<li class="mb-2">
                        <a href="privacy" class="text-muted text-decoration-none hover-primary">
                            <i class="bi bi-chevron-right small"></i> Privacy Policy
                        </a>
                    </li>  -->
                </ul>
            </div>

            <!-- Contact/Social -->
            <div class="col-lg-4 col-md-6">
    <h6 class="text-dark mb-3 fw-semibold">Connect With Us</h6>
    <div class="d-flex gap-3 mb-3">
        <a href="#" class="social-link btn btn-light rounded-circle p-2" aria-label="Twitter">
            <i class="bi bi-twitter"></i>
        </a>
        <a href="#" class="social-link btn btn-light rounded-circle p-2" aria-label="Facebook">
            <i class="bi bi-facebook"></i>
        </a>
        <a href="#" class="social-link btn btn-light rounded-circle p-2" aria-label="LinkedIn">
            <i class="bi bi-linkedin"></i>
        </a>
        <a href="#" class="social-link btn btn-light rounded-circle p-2" aria-label="Instagram">
            <i class="bi bi-instagram"></i>
        </a>
    </div>
</div>
        </div>

        <!-- Bottom Bar -->
      <div class="border-top mt-4 pt-4">
    <div class="row align-items-center gy-3">
        <div class="col-md-6 text-center text-md-start">
            <p class="mb-0 text-muted small">
                &copy; <?php echo '<script'; ?>
>document.write(new Date().getFullYear())<?php echo '</script'; ?>
> <?php echo $_smarty_tpl->tpl_vars['settings']->value['site_url'];?>
. All Rights Reserved.
            </p>
        </div>
        <div class="col-md-6 text-center text-md-end">
            <p class="mb-0 text-muted small">
                Made with <i class="bi bi-heart-fill text-danger"></i> for our valued investors
            </p>
        </div>
    </div>
</div>
    </div>

</footer>
</body>
</html><?php }
}
