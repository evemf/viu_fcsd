<?php
if ( ! defined('ABSPATH') ) { exit; }

/* =========================================
 * A) Crear páginas front-end al activar tema
 * ========================================= */
add_action('after_switch_theme', function(){
  $pages = [
    'account' => [
      'post_title'   => __('Compte', 'viu-fcsd'),
      'post_content' => '[viu_account]',
    ],
    'account-dashboard' => [
      'post_title'   => __('El meu compte', 'viu-fcsd'),
      'post_content' => '[viu_account_dashboard]',
    ],
    'password-reset' => [
      'post_title'   => __('Recuperar contrasenya', 'viu-fcsd'),
      'post_content' => '[viu_password_reset]',
    ],
  ];
  foreach($pages as $slug=>$data){
    if (!get_page_by_path($slug)) {
      wp_insert_post([
        'post_type'   => 'page',
        'post_status' => 'publish',
        'post_name'   => $slug,
        'post_title'  => $data['post_title'],
        'post_content'=> $data['post_content'],
      ]);
    }
  }
});

/* =========================================
 * A1) Auth assets: registrar y encolar auth.js sólo donde toca
 * ========================================= */

/** Registrar script (no se encola todavía). */
add_action('wp_enqueue_scripts', function () {
  $fs  = get_template_directory() . '/assets/js/auth.js';
  $uri = get_template_directory_uri() . '/assets/js/auth.js';
  wp_register_script(
    'viu-auth',
    $uri,
    [], // sin dependencias
    file_exists($fs) ? filemtime($fs) : null, // cache-busting
    true // footer
  );
}, 9);

/** ¿Debemos encolar auth.js en esta petición? */
function viu_fcsd_should_enqueue_auth_js() : bool {
  // 1) Slugs habituales
  if ( is_page('account') || is_page('account-dashboard') || is_page('password-reset') ) { return true; }
  // 2) Plantilla específica (por si cambia el slug)
  if ( is_page_template('page-account.php') ) { return true; }
  // 3) Shortcodes en el contenido
  global $post;
  if ( $post instanceof WP_Post ) {
    $content = (string) $post->post_content;
    if ( has_shortcode($content, 'viu_account')
      || has_shortcode($content, 'viu_account_dashboard')
      || has_shortcode($content, 'viu_password_reset') ) {
      return true;
    }
  }
  // 4) Ruta virtual opcional
  if ( get_query_var('viu_route') === 'account' ) { return true; }

  return false;
}

/** Encolar condicionalmente */
add_action('wp_enqueue_scripts', function(){
  if ( wp_script_is('viu-auth','registered') && viu_fcsd_should_enqueue_auth_js() ) {
    wp_enqueue_script('viu-auth');
  }
}, 20);

/** Añadir defer para mejor rendimiento */
add_filter('script_loader_tag', function($tag, $handle){
  if ( 'viu-auth' === $handle && false === strpos($tag, ' defer') ) {
    $tag = str_replace('<script ', '<script defer ', $tag);
  }
  return $tag;
}, 10, 2);

/* =========================================
 * B) Bloquear /wp-admin/ para no-admins
 * ========================================= */
add_action('admin_init', function(){
  if ( is_user_logged_in() && ! current_user_can('manage_options') ) {
    $pagenow = $GLOBALS['pagenow'] ?? '';
    $is_profile = in_array($pagenow, ['profile.php','user-edit.php'], true);
    if ( $is_profile || ( is_admin() && ! wp_doing_ajax() ) ) {
      $dash = get_page_by_path('account-dashboard');
      $url  = $dash ? get_permalink($dash->ID) : home_url('/account');
      wp_safe_redirect($url);
      exit;
    }
  }
});

/* =========================================
 * C) Utilidades UI
 * ========================================= */
function viu_notice($msg, $type='info'){
  if (empty($msg)) return '';
  $class = 'notice--'.$type;
  return '<div class="notice '.$class.'">'.esc_html($msg).'</div>';
}

/* =========================================
 * D) Shortcode /account (Login + Registro) — usa partials
 * ========================================= */
add_shortcode('viu_account', function(){
  if (is_user_logged_in()){
    $dash = get_page_by_path('account-dashboard');
    $url  = $dash ? get_permalink($dash->ID) : home_url('/account-dashboard');
    wp_safe_redirect($url); exit;
  }

  // Mensajes (?ok / ?err) y pestaña inicial (?tab=register)
  $errors      = isset($_GET['err']) ? sanitize_text_field(wp_unslash($_GET['err'])) : '';
  $ok          = isset($_GET['ok'])  ? sanitize_text_field(wp_unslash($_GET['ok']))  : '';
  $initial_tab = ( isset($_GET['tab']) && $_GET['tab'] === 'register' ) ? 'register' : 'login';

  ob_start(); ?>
  <section class="container" style="max-width:960px;margin:40px auto;">
    <div class="auth-card">
      <!-- Tabs -->
      <div class="auth-card__tabs" role="tablist" aria-label="<?php esc_attr_e('Compte','viu-fcsd'); ?>">
        <button class="auth-tab <?php echo $initial_tab==='login' ? 'is-active' : ''; ?>"
                data-tab="login" id="tab-login" role="tab"
                aria-controls="panel-login"
                aria-selected="<?php echo $initial_tab==='login' ? 'true' : 'false'; ?>">
          <?php esc_html_e('Login','viu-fcsd'); ?>
        </button>
        <button class="auth-tab <?php echo $initial_tab==='register' ? 'is-active' : ''; ?>"
                data-tab="register" id="tab-register" role="tab"
                aria-controls="panel-register"
                aria-selected="<?php echo $initial_tab==='register' ? 'true' : 'false'; ?>">
          <?php esc_html_e('Registro','viu-fcsd'); ?>
        </button>
      </div>

      <!-- Avisos -->
      <?php echo $ok ? viu_notice($ok, 'success') : ''; ?>
      <?php echo $errors ? viu_notice($errors, 'error') : ''; ?>

      <!-- Panels -->
      <div class="auth-card__panels">
        <div class="auth-panel" id="panel-login" role="tabpanel" aria-labelledby="tab-login"
             <?php echo $initial_tab==='register' ? 'hidden' : ''; ?>>
          <?php get_template_part('templates/partials/login'); ?>
        </div>

        <div class="auth-panel" id="panel-register" role="tabpanel" aria-labelledby="tab-register"
             <?php echo $initial_tab==='login' ? 'hidden' : ''; ?>>
          <?php get_template_part('templates/partials/register'); ?>
        </div>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
});

/* =========================================
 * E) Shortcode /account-dashboard (Perfil|Pedidos|Favoritos)
 * ========================================= */
add_shortcode('viu_account_dashboard', function(){
  if (!is_user_logged_in()){
    $acc = get_page_by_path('account');
    $url = $acc ? get_permalink($acc->ID) : home_url('/account');
    wp_safe_redirect($url); exit;
  }
  $u  = wp_get_current_user();
  $ok = isset($_GET['ok']) ? sanitize_text_field(wp_unslash($_GET['ok'])) : '';

  // Datos de perfil
  $first = get_user_meta($u->ID,'first_name',true);
  $last  = get_user_meta($u->ID,'last_name',true);
  $avatar_id  = get_user_meta($u->ID,'profile_photo_id',true);
  $avatar_url = $avatar_id ? wp_get_attachment_image_url($avatar_id, 'thumbnail') : '';

  // Pedidos del usuario (por user_id; fallback por email)
  $orders = new WP_Query([
    'post_type'      => 'order',
    'posts_per_page' => 20,
    'meta_query'     => [
      'relation'=>'OR',
      ['key'=>'_viu_user_id','value'=>$u->ID,'compare'=>'='],
      ['key'=>'_viu_email','value'=>$u->user_email,'compare'=>'='],
    ],
    'orderby'=>'date','order'=>'DESC'
  ]);

  // Favoritos
  $favs = (array) get_user_meta($u->ID, '_viu_favorites', true);
  if (!is_array($favs)) { $favs = []; }

  ob_start(); ?>
  <section class="container" style="max-width:1100px;margin:40px auto;">
    <div class="auth-card">
      <div class="auth-card__header">
        <h2 class="auth-title"><?php esc_html_e('El meu compte','viu-fcsd'); ?></h2>
      </div>
      <?php echo $ok ? viu_notice($ok, 'success') : ''; ?>

      <div class="auth-card__tabs" role="tablist">
        <button class="auth-tab is-active" data-tab="profile" role="tab" aria-controls="panel-profile" aria-selected="true"><?php esc_html_e('Perfil','viu-fcsd'); ?></button>
        <button class="auth-tab" data-tab="orders"  role="tab" aria-controls="panel-orders"  aria-selected="false"><?php esc_html_e('Pedidos','viu-fcsd'); ?></button>
        <button class="auth-tab" data-tab="favs"    role="tab" aria-controls="panel-favs"    aria-selected="false"><?php esc_html_e('Favoritos','viu-fcsd'); ?></button>
      </div>

      <div class="auth-card__panels">
        <!-- Perfil -->
        <div class="auth-panel" id="panel-profile" role="tabpanel">
          <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" class="auth-form" enctype="multipart/form-data">
            <?php wp_nonce_field('viu_profile','_viu_nonce'); ?>
            <input type="hidden" name="action" value="viu_profile">
            <label>
              <span><?php esc_html_e('Foto de perfil','viu-fcsd'); ?></span>
              <?php if ( $avatar_url ) : ?>
                <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" class="auth-avatar" />
              <?php endif; ?>
              <input type="file" name="profile_photo" accept="image/*" />
            </label>
            <label>
              <span><?php esc_html_e('Nombre','viu-fcsd'); ?></span>
              <input type="text" name="first_name" value="<?php echo esc_attr($first); ?>" />
            </label>
            <label>
              <span><?php esc_html_e('Apellidos','viu-fcsd'); ?></span>
              <input type="text" name="last_name" value="<?php echo esc_attr($last); ?>" />
            </label>
            <label>
              <span><?php esc_html_e('Email','viu-fcsd'); ?></span>
              <input type="email" name="user_email" value="<?php echo esc_attr($u->user_email); ?>" required />
            </label>
            <fieldset class="auth-fieldset">
              <legend><?php esc_html_e('Cambiar contraseña (opcional)','viu-fcsd'); ?></legend>
              <label>
                <span><?php esc_html_e('Nova contrasenya','viu-fcsd'); ?></span>
                <input type="password" name="pass1" autocomplete="new-password" minlength="6" />
              </label>
              <label>
                <span><?php esc_html_e('Repite contraseña','viu-fcsd'); ?></span>
                <input type="password" name="pass2" autocomplete="new-password" minlength="6" />
              </label>
            </fieldset>
            <div style="display:flex;gap:8px;align-items:center;">
              <button type="submit" class="button"><?php esc_html_e('Guardar cambios','viu-fcsd'); ?></button>
              <a class="button button--ghost" href="<?php echo esc_url( wp_logout_url( home_url('/account') ) ); ?>"><?php esc_html_e('Tanca la sessió','viu-fcsd'); ?></a>
            </div>
          </form>
        </div>

        <!-- Pedidos -->
        <div class="auth-panel" id="panel-orders" role="tabpanel" hidden>
          <?php if ($orders->have_posts()): ?>
            <div class="store-grid">
              <?php while($orders->have_posts()): $orders->the_post();
                $amount   = get_post_meta(get_the_ID(),'_viu_amount',true);
                $currency = get_post_meta(get_the_ID(),'_viu_currency',true);
                $status   = get_post_meta(get_the_ID(),'_viu_status',true);
                $pid      = (int) get_post_meta(get_the_ID(), '_viu_product_id', true);
              ?>
                <article class="product-card">
                  <a class="product-card__media" href="<?php echo esc_url( get_permalink($pid) ); ?>">
                    <?php echo get_the_post_thumbnail($pid, 'viu-product-card', ['class'=>'product-card__img']); ?>
                  </a>
                  <div class="product-card__body">
                    <h3 class="product-card__title"><?php echo esc_html( get_the_title($pid) ); ?></h3>
                    <p class="store-card__price"><?php echo esc_html($currency.' '.number_format_i18n((float)$amount,2)); ?></p>
                    <p class="store-card__excerpt"><?php esc_html_e('Estado: ','viu-fcsd'); echo esc_html( $status ?: 'pending'); ?></p>
                    <p class="store-card__excerpt"><small><?php esc_html_e('Pedido: ','viu-fcsd'); echo esc_html( get_the_title() ); ?></small></p>
                  </div>
                </article>
              <?php endwhile; wp_reset_postdata(); ?>
            </div>
          <?php else: ?>
            <p><?php esc_html_e('No tienes pedidos todavía.','viu-fcsd'); ?></p>
          <?php endif; ?>
        </div>

        <!-- Favoritos -->
        <div class="auth-panel" id="panel-favs" role="tabpanel" hidden>
          <?php if (!empty($favs)):
            $fav_q = new WP_Query([
              'post_type'      => 'product',
              'post__in'       => array_map('intval',$favs),
              'posts_per_page' => -1
            ]);
            if ($fav_q->have_posts()): ?>
              <div class="store-grid">
                <?php while($fav_q->have_posts()): $fav_q->the_post(); ?>
                  <?php get_template_part('templates/partials/product-card'); ?>
                <?php endwhile; wp_reset_postdata(); ?>
              </div>
            <?php else: ?>
              <p><?php esc_html_e('Aún no tienes favoritos.','viu-fcsd'); ?></p>
            <?php endif; ?>
          <?php else: ?>
            <p><?php esc_html_e('Aún no tienes favoritos.','viu-fcsd'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
});

/* =========================================
 * F) Shortcode /password-reset (nativo WP con tu UI)
 * ========================================= */
add_shortcode('viu_password_reset', function(){
  $key   = isset($_GET['key'])   ? sanitize_text_field($_GET['key'])   : '';
  $login = isset($_GET['login']) ? sanitize_user($_GET['login'])       : '';
  ob_start(); ?>
  <section class="container" style="max-width:720px;margin:40px auto;">
    <div class="auth-card">
      <div class="auth-card__header">
        <h2 class="auth-title"><?php echo $key && $login ? esc_html__('Nova contrasenya','viu-fcsd') : esc_html__('Recuperar contrasenya','viu-fcsd'); ?></h2>
      </div>
      <div class="auth-card__content">
        <?php if (!$key || !$login): ?>
          <form method="post" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="auth-form">
            <label>
              <span><?php esc_html_e('Email o usuario','viu-fcsd'); ?></span>
              <input type="text" name="user_login" required autocomplete="username" />
            </label>
            <button type="submit" class="button" style="width:100%;"><?php esc_html_e('Enviar enlace de recuperación','viu-fcsd'); ?></button>
          </form>
        <?php else: ?>
          <form method="post" action="<?php echo esc_url( site_url('wp-login.php?action=resetpass') ); ?>" class="auth-form">
            <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            <label>
              <span><?php esc_html_e('Nova contrasenya','viu-fcsd'); ?></span>
              <input type="password" name="pass1" required autocomplete="new-password" minlength="6"/>
            </label>
            <label>
              <span><?php esc_html_e('Repite contraseña','viu-fcsd'); ?></span>
              <input type="password" name="pass2" required autocomplete="new-password" minlength="6"/>
            </label>
            <button type="submit" class="button" style="width:100%;"><?php esc_html_e('Guardar','viu-fcsd'); ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
});

/* =========================================
 * G) Handlers: login / registro / logout / perfil
 * ========================================= */
add_action('admin_post_nopriv_viu_login','viu_handle_login');
function viu_handle_login(){
  if (!isset($_POST['_viu_nonce']) || !wp_verify_nonce($_POST['_viu_nonce'],'viu_login')){
    wp_safe_redirect( add_query_arg('err', urlencode(__('Solicitud inválida','viu-fcsd')), home_url('/account')) ); exit;
  }
  $creds = [
    'user_login'    => sanitize_text_field($_POST['log'] ?? ''),
    'user_password' => (string) ($_POST['pwd'] ?? ''),
    'remember'      => true,
  ];
  $user = wp_signon($creds, false);
  if (is_wp_error($user)){
    wp_safe_redirect( add_query_arg('err', urlencode($user->get_error_message()), home_url('/account')) ); exit;
  }
  $dash = get_page_by_path('account-dashboard');
  $url  = $dash ? get_permalink($dash->ID) : home_url('/account-dashboard');
  wp_safe_redirect($url); exit;
}

add_action('admin_post_nopriv_viu_register','viu_handle_register');
function viu_handle_register(){
  if (!isset($_POST['_viu_nonce']) || !wp_verify_nonce($_POST['_viu_nonce'],'viu_register')){
    wp_safe_redirect( add_query_arg('err', urlencode(__('Solicitud inválida','viu-fcsd')), home_url('/account')) ); exit;
  }
  $login = sanitize_user($_POST['user_login'] ?? '');
  $email = sanitize_email($_POST['user_email'] ?? '');
  $pass  = (string) ($_POST['user_pass'] ?? '');

  if (!$login || !$email || !$pass){
    wp_safe_redirect( add_query_arg(['err'=>urlencode(__('Completa todos los campos','viu-fcsd')),'tab'=>'register'], home_url('/account')) ); exit;
  }
  if (!is_email($email)){
    wp_safe_redirect( add_query_arg(['err'=>urlencode(__('Email no válido','viu-fcsd')),'tab'=>'register'], home_url('/account')) ); exit;
  }
  $uid = wp_create_user($login, $pass, $email);
  if (is_wp_error($uid)){
    wp_safe_redirect( add_query_arg(['err'=>urlencode($uid->get_error_message()),'tab'=>'register'], home_url('/account')) ); exit;
  }
  // Autologin
  wp_set_current_user($uid);
  wp_set_auth_cookie($uid, true);

  $dash = get_page_by_path('account-dashboard');
  $url  = $dash ? get_permalink($dash->ID) : home_url('/account-dashboard');
  wp_safe_redirect( add_query_arg('ok', urlencode(__('Compte creat amb èxit','viu-fcsd')), $url) ); exit;
}

add_action('admin_post_viu_logout','viu_handle_logout');
function viu_handle_logout(){
  if (!isset($_POST['_viu_nonce']) || !wp_verify_nonce($_POST['_viu_nonce'],'viu_logout')){
    wp_safe_redirect( home_url('/account') ); exit;
  }
  wp_logout();
  wp_safe_redirect( add_query_arg('ok', urlencode(__('Sesión cerrada','viu-fcsd')), home_url('/account')) ); exit;
}

add_action('admin_post_viu_profile','viu_handle_profile');
function viu_handle_profile(){
  if ( ! is_user_logged_in() ){
    wp_safe_redirect( home_url('/account') ); exit;
  }
  if (!isset($_POST['_viu_nonce']) || !wp_verify_nonce($_POST['_viu_nonce'],'viu_profile')){
    wp_safe_redirect( add_query_arg('err', urlencode(__('Solicitud inválida','viu-fcsd')), home_url('/account-dashboard')) ); exit;
  }
  $u = wp_get_current_user();
  $first = sanitize_text_field($_POST['first_name'] ?? '');
  $last  = sanitize_text_field($_POST['last_name'] ?? '');
  $email = sanitize_email($_POST['user_email'] ?? '');

  if ($email && is_email($email) && $email !== $u->user_email){
    $exists = get_user_by('email', $email);
    if ($exists && $exists->ID !== $u->ID){
      wp_safe_redirect( add_query_arg('err', urlencode(__('Ese email ya está en uso','viu-fcsd')), home_url('/account-dashboard')) ); exit;
    }
    wp_update_user(['ID'=>$u->ID, 'user_email'=>$email]);
  }
  update_user_meta($u->ID,'first_name',$first);
  update_user_meta($u->ID,'last_name',$last);

  if ( ! empty( $_FILES['profile_photo']['name'] ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $avatar_id = media_handle_upload('profile_photo', 0);
    if ( is_wp_error( $avatar_id ) ) {
      wp_safe_redirect( add_query_arg('err', urlencode(__('Error al subir la imagen','viu-fcsd')), home_url('/account-dashboard')) ); exit;
    }
    update_user_meta( $u->ID, 'profile_photo_id', $avatar_id );
  }

  // Cambio de contraseña opcional
  $p1 = (string)($_POST['pass1'] ?? '');
  $p2 = (string)($_POST['pass2'] ?? '');
  if ($p1 || $p2){
    if ($p1 !== $p2){
      wp_safe_redirect( add_query_arg('err', urlencode(__('Las contraseñas no coinciden','viu-fcsd')), home_url('/account-dashboard')) ); exit;
    }
    if (strlen($p1) < 6){
      wp_safe_redirect( add_query_arg('err', urlencode(__('Usa al menos 6 caracteres','viu-fcsd')), home_url('/account-dashboard')) ); exit;
    }
    wp_set_password($p1, $u->ID);
    // Mantener sesión iniciada
    wp_set_auth_cookie($u->ID, true);
  }

  wp_safe_redirect( add_query_arg('ok', urlencode(__('Perfil actualizado','viu-fcsd')), home_url('/account-dashboard')) ); exit;
}

/* =========================================
 * H) Favoritos (REST + helpers)
 * ========================================= */
add_action('rest_api_init', function(){
  register_rest_route('viu-fcsd/v1','/favorites/toggle', [
    'methods'  => 'POST',
    'permission_callback'=> function(){ return is_user_logged_in(); },
    'callback' => function(WP_REST_Request $r){
      $pid = (int) $r->get_param('product_id');
      if (!$pid || get_post_type($pid) !== 'product') {
        return new WP_Error('invalid','Producto no válido',['status'=>400]);
      }
      $uid = get_current_user_id();
      $f = (array) get_user_meta($uid, '_viu_favorites', true);
      $f = is_array($f) ? array_map('intval',$f) : [];
      if (in_array($pid,$f,true)){
        $f = array_values(array_diff($f, [$pid]));
        update_user_meta($uid,'_viu_favorites',$f);
        return ['status'=>'removed','favorites'=>$f];
      } else {
        $f[] = $pid;
        $f = array_values(array_unique($f));
        update_user_meta($uid,'_viu_favorites',$f);
        return ['status'=>'added','favorites'=>$f];
      }
    }
  ]);
});
