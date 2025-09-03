<?php
if ( ! defined('ABSPATH') ) { exit; }

// Si ya está logueado, enviamos al dashboard por si accede directo
if ( is_user_logged_in() ) {
  $dash = get_page_by_path('account-dashboard');
  $url  = $dash ? get_permalink($dash->ID) : home_url('/account-dashboard');
  wp_safe_redirect($url); exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="auth-form" novalidate>
  <?php wp_nonce_field('viu_register','_viu_nonce'); ?>
  <input type="hidden" name="action" value="viu_register">

  <label>
    <span><?php esc_html_e('Nombre de usuario','viu-fcsd'); ?></span>
    <input type="text" name="user_login" required autocomplete="username" />
  </label>

  <label>
    <span><?php esc_html_e('Email','viu-fcsd'); ?></span>
    <input type="email" name="user_email" required autocomplete="email" />
  </label>

  <label>
    <span><?php esc_html_e('Contraseña','viu-fcsd'); ?></span>
    <input type="password" name="user_pass" required autocomplete="new-password" minlength="6"/>
  </label>

  <button type="submit" class="button" style="width:100%;">
    <?php esc_html_e('Crear cuenta','viu-fcsd'); ?>
  </button>
</form>
