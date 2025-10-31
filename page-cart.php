<?php
/**
 * Cart page template.
 * Template Name: Cart
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! session_id() ) {
    session_start();
}

get_header();

$cart = $_SESSION['cart'] ?? [];
?>
<main id="primary" class="site-main">
  <h1><?php esc_html_e( 'Cart', 'viu-fcsd' ); ?></h1>
  <?php if ( $cart ) : ?>
    <table class="cart-table">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Product', 'viu-fcsd' ); ?></th>
          <th><?php esc_html_e( 'Qty', 'viu-fcsd' ); ?></th>
          <th><?php esc_html_e( 'Total', 'viu-fcsd' ); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $grand_total = 0;
        $currency = 'EUR';
        foreach ( $cart as $pid => $qty ) :
            $product = get_post( $pid );
            if ( ! $product || 'product' !== $product->post_type ) {
                continue;
            }
            $price    = (float) get_post_meta( $pid, '_viu_price', true );
            $currency = get_post_meta( $pid, '_viu_currency', true ) ?: 'EUR';
            $line_total = $price * $qty;
            $grand_total += $line_total;
        ?>
        <tr>
          <td><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></td>
          <td><?php echo intval( $qty ); ?></td>
          <td><?php echo esc_html( $currency . ' ' . number_format_i18n( $line_total, 2 ) ); ?></td>
          <td>
            <form method="post">
              <button type="submit" name="remove_from_cart" value="<?php echo esc_attr( $pid ); ?>">&times;</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2"><?php esc_html_e( 'Total', 'viu-fcsd' ); ?></th>
          <th colspan="2"><?php echo esc_html( $currency . ' ' . number_format_i18n( $grand_total, 2 ) ); ?></th>
        </tr>
      </tfoot>
    </table>
    <p><?php esc_html_e( 'Checkout process pending implementation.', 'viu-fcsd' ); ?></p>
  <?php else : ?>
    <p><?php esc_html_e( 'Your cart is empty.', 'viu-fcsd' ); ?></p>
  <?php endif; ?>
</main>
<?php
get_footer();
