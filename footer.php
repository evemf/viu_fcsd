<footer class="site-footer" role="contentinfo">
  <div class="container">
    <?php get_template_part( 'templates/partials/language', 'switcher' ); ?>
    <nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Footer Menu', 'viu-fcsd' ); ?>">
      <?php
        wp_nav_menu( [
          'theme_location' => 'footer',
          'menu_class'     => 'menu footer-menu',
          'container'      => false,
        ] );
      ?>
    </nav>
    <p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
