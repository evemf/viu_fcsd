<?php
/**
 * Footer FCSD (estilo Waso adaptado + selector de idiomas + dirección dinámica + mapa)
 */
?>
<footer class="site-footer" role="contentinfo">
  <div class="container">

    <?php
    // Dirección por defecto (FCSD) y del Customizer
    $default_address = defined('VIU_FCSD_DEFAULT_ADDRESS')
      ? VIU_FCSD_DEFAULT_ADDRESS
      : "Fundació Catalana Síndrome de Down\nComte Borrell, 201–203, entresòl\n08029 Barcelona\nEspaña";

    $address_raw  = get_theme_mod( 'viu_fcsd_address', $default_address );
    $address_raw  = is_string( $address_raw ) ? trim( $address_raw ) : '';

    // Para mostrar en HTML (con saltos de línea)
    $address_html = nl2br( esc_html( $address_raw ?: $default_address ) );

    // Normalizar para URL del mapa (una sola línea) y codificar
    $address_line = preg_replace( '/\s+/', ' ', $address_raw ?: $default_address );
    $address_q    = urlencode( $address_line );
    ?>

    <!-- Zona superior: branding / contacto / redes -->
    <div class="c-footer__top">
      <!-- Branding + mensaje + donaciones -->
      <div class="c-footer__brand">
        <a class="c-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
          <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
        </a>
        <p class="c-footer__tagline">
          <?php echo esc_html__( 'Treballem per la plena inclusió i igualtat de drets.', 'viu-fcsd' ); ?>
        </p>
        <p class="c-footer__donate">
          <a class="button" href="https://fcsd.org/es/donativo-particular/">
            <?php echo esc_html__( 'Donar', 'viu-fcsd' ); ?>
          </a>
        </p>
      </div>

      <!-- Contacto (dirección dinámica) -->
      <div class="c-footer__contact">
        <ul class="c-footer__contact-list">
          <li>
            <i class="ri-map-pin-line" aria-hidden="true"></i>
            <span><?php echo $address_html; ?></span>
          </li>
          <li>
            <i class="ri-phone-line" aria-hidden="true"></i>
            <a href="tel:+34932157423">+34 93 215 74 23</a>
          </li>
          <li>
            <i class="ri-mail-line" aria-hidden="true"></i>
            <a href="mailto:general@fcsd.org">general@fcsd.org</a>
          </li>
        </ul>
      </div>

      <!-- Redes sociales -->
      <div class="c-footer__social">
        <ul class="social-icon" aria-label="<?php esc_attr_e( 'Redes sociales', 'viu-fcsd' ); ?>">
          <li><a href="https://fcsd.org/" class="social-icon-link" aria-label="Web FCSD" rel="noopener"><i class="ri-global-line" aria-hidden="true"></i></a></li>
          <li><a href="https://www.facebook.com/fundaciocatalanasindromededown/" class="social-icon-link" aria-label="Facebook" rel="noopener"><i class="ri-facebook-fill" aria-hidden="true"></i></a></li>
          <li><a href="https://www.instagram.com/fcsdown/" class="social-icon-link" aria-label="Instagram" rel="noopener"><i class="ri-instagram-line" aria-hidden="true"></i></a></li>
          <li><a href="https://x.com/fcsdown" class="social-icon-link" aria-label="X (Twitter)" rel="noopener"><i class="ri-twitter-x-line" aria-hidden="true"></i></a></li>
          <li><a href="https://www.youtube.com/@FCSDown" class="social-icon-link" aria-label="YouTube" rel="noopener"><i class="ri-youtube-fill" aria-hidden="true"></i></a></li>
        </ul>
      </div>
    </div><!-- /.c-footer__top -->

    <!-- Mapa (misma dirección del customizer) -->
    <?php if ( ! empty( $address_line ) ) : ?>
      <div class="c-footer__map" aria-label="<?php esc_attr_e( 'Mapa de localización', 'viu-fcsd' ); ?>">
        <iframe
          class="google-map"
          title="<?php esc_attr_e( 'Mapa de la sede', 'viu-fcsd' ); ?>"
          src="https://www.google.com/maps?output=embed&q=<?php echo $address_q; ?>"
          width="100%"
          height="300"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          allowfullscreen
        ></iframe>
        <p class="c-footer__map-actions">
          <a class="button button--ghost" target="_blank" rel="noopener"
             href="https://www.google.com/maps/search/?api=1&query=<?php echo $address_q; ?>">
            <?php echo esc_html__( 'Cómo llegar', 'viu-fcsd' ); ?>
          </a>
        </p>
      </div>
    <?php endif; ?>

    <!-- Tu menú de footer (se mantiene tal cual) -->
    <nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Footer Menu', 'viu-fcsd' ); ?>">
      <?php
        wp_nav_menu( [
          'theme_location' => 'footer',
          'menu_class'     => 'menu footer-menu',
          'container'      => false,
          'fallback_cb'    => false,
        ] );
      ?>
    </nav>

    <!-- Selector de idiomas (se mantiene tal cual, en modo dropdown) -->
    <?php get_template_part( 'templates/partials/language-switcher', null, [ 'type' => 'dropdown' ] ); ?>

    <!-- Franja inferior: copyright + legales -->
    <div class="c-footer__bottom">
      <p class="copyright-text mb-0">
        &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
      </p>
      <?php if (function_exists('viu_ml_footer_links')) { viu_ml_footer_links(); } ?>
    </div>

  </div><!-- /.container -->
</footer>

<?php wp_footer(); ?>
</body>
</html>
