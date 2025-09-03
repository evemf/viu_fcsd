<?php
/**
 * Tarjeta de producto reutilizable
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$id = get_the_ID();
$price = (float) get_post_meta( $id, '_viu_price', true );
$currency = get_post_meta( $id, '_viu_currency', true ) ?: 'EUR';
$sale = (float) get_post_meta( $id, '_viu_sale_price', true );
$stock = get_post_meta( $id, '_viu_stock', true );
$has_stock = ($stock !== '' && $stock !== null);

// Favoritos (estado inicial)
$is_fav = false;
if ( is_user_logged_in() ) {
  $uid = get_current_user_id();
  $user_favs = (array) get_user_meta( $uid, '_viu_favorites', true );
  $user_favs = is_array($user_favs) ? array_map('intval', $user_favs) : [];
  $is_fav = in_array( (int) $id, $user_favs, true );
}
?>
<article class="product-card" data-stock="<?php echo esc_attr( $has_stock ? $stock : '' ); ?>">
  <a class="product-card__media product-card__image-wrapper" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
    <?php if ( has_post_thumbnail() ) {
      // Usa el size que estés empleando (recomiendo 'viu-product-card' si lo definiste)
      the_post_thumbnail( 'viu-product-card', [
        'class'    => 'product-card__img product-card__image',
        'alt'      => esc_attr( get_the_title() ),
        'loading'  => 'lazy',
        'decoding' => 'async'
      ] );
    } ?>
  </a>

  <div class="product-card__body">
    <h3 class="product-card__title">
      <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h3>

    <?php if ( has_excerpt() ) : ?>
      <p class="product-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
    <?php endif; ?>

    <div class="product-card__prices">
      <?php if ( $sale && $sale < $price ) : ?>
        <span class="product-card__price product-card__price--sale">
          <?php echo esc_html( $currency . ' ' . number_format_i18n( $sale, 2 ) ); ?>
        </span>
        <span class="product-card__price product-card__price--regular">
          <?php echo esc_html( $currency . ' ' . number_format_i18n( $price, 2 ) ); ?>
        </span>
      <?php else : ?>
        <span class="product-card__price">
          <?php echo esc_html( $currency . ' ' . number_format_i18n( $price, 2 ) ); ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ( $has_stock ) : ?>
      <div class="product-card__stock">
        <?php if ( (int) $stock > 0 ) : ?>
          <?php echo esc_html__( 'Unidades: ', 'viu-fcsd' ) . intval( $stock ); ?>
        <?php else : ?>
          <?php echo esc_html__( 'Agotado', 'viu-fcsd' ); ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ( ! $has_stock || (int) $stock > 0 ) : ?>
      <div class="product-card__actions" style="display:flex;gap:8px;align-items:center;">
        <button
          class="product-card__btn js-add-cart"
          data-product="<?php echo esc_attr( $id ); ?>"
          aria-label="<?php esc_attr_e( 'Añadir a la cesta', 'viu-fcsd' ); ?>"
          title="<?php esc_attr_e( 'Añadir a la cesta', 'viu-fcsd' ); ?>">🛒</button>

        <button
          class="product-card__btn js-buy-one"
          data-product="<?php echo esc_attr( $id ); ?>"
          aria-label="<?php esc_attr_e( 'Comprar en 1 clic', 'viu-fcsd' ); ?>"
          title="<?php esc_attr_e( 'Comprar en 1 clic', 'viu-fcsd' ); ?>">⚡</button>

        <!-- ❤️ Favoritos -->
        <?php
          $fav_label = esc_attr__( 'Favorito', 'viu-fcsd' );
          $fav_text  = $is_fav ? '❤️ ' . $fav_label : '🤍 ' . $fav_label;
        ?>
        <button
          class="product-card__btn js-fav"
          data-product="<?php echo esc_attr( $id ); ?>"
          data-label="<?php echo esc_attr( $fav_label ); ?>"
          aria-pressed="<?php echo $is_fav ? 'true' : 'false'; ?>"
          aria-label="<?php echo $is_fav ? esc_attr__('Quitar de favoritos','viu-fcsd') : esc_attr__('Añadir a favoritos','viu-fcsd'); ?>"
          title="<?php echo $is_fav ? esc_attr__('Quitar de favoritos','viu-fcsd') : esc_attr__('Añadir a favoritos','viu-fcsd'); ?>">
          <?php echo esc_html( $fav_text ); ?>
        </button>
      </div>
    <?php endif; ?>
  </div>
</article>
