<?php
/**
 * Página: Cuenta (Login / Registro) — modular, sin estilos
 * Se activa por jerarquía al existir una página con slug "account".
 */

if ( ! defined('ABSPATH') ) { exit; }
get_header();

/** Imagen aleatoria para hero (opcional). Si no existe la función, no se muestra fondo. */
$hero_bg_url = function_exists('viu_fcsd_get_random_header_image')
  ? viu_fcsd_get_random_header_image()
  : '';

$hero_bg_inline_style = '';
if ( ! empty( $hero_bg_url ) ) {
  $hero_bg_inline_style = "background-image: linear-gradient(45deg, rgba(255,255,255,0.15), rgba(255,255,255,0.15)), url('" . esc_url( $hero_bg_url ) . "'); background-size: cover; background-position: center;";
}

/** Tab inicial por query ?tab=register */
$initial_tab = ( isset($_GET['tab']) && $_GET['tab'] === 'register' ) ? 'register' : 'login';
$login_active    = $initial_tab === 'login'    ? 'active' : '';
$login_show      = $initial_tab === 'login'    ? 'show active' : '';
$register_active = $initial_tab === 'register' ? 'active' : '';
$register_show   = $initial_tab === 'register' ? 'show active' : '';

/** Mensajes desde query (?ok=..., ?err=...) */
$ok  = isset($_GET['ok'])  ? sanitize_text_field(wp_unslash($_GET['ok']))  : '';
$err = isset($_GET['err']) ? sanitize_text_field(wp_unslash($_GET['err'])) : '';
?>
<a class="skip-link screen-reader-text" href="#contenido-principal"><?php esc_html_e('Saltar al contenido','viu-fcsd'); ?></a>

<div id="primary" class="content-area">
  <main id="main" class="site-main" role="main">

    <!-- HERO -->
    <header class="hero" role="banner">
      <div class="hero__bg" style="<?php echo esc_attr( $hero_bg_inline_style ); ?>"></div>
      <div class="hero__overlay" aria-hidden="true"></div>

      <div class="container hero__card" aria-hidden="false">
        <h1 class="hero__title"><?php the_title(); ?></h1>
        <p class="hero__lead"><?php esc_html_e('Inicia sesión o crea una cuenta nueva','viu-fcsd'); ?></p>
      </div>
    </header>

    <!-- CONTENIDO -->
    <section id="contenido-principal" class="section section--auth">
      <div class="container">
        <div class="auth-wrapper">

          <div class="auth-card card card--elevated" role="region" aria-label="<?php esc_attr_e('Autenticación','viu-fcsd'); ?>">

            <!-- Pestañas -->
            <ul class="nav nav-tabs auth-tabs" role="tablist" data-auth-tabs>
              <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo esc_attr($login_active); ?>" id="login-tab-btn"
                  data-target="#login-tab"
                  type="button"
                  role="tab"
                  aria-controls="login-tab"
                  aria-selected="<?php echo $initial_tab === 'login' ? 'true' : 'false'; ?>">
                  <?php esc_html_e('Iniciar sesión', 'viu-fcsd'); ?>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo esc_attr($register_active); ?>" id="register-tab-btn"
                  data-target="#register-tab"
                  type="button"
                  role="tab"
                  aria-controls="register-tab"
                  aria-selected="<?php echo $initial_tab === 'register' ? 'true' : 'false'; ?>">
                  <?php esc_html_e('Registrarse', 'viu-fcsd'); ?>
                </button>
              </li>
            </ul>

            <!-- Avisos -->
            <?php
              if (function_exists('viu_notice')) {
                echo $ok  ? viu_notice($ok, 'success') : '';
                echo $err ? viu_notice($err, 'error')   : '';
              }
            ?>

            <!-- Contenido de tabs -->
            <div class="tab-content auth-content" data-auth-panels>
              <div class="tab-pane fade <?php echo esc_attr($login_show); ?>" id="login-tab" role="tabpanel" aria-labelledby="login-tab-btn">
                <?php get_template_part('templates/partials/login'); ?>
              </div>
              <div class="tab-pane fade <?php echo esc_attr($register_show); ?>" id="register-tab" role="tabpanel" aria-labelledby="register-tab-btn">
                <?php get_template_part('templates/partials/register'); ?>
              </div>
            </div>

          </div><!-- /.auth-card -->
        </div>
      </div>
    </section>

  </main>
</div>

<?php get_footer();
