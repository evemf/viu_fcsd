<?php
get_header();
the_post();
$id = get_the_ID();
$price = get_post_meta($id,'_viu_price', true);
$currency = get_post_meta($id,'_viu_currency', true) ?: 'EUR';
$is_sub = (bool) get_post_meta($id,'_viu_is_subscription', true);
$interval = get_post_meta($id,'_viu_interval', true) ?: 'month';
$interval_count = (int)(get_post_meta($id,'_viu_interval_count', true) ?: 1);
?>
<main id="content" class="site-content site-content--product">
  <section class="services section-padding">
    <div class="container">
      <article class="product-single">
        <header class="product-single__header">
          <small class="small-title"><?php esc_html_e('Servei', 'viu-fcsd'); ?> <strong class="text-warning">•</strong></small>
          <h1 class="product-single__title"><?php the_title(); ?></h1>
          <?php if (has_post_thumbnail()): ?>
            <div class="product-single__image"><?php the_post_thumbnail('large'); ?></div>
          <?php endif; ?>
        </header>
        <div class="product-single__content">
          <?php the_content(); ?>
          <div class="product-single__buy">
            <div class="product-single__price">
              <?php
                $amount = number_format_i18n((float)$price,2);
                echo esc_html($currency.' '.$amount);
                if($is_sub){ echo ' / '.esc_html($interval_count.' '.$interval); }
              ?>
            </div>
            <button class="custom-btn btn custom-link js-buy" data-product="<?php echo esc_attr($id); ?>">
              <?php echo $is_sub ? esc_html__('Subscriu-te','viu-fcsd') : esc_html__('Compra ara','viu-fcsd'); ?>
            </button>
          </div>
        </div>
      </article>
    </div>
  </section>

  <!-- Reusa el modal del shortcode si quieres, o pinta uno propio -->
  <div class="store-modal" id="store-modal" hidden>
    <div class="store-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="store-modal-title">
      <button class="store-modal__close" aria-label="<?php esc_attr_e('Tanca','viu-fcsd'); ?>">&times;</button>
      <h3 id="store-modal-title"><?php esc_html_e('Finalitza la compra','viu-fcsd'); ?></h3>
      <form id="store-checkout-form">
        <input type="hidden" name="product_id" id="store-product-id" value="<?php echo esc_attr($id); ?>">
        <label>
          <?php esc_html_e('Email (rebut i accés):','viu-fcsd'); ?>
          <input type="email" name="email" id="store-email" required>
        </label>
        <button type="submit" class="button button-primary" id="store-pay-btn">
          <?php esc_html_e('Paga','viu-fcsd'); ?>
        </button>
      </form>
      <div id="paypal-buttons-container" style="display:none;"></div>
    </div>
  </div>
</main>
<?php get_footer();
