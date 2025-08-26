<?php wp_head(); ?>
<body>
<?php wp_body_open(); ?>
<header>
    <?php get_template_part( 'templates/partials/language-switcher' ); ?>
    <nav><?php wp_nav_menu( [ 'theme_location' => 'primary' ] ); ?></nav>
</header>
