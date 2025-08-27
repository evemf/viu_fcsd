<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main">
  <?php esc_html_e( 'Skip to content', 'viu-fcsd' ); ?>
</a>

<header class="site-header" role="banner">
  <div class="container">
    <div class="site-branding">
      <?php get_template_part( 'templates/partials/site-branding' ); ?>
    </div>
    <button class="nav-toggle" aria-controls="primary-menu" aria-expanded="false">
      <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'viu-fcsd' ); ?></span>
    </button>

    <nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'viu-fcsd' ); ?>">
      <?php
      wp_nav_menu( [
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => 'menu',
        'menu_id'        => 'primary-menu',
        'fallback_cb'    => false,
      ] );
      ?>

      <div class="lang-switcher" aria-label="<?php esc_attr_e( 'Language selector', 'viu-fcsd' ); ?>">
        <?php
          $current = function_exists( 'viu_fcsd_current_lang' ) ? viu_fcsd_current_lang() : 'ca';
          $langs   = [ 'ca' => 'Català', 'es' => 'Español', 'en' => 'English' ];
          $current_label = $langs[ $current ] ?? $langs['ca'];
        ?>
        <button class="lang-current" aria-expanded="false">
          <?php echo esc_html( $current_label ); ?>
        </button>
        <ul class="lang-list" hidden>
          <?php
          foreach ( $langs as $code => $label ) {
            $url   = function_exists( 'viu_fcsd_switch_url' ) ? viu_fcsd_switch_url( $code ) : home_url( '/' . $code . '/' );
            $attr  = $current === $code ? ' aria-current="true" class="is-active"' : '';
            $aria  = sprintf( __( 'Change language to %s', 'viu-fcsd' ), $label );
            echo '<li><a href="' . esc_url( $url ) . '"' . $attr . ' aria-label="' . esc_attr( $aria ) . '">' . esc_html( $label ) . '</a></li>';
          }
          ?>
        </ul>
      </div>

      <div class="donate-cta">
        <?php
          $donate_slugs = [ 'ca' => 'fes-un-donatiu', 'es' => 'haz-un-donativo', 'en' => 'donate' ];
          $slug         = $donate_slugs[ $current ] ?? $donate_slugs['ca'];
          $base         = function_exists( 'viu_fcsd_switch_url' ) ? viu_fcsd_switch_url( $current ) : home_url( '/' . $current . '/' );
          $donate_url   = trailingslashit( $base ) . $slug . '/';
        ?>
        <a class="button donate" href="<?php echo esc_url( $donate_url ); ?>">
          <?php esc_html_e( 'Fes un donatiu', 'viu-fcsd' ); ?>
        </a>
      </div>
    </nav>
  </div>
</header>

<main id="main" class="site-main">
