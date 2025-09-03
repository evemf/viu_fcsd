<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="c-skip-link" href="#content"><?php esc_html_e( 'Ir al contenido', 'viu-fcsd' ); ?></a>

<!-- TOP BAR (Waso-style) con datos FCSD -->
<header class="site-header" role="banner">
    <div class="container">
        <div class="row align-items-center">

            <!-- Horario -->
            <div class="col col--hours">
                <p class="text-white mb-0">
                    <i class="ri-time-line site-header-icon" aria-hidden="true"></i>
                    <?php /* Lun-Jue 08:00-20:00 • Vie 08:00-18:00 */ ?>
                    <?php echo esc_html__( 'Lun-Jue 08:00-20:00 • Vie 08:00-18:00', 'viu-fcsd' ); ?>
                </p>
            </div>

            <!-- Teléfono -->
            <div class="col col--phone">
                <p class="text-white mb-0">
                    <a href="tel:+34932157423" class="text-white">
                        <i class="ri-phone-line site-header-icon" aria-hidden="true"></i>
                        +34 93 215 74 23
                    </a>
                </p>
            </div>

            <!-- Redes sociales oficiales FCSD -->
            <div class="col col--social ms-auto">
                <ul class="social-icon" aria-label="<?php esc_attr_e( 'Redes sociales', 'viu-fcsd' ); ?>">
                    <li>
                        <a href="https://fcsd.org/" class="social-icon-link" aria-label="Web FCSD" rel="noopener">
                            <i class="ri-global-line" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/fundaciocatalanasindromededown/" class="social-icon-link" aria-label="Facebook" rel="noopener">
                            <i class="ri-facebook-fill" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.instagram.com/fcsdown/" class="social-icon-link" aria-label="Instagram" rel="noopener">
                            <i class="ri-instagram-line" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://x.com/fcsdown" class="social-icon-link" aria-label="X (Twitter)" rel="noopener">
                            <i class="ri-twitter-x-line" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.youtube.com/@FCSDown" class="social-icon-link" aria-label="YouTube" rel="noopener">
                            <i class="ri-youtube-fill" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</header>

<!-- NAV + BRAND + TOOLS -->
<header class="c-header" data-header>
    <div class="c-header__inner container">
        <?php get_template_part( 'templates/partials/site-branding' ); ?>

        <div class="c-header__tools">
            <!-- Buscador -->
            <button class="c-header__search-toggle" aria-controls="site-search" aria-expanded="false">
                <i class="ri-search-line" aria-hidden="true"></i>
                <span class="screen-reader-text"><?php esc_html_e( 'Buscar', 'viu-fcsd' ); ?></span>
            </button>

            <!-- Dark mode -->
            <button class="c-header__dark-toggle" aria-label="<?php esc_attr_e( 'Cambiar tema', 'viu-fcsd' ); ?>">
                <i class="ri-contrast-2-line" aria-hidden="true"></i>
            </button>

            <!-- Carrito -->
            <a class="c-header__cart" href="#" role="button" aria-label="<?php esc_attr_e( 'Ver carrito', 'viu-fcsd' ); ?>">
                <i class="ri-shopping-cart-line" aria-hidden="true"></i>
            </a>

            <!-- Mi cuenta -->
            <?php
            $account_page   = get_page_by_path('account');
            $dashboard_page = get_page_by_path('account-dashboard');

            $account_url   = $account_page ? get_permalink($account_page->ID) : home_url('/account');
            $dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/account');
            ?>
            <?php if ( is_user_logged_in() ) : ?>
                <a class="c-header__account is-logged" href="<?php echo esc_url( $dashboard_url ); ?>" aria-label="<?php esc_attr_e( 'Mi cuenta', 'viu-fcsd' ); ?>">
                    <i class="ri-user-3-line" aria-hidden="true"></i>
                </a>
                <a class="c-header__logout" href="<?php echo esc_url( wp_logout_url( home_url('/account') ) ); ?>" aria-label="<?php esc_attr_e( 'Cerrar sesión', 'viu-fcsd' ); ?>">
                    <i class="ri-logout-box-line" aria-hidden="true"></i>
                </a>
            <?php else : ?>
                <a class="c-header__account" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'Iniciar sesión', 'viu-fcsd' ); ?>">
                    <i class="ri-user-3-line" aria-hidden="true"></i>
                </a>
                <a class="c-header__register" href="<?php echo esc_url( add_query_arg( 'tab', 'register', $account_url ) ); ?>" aria-label="<?php esc_attr_e( 'Registrarse', 'viu-fcsd' ); ?>">
                    <i class="ri-user-add-line" aria-hidden="true"></i>
                </a>
            <?php endif; ?>

            <!-- Language switcher -->
            <?php get_template_part( 'templates/partials/language-switcher', null, [ 'type' => 'list' ] ); ?>

            <!-- Toggle menú -->
            <button class="c-header__menu-toggle"
                    aria-controls="primary-nav"
                    aria-expanded="false">
                <span class="c-header__menu-icon" aria-hidden="true"></span>
                <span class="screen-reader-text"><?php esc_html_e( 'Menú', 'viu-fcsd' ); ?></span>
            </button>
        </div>
    </div>

    <nav class="c-header__nav"
         id="primary-nav"
         aria-label="<?php esc_attr_e( 'Principal', 'viu-fcsd' ); ?>"
         itemscope itemtype="https://schema.org/SiteNavigationElement"
         hidden>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'c-menu',
                'depth'          => 2,
                'fallback_cb'    => false,
            ]
        );
        ?>
    </nav>

    <!-- Buscador expandible -->
    <div class="c-header__search" id="site-search" role="search" hidden>
        <?php get_search_form(); ?>
        <p class="c-header__address">
            <i class="ri-map-pin-line" aria-hidden="true"></i>
            Comte Borrell, 201–203, entresòl · 08029 Barcelona
        </p>
        <p class="c-header__email">
            <i class="ri-mail-line" aria-hidden="true"></i>
            <a href="mailto:general@fcsd.org">general@fcsd.org</a>
        </p>
    </div>

    <div class="c-header__overlay" data-overlay hidden></div>
</header>
