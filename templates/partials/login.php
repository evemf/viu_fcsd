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
  <?php wp_nonce_field('viu_login','_viu_nonce'); ?>
  <input type="hidden" name="action" value="viu_login">

  <label>
    <span><?php esc_html_e('Email o usuario','viu-fcsd'); ?></span>
    <input type="text" name="log" required autocomplete="username" />
  </label>

  <label>
    <span><?php esc_html_e('Contraseña','viu-fcsd'); ?></span>
    <input type="password" name="pwd" required autocomplete="current-password" />
  </label>

  <button type="submit" class="button" style="width:100%;">
    <?php esc_html_e('Entrar','viu-fcsd'); ?>
  </button>

  <p class="auth-meta">
    <a href="<?php echo esc_url( home_url('/password-reset') ); ?>">
      <?php esc_html_e('¿Olvidaste la contraseña?','viu-fcsd'); ?>
    </a>
  </p>
</form>
