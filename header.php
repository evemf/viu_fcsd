<<<<<<< ours
<?php wp_head(); ?>
<body>
<?php wp_body_open(); ?>
<header>
    <?php get_template_part( 'templates/partials/language-switcher' ); ?>
    <nav><?php wp_nav_menu( [ 'theme_location' => 'primary' ] ); ?></nav>
=======
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'viu-fcsd' ); ?></a>
<header class="site-header" role="banner">
  <div class="container">
    <?php get_template_part( 'templates/partials/site', 'branding' ); ?>
    <nav class="primary-navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'viu-fcsd' ); ?>">
      <?php
        wp_nav_menu( [
          'theme_location' => 'primary',
          'menu_class'     => 'menu primary-menu',
          'container'      => false,
        ] );
      ?>
    </nav>
    <?php get_template_part( 'templates/partials/language', 'switcher' ); ?>
  </div>
>>>>>>> theirs
</header>
