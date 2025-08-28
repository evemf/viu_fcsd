<?php
/**
 * Tienda de servicios digitales: CPT + Ajustes + Shortcodes + REST + Webhooks
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* --------------------------
 *  A) Custom Post Types
 * --------------------------*/

add_action('init', function () {

  // Productos (servicios digitales / suscripciones)
  register_post_type('product', [
    'labels' => [
      'name' => __('Productos', 'viu-fcsd'),
      'singular_name' => __('Producto', 'viu-fcsd'),
    ],
    'public' => true,
    'has_archive' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-products',
    'supports' => ['title','editor','excerpt','thumbnail','revisions'],
    'rewrite' => ['slug'=>'tienda','with_front'=>false],
  ]);

  // Metacampos del producto
  $product_meta = [
    'price' => ['type'=>'number'],
    'currency' => ['type'=>'string', 'default'=>'EUR'],
    'is_subscription' => ['type'=>'boolean', 'default'=>false],
    'interval' => ['type'=>'string', 'default'=>'month'], // day|week|month|year
    'interval_count' => ['type'=>'integer', 'default'=>1],
    'sku' => ['type'=>'string'],
  ];
  foreach ($product_meta as $key=>$schema) {
    register_post_meta('product', "_viu_{$key}", array_merge([
      'single'=>true,'show_in_rest'=>true,'auth_callback'=>'__return_true'
    ], $schema));
  }

  // Pedidos
  register_post_type('order', [
    'labels' => [
      'name' => __('Pedidos', 'viu-fcsd'),
      'singular_name' => __('Pedido', 'viu-fcsd'),
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-cart',
    'supports' => ['title','custom-fields'],
  ]);
});

/* --------------------------
 *  B) Ajustes de pago (Admin)
 * --------------------------*/

add_action('admin_menu', function () {
  add_options_page(
    __('Tienda Digital', 'viu-fcsd'),
    __('Tienda Digital', 'viu-fcsd'),
    'manage_options',
    'viu-store',
    'viu_store_settings_page'
  );
});

add_action('admin_init', function () {
  register_setting('viu_store', 'viu_store_settings', ['sanitize_callback'=>'viu_store_sanitize']);

  add_settings_section('viu_store_main', __('Proveedor de pago', 'viu-fcsd'), '__return_false', 'viu_store');

  add_settings_field('provider', __('Proveedor', 'viu-fcsd'), function(){
    $opt = viu_store_get_settings();
    ?>
    <select name="viu_store_settings[provider]">
      <option value="stripe" <?php selected($opt['provider'],'stripe'); ?>>Stripe</option>
      <option value="paypal" <?php selected($opt['provider'],'paypal'); ?>>PayPal</option>
      <option value="monei"  <?php selected($opt['provider'],'monei');  ?>>MONEI</option>
    </select>
    <?php
  }, 'viu_store', 'viu_store_main');

  add_settings_section('viu_store_keys', __('Credenciales', 'viu-fcsd'), function(){
    echo '<p>'.esc_html__('Introduce las claves públicas/privadas del proveedor seleccionado.', 'viu-fcsd').'</p>';
  }, 'viu_store');

  // Stripe
  viu_store_text('stripe_pk', 'Stripe Publishable Key');
  viu_store_text('stripe_sk', 'Stripe Secret Key');

  // PayPal
  viu_store_select('paypal_env', 'PayPal Entorno', [
    'sandbox'=> 'Sandbox',
    'live'   => 'Live',
  ]);
  viu_store_text('paypal_client_id', 'PayPal Client ID');
  viu_store_text('paypal_client_secret', 'PayPal Client Secret');

  // MONEI
  viu_store_text('monei_account_id', 'MONEI Account ID (merchant)');
  viu_store_text('monei_api_key', 'MONEI API Key (secret)');
});

function viu_store_text($key, $label){
  add_settings_field($key, esc_html__($label,'viu-fcsd'), function() use($key){
    $opt = viu_store_get_settings();
    printf('<input type="text" name="viu_store_settings[%1$s]" value="%2$s" class="regular-text"/>',
      esc_attr($key), esc_attr($opt[$key] ?? '')
    );
  }, 'viu_store', 'viu_store_keys');
}
function viu_store_select($key,$label,$choices){
  add_settings_field($key, esc_html__($label,'viu-fcsd'), function() use($key,$choices){
    $opt = viu_store_get_settings(); $val = $opt[$key] ?? '';
    echo '<select name="viu_store_settings['.esc_attr($key).']">';
    foreach($choices as $k=>$l){
      printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($val,$k,false), esc_html($l));
    }
    echo '</select>';
  }, 'viu_store', 'viu_store_keys');
}

function viu_store_settings_page(){
  ?>
  <div class="wrap">
    <h1><?php esc_html_e('Tienda Digital', 'viu-fcsd'); ?></h1>
    <form method="post" action="options.php">
      <?php
        settings_fields('viu_store');
        do_settings_sections('viu_store');
        submit_button();
      ?>
    </form>
  </div>
  <?php
}

function viu_store_sanitize($input){
  $out = is_array($input)? array_map('sanitize_text_field',$input) : [];
  $out['provider'] = in_array($out['provider'] ?? '', ['stripe','paypal','monei'], true) ? $out['provider'] : 'stripe';
  return $out;
}
function viu_store_get_settings(){
  $defaults = [
    'provider'=>'stripe',
    'stripe_pk'=>'','stripe_sk'=>'',
    'paypal_env'=>'sandbox','paypal_client_id'=>'','paypal_client_secret'=>'',
    'monei_account_id'=>'','monei_api_key'=>'',
  ];
  return wp_parse_args(get_option('viu_store_settings',[]), $defaults);
}

/* --------------------------
 *  C) Shortcode de tienda
 * --------------------------*/

add_shortcode('digital_store', function($atts=[]){
  $atts = shortcode_atts([
    'title'   => __('Store', 'viu-fcsd'),
    'subtitle'=> __('Store', 'viu-fcsd'),
    'limit'   => 12,
  ], $atts, 'digital_store');

  $q = new WP_Query([
    'post_type'=>'product',
    'posts_per_page'=> (int)$atts['limit'],
    'orderby'=>'menu_order title',
    'order'=>'ASC',
  ]);

  ob_start(); ?>
  <section class="services section-padding" id="section_store">
    <div class="container">
      <div class="row">
        <div class="col col--intro text-center mx-auto mb-5">
          <h2><?php echo esc_html($atts['title']); ?></h2>
        </div>

        <div class="store-grid">
          <?php if($q->have_posts()): while($q->have_posts()): $q->the_post();
            $id = get_the_ID();
            $price = get_post_meta($id,'_viu_price', true);
            $currency = get_post_meta($id,'_viu_currency', true) ?: 'EUR';
            $is_sub = (bool) get_post_meta($id,'_viu_is_subscription', true);
            $interval = get_post_meta($id,'_viu_interval', true) ?: 'month';
            $interval_count = (int)(get_post_meta($id,'_viu_interval_count', true) ?: 1);
          ?>
            <article class="store-card">
              <a class="store-card__media" href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()) { the_post_thumbnail('large', ['class'=>'store-card__image','alt'=>esc_attr(get_the_title())]); } ?>
              </a>
              <div class="store-card__body">
                <h3 class="store-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php if(has_excerpt()): ?><p class="store-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                <div class="store-card__buy">
                  <div class="store-card__price">
                    <?php
                      $amount = number_format_i18n((float)$price, 2);
                      echo esc_html($currency.' '.$amount);
                      if($is_sub){ echo ' / '.esc_html($interval_count.' '.$interval); }
                    ?>
                  </div>
                  <button class="custom-btn btn custom-link js-buy"
                          data-product="<?php echo esc_attr($id); ?>">
                    <?php echo $is_sub ? esc_html__('Subscribe', 'viu-fcsd') : esc_html__('Buy now', 'viu-fcsd'); ?>
                  </button>
                </div>
              </div>
            </article>
          <?php endwhile; wp_reset_postdata(); else: ?>
            <p><?php esc_html_e('No hay productos todavía.', 'viu-fcsd'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Modal de checkout -->
    <div class="store-modal" id="store-modal" hidden>
      <div class="store-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="store-modal-title">
        <button class="store-modal__close" aria-label="<?php esc_attr_e('Cerrar','viu-fcsd'); ?>">&times;</button>
        <h3 id="store-modal-title"><?php esc_html_e('Finalizar compra','viu-fcsd'); ?></h3>
        <form id="store-checkout-form">
          <input type="hidden" name="product_id" id="store-product-id">
          <label>
            <?php esc_html_e('Email (recibo y acceso):','viu-fcsd'); ?>
            <input type="email" name="email" id="store-email" required>
          </label>
          <button type="submit" class="button button-primary" id="store-pay-btn">
            <?php esc_html_e('Pagar','viu-fcsd'); ?>
          </button>
          <p class="store-note">
            <?php $opt = viu_store_get_settings(); ?>
            <?php echo esc_html__('Proveedor de pago: ', 'viu-fcsd').'<strong>'.esc_html(ucfirst($opt['provider'])).'</strong>'; ?>
          </p>
        </form>
        <div id="paypal-buttons-container" style="display:none;"></div>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
});

/* --------------------------
 *  D) Encolar assets y SDKs
 * --------------------------*/

add_action('wp_enqueue_scripts', function(){
  $opt = viu_store_get_settings();

  // CSS mínimo (usa tu hoja principal si prefieres)
  wp_add_inline_style('viu-fcsd-style', '
    .store-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
    @media(max-width:991px){.store-grid{grid-template-columns:1fr}}
    .store-card{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.06)}
    .store-card__image{width:100%;height:auto;display:block}
    .store-card__body{padding:16px}
    .store-card__title{margin:0 0 8px;font-size:20px}
    .store-card__excerpt{color:#666}
    .store-card__buy{display:flex;justify-content:space-between;align-items:center;margin-top:12px}
    .store-card__price{font-weight:700}
    .store-modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9999}
    .store-modal__dialog{background:#fff;border-radius:12px;max-width:520px;width:92%;padding:20px;position:relative}
    .store-modal__close{position:absolute;right:10px;top:10px;background:transparent;border:0;font-size:24px;cursor:pointer}
    #store-checkout-form label{display:block;margin:12px 0}
    #store-checkout-form input[type=email]{width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
  ');

  // JS
  wp_enqueue_script(
    'viu-store',
    get_template_directory_uri().'/assets/js/store.js',
    ['jquery'],
    file_exists(get_template_directory().'/assets/js/store.js') ? filemtime(get_template_directory().'/assets/js/store.js') : null,
    true
  );

  // Pasar ajustes al JS
  wp_localize_script('viu-store', 'VIU_STORE', [
    'provider' => $opt['provider'],
    'ajaxUrl'  => esc_url_raw( rest_url('viu-fcsd/v1') ),
    'stripePk' => $opt['stripe_pk'],
    'paypalEnv'=> $opt['paypal_env'],
    'paypalId' => $opt['paypal_client_id'],
  ]);

  // SDKs según proveedor
  if ($opt['provider']==='stripe' && !empty($opt['stripe_pk'])) {
    wp_enqueue_script('stripe-js','https://js.stripe.com/v3/',[],null,true);
  } elseif ($opt['provider']==='paypal' && !empty($opt['paypal_client_id'])) {
    $env = $opt['paypal_env']==='live' ? 'production' : 'sandbox';
    $src = 'https://www.paypal.com/sdk/js?client-id='.rawurlencode($opt['paypal_client_id']).'&intent=capture&currency=EUR';
    wp_enqueue_script('paypal-sdk', $src, [], null, true);
  }
  // MONEI: no hace falta SDK si usamos hosted checkout (redirect)
});

/* --------------------------
 *  E) REST API: Checkout
 * --------------------------*/

add_action('rest_api_init', function(){
  register_rest_route('viu-fcsd/v1','/checkout', [
    'methods' => 'POST',
    'callback'=> 'viu_store_checkout',
    'permission_callback' => '__return_true',
  ]);

  register_rest_route('viu-fcsd/v1','/paypal/create-order', [
    'methods'=>'POST','callback'=>'viu_store_paypal_create','permission_callback'=>'__return_true',
  ]);
  register_rest_route('viu-fcsd/v1','/paypal/capture-order', [
    'methods'=>'POST','callback'=>'viu_store_paypal_capture','permission_callback'=>'__return_true',
  ]);

  register_rest_route('viu-fcsd/v1','/webhook/stripe', [
    'methods'=>'POST','callback'=>'viu_store_webhook_stripe','permission_callback'=>'__return_true',
  ]);
  register_rest_route('viu-fcsd/v1','/webhook/monei', [
    'methods'=>'POST','callback'=>'viu_store_webhook_monei','permission_callback'=>'__return_true',
  ]);
  register_rest_route('viu-fcsd/v1','/webhook/paypal', [
    'methods'=>'POST','callback'=>'viu_store_webhook_paypal','permission_callback'=>'__return_true',
  ]);
});

function viu_store_checkout(WP_REST_Request $r){
  $product_id = (int) $r->get_param('product_id');
  $email = sanitize_email($r->get_param('email'));
  if (!$product_id || !is_email($email)) return new WP_Error('invalid','Parámetros inválidos', ['status'=>400]);

  $opt = viu_store_get_settings();
  $provider = $opt['provider'];

  $price = (float) get_post_meta($product_id,'_viu_price',true);
  $currency = get_post_meta($product_id,'_viu_currency',true) ?: 'EUR';
  $is_sub = (bool) get_post_meta($product_id,'_viu_is_subscription',true);
  $interval = get_post_meta($product_id,'_viu_interval',true) ?: 'month';
  $interval_count = (int)(get_post_meta($product_id,'_viu_interval_count',true) ?: 1);

  // Crear pedido local (pendiente)
  $order_id = wp_insert_post([
    'post_type'=>'order',
    'post_status'=>'publish',
    'post_title'=> 'Pedido · '.get_the_title($product_id).' · '.current_time('mysql'),
  ]);
  update_post_meta($order_id,'_viu_product_id',$product_id);
  update_post_meta($order_id,'_viu_email',$email);
  update_post_meta($order_id,'_viu_amount',$price);
  update_post_meta($order_id,'_viu_currency',$currency);
  update_post_meta($order_id,'_viu_status','pending');
  update_post_meta($order_id,'_viu_provider',$provider);

  $return_url = home_url('/?checkout=success&order='.$order_id);
  $cancel_url = home_url('/?checkout=cancel&order='.$order_id);

  if ($provider==='stripe') {
    // Stripe Checkout Session (curl)
    $sk = $opt['stripe_sk'];
    $pk = $opt['stripe_pk'];
    if (empty($sk) || empty($pk)) return new WP_Error('stripe_keys','Stripe keys missing', ['status'=>400]);

    $line = [
      'price_data'=>[
        'currency'=>$currency,
        'product_data'=> ['name'=> get_the_title($product_id)],
        'unit_amount'=> (int) round($price*100),
        ],
      'quantity'=>1
    ];
    if ($is_sub) {
      // Subscription via Checkout: recurring
      $line['price_data']['recurring'] = [
        'interval' => $interval,
        'interval_count' => max(1,$interval_count),
      ];
    }

    $payload = [
      'mode' => $is_sub ? 'subscription' : 'payment',
      'payment_method_types[]' => 'card',
      'success_url' => $return_url.'&session_id={CHECKOUT_SESSION_ID}',
      'cancel_url' => $cancel_url,
      'customer_email' => $email,
    ];
    // encode line items
    // line_items[0][price_data][currency]=... etc.
    $i=0;
    foreach ($line as $k=>$v){
      if ($k==='quantity'){ $payload["line_items[$i][quantity]"] = $v; continue; }
      if ($k==='price_data'){
        foreach($v as $k2=>$v2){
          if (is_array($v2)){
            foreach($v2 as $k3=>$v3){
              $payload["line_items[$i][price_data][$k2][$k3]"] = $v3;
            }
          } else {
            $payload["line_items[$i][price_data][$k2]"] = $v2;
          }
        }
      }
    }

    $res = viu_store_curl('https://api.stripe.com/v1/checkout/sessions', $payload, [
      'Authorization: Bearer '.$sk,
      'Content-Type: application/x-www-form-urlencoded',
    ]);
    if (empty($res['id']) || empty($res['url'])) return new WP_Error('stripe_error','No se pudo crear sesión de pago', ['status'=>500]);

    update_post_meta($order_id,'_viu_provider_id',$res['id']);
    return [
      'redirect' => $res['url'],
      'order_id' => $order_id,
    ];
  }

  if ($provider==='paypal') {
    // Usamos botón JS en el modal. Solo devolvemos order_id local.
    return ['paypal'=>true,'order_id'=>$order_id];
  }

  if ($provider==='monei') {
    // MONEI Hosted Checkout
    $apiKey = $opt['monei_api_key'];
    $account = $opt['monei_account_id'];
    if (empty($apiKey) || empty($account)) return new WP_Error('monei_keys','MONEI keys missing',['status'=>400]);

    $payload = [
      'amount'   => (int) round($price*100),
      'currency' => $currency,
      'orderId'  => (string) $order_id,
      'callbackUrl' => rest_url('viu-fcsd/v1/webhook/monei'),
      'successUrl' => $return_url,
      'failUrl'    => $cancel_url,
      'customer' => ['email'=>$email],
      'description' => get_the_title($product_id),
    ];
    if ($is_sub){
      $payload['subscription'] = [
        'interval' => $interval, 'intervalCount'=> max(1,$interval_count),
      ];
    }

    $res = viu_store_curl("https://api.monei.com/v1/{$account}/payments", $payload, [
      'Authorization: Bearer '.$apiKey,
      'Content-Type: application/json',
    ], true);

    if (empty($res['id']) || empty($res['nextAction']['redirectUrl'])) return new WP_Error('monei_error','No se pudo crear pago MONEI', ['status'=>500]);

    update_post_meta($order_id,'_viu_provider_id',$res['id']);
    return [
      'redirect' => $res['nextAction']['redirectUrl'],
      'order_id' => $order_id,
    ];
  }

  return new WP_Error('provider','Proveedor no soportado', ['status'=>400]);
}

function viu_store_curl($url,$payload,$headers=[],$json=false){
  $ch = curl_init($url);
  if ($json){
    $body = wp_json_encode($payload);
  } else {
    $body = http_build_query($payload);
  }
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>$body,
    CURLOPT_HTTPHEADER=>$headers,
    CURLOPT_TIMEOUT=>30,
  ]);
  $out = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);
  if ($err) return [];
  $data = json_decode($out,true);
  return is_array($data)? $data : [];
}

/* --------------------------
 *  F) PayPal create/capture
 * --------------------------*/

function viu_store_paypal_token($opt){
  $url = ($opt['paypal_env']==='live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
  $auth = base64_encode($opt['paypal_client_id'].':'.$opt['paypal_client_secret']);
  $res = viu_store_curl($url.'/v1/oauth2/token', ['grant_type'=>'client_credentials'], [
    'Authorization: Basic '.$auth,
    'Content-Type: application/x-www-form-urlencoded',
  ]);
  return $res['access_token'] ?? '';
}

function viu_store_paypal_create(WP_REST_Request $r){
  $order_id = (int)$r->get_param('order_id');
  $opt = viu_store_get_settings();
  $token = viu_store_paypal_token($opt);
  if (!$token) return new WP_Error('paypal_token','No token', ['status'=>500]);

  $pid = (int) get_post_meta($order_id,'_viu_product_id',true);
  $price = (float) get_post_meta($pid,'_viu_price',true);
  $currency = get_post_meta($pid,'_viu_currency',true) ?: 'EUR';

  $url = ($opt['paypal_env']==='live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

  $payload = [
    'intent'=>'CAPTURE',
    'purchase_units'=>[[
      'reference_id'=>(string)$order_id,
      'amount'=>['currency_code'=>$currency,'value'=>number_format($price,2,'.','')],
    ]],
    'application_context'=>[
      'return_url'=> home_url('/?checkout=success&order='.$order_id),
      'cancel_url'=> home_url('/?checkout=cancel&order='.$order_id),
    ],
  ];

  $res = viu_store_curl($url.'/v2/checkout/orders',$payload,[
    'Authorization: Bearer '.$token,
    'Content-Type: application/json',
  ], true);

  if (empty($res['id'])) return new WP_Error('paypal_create','Error creando orden', ['status'=>500]);

  update_post_meta($order_id,'_viu_provider','paypal');
  update_post_meta($order_id,'_viu_provider_id',$res['id']);
  return ['id'=>$res['id']];
}

function viu_store_paypal_capture(WP_REST_Request $r){
  $provider_id = sanitize_text_field($r->get_param('orderId'));
  $order_id = (int) $r->get_param('order_id');
  $opt = viu_store_get_settings();
  $token = viu_store_paypal_token($opt);
  $url = ($opt['paypal_env']==='live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

  $res = viu_store_curl($url.'/v2/checkout/orders/'.$provider_id.'/capture', [],[
    'Authorization: Bearer '.$token,
    'Content-Type: application/json',
  ], true);
  // Marcamos como pagado si OK
  if (!empty($res['status']) && in_array($res['status'], ['COMPLETED','APPROVED'], true)) {
    update_post_meta($order_id,'_viu_status','paid');
    return ['status'=>'paid'];
  }
  return new WP_Error('paypal_capture','No completado', ['status'=>400]);
}

/* --------------------------
 *  G) Webhooks (simplificados)
 * --------------------------*/

function viu_store_webhook_stripe(WP_REST_Request $r){
  $body = json_decode($r->get_body(), true);
  $type = $body['type'] ?? '';
  $session = $body['data']['object'] ?? [];
  if (in_array($type, ['checkout.session.completed','invoice.payment_succeeded'], true)) {
    // Buscar pedido por provider_id
    $provider_id = $session['id'] ?? ($session['subscription'] ?? '');
    if ($provider_id){
      viu_store_mark_paid_by_provider($provider_id, 'stripe');
    }
  }
  return ['received'=>true];
}
function viu_store_webhook_monei(WP_REST_Request $r){
  $body = json_decode($r->get_body(), true);
  if (!empty($body['status']) && $body['status']==='SUCCEEDED') {
    $provider_id = $body['id'] ?? '';
    viu_store_mark_paid_by_provider($provider_id,'monei');
  }
  return ['received'=>true];
}
function viu_store_webhook_paypal(WP_REST_Request $r){
  $body = json_decode($r->get_body(), true);
  $resource = $body['resource'] ?? [];
  if (!empty($resource['status']) && $resource['status']==='COMPLETED') {
    $provider_id = $resource['id'] ?? '';
    viu_store_mark_paid_by_provider($provider_id,'paypal');
  }
  return ['received'=>true];
}

function viu_store_mark_paid_by_provider($provider_id,$provider){
  $q = new WP_Query([
    'post_type'=>'order',
    'posts_per_page'=>1,
    'meta_query'=>[
      ['key'=>'_viu_provider','value'=>$provider],
      ['key'=>'_viu_provider_id','value'=>$provider_id],
    ]
  ]);
  if ($q->have_posts()){
    $q->the_post();
    update_post_meta(get_the_ID(),'_viu_status','paid');
    wp_reset_postdata();
  }
}

/* --------------------------
 *  H) Plantilla Single Product (en filtro)
 * --------------------------*/

add_filter('single_template', function($template){
  if (is_singular('product')) {
    $tpl = get_template_directory().'/single-product.php';
    if (file_exists($tpl)) return $tpl;
  }
  return $template;
});
