<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content">
  <?php esc_html_e( 'Skip to content', 'viu-fcsd' ); ?>
</a>

<header class="site-header" role="banner">
  <div class="container">
    <?php get_template_part( 'templates/partials/site-branding' ); ?>
    <button class="nav-toggle" aria-controls="primary-menu" aria-expanded="false">
      <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'viu-fcsd' ); ?></span>
      <span class="hamburger" aria-hidden="true"></span>
    </button>

    <nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'viu-fcsd' ); ?>">
      <?php
      wp_nav_menu(
        [
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => 'menu',
          'menu_id'        => 'primary-menu',
          'fallback_cb'    => false,
        ]
      );
      ?>
      <div class="nav-utilities">
        <a class="user-link" href="<?php echo esc_url( home_url( '/auth/' ) ); ?>"><?php esc_html_e( 'User access', 'viu-fcsd' ); ?></a>
        <?php get_template_part( 'templates/partials/language-switcher', null, [ 'type' => 'list' ] ); ?>
      </div>
    </nav>
  </div>
</header>
