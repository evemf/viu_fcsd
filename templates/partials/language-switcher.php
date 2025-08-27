<?php
/**
 * Language switcher.
 */

$langs = [
    'ca' => __( 'Català', 'viu-fcsd' ),
    'es' => __( 'Español', 'viu-fcsd' ),
    'en' => __( 'English', 'viu-fcsd' ),
];
$current       = function_exists( 'viu_fcsd_current_lang' ) ? viu_fcsd_current_lang() : 'ca';
$current_label = $langs[ $current ] ?? $langs['ca'];
?>
<div class="lang-switcher" aria-label="<?php esc_attr_e( 'Language selector', 'viu-fcsd' ); ?>">
    <button class="lang-current" aria-expanded="false">
        <?php echo esc_html( $current_label ); ?>
    </button>
    <ul class="lang-list" hidden>
        <?php foreach ( $langs as $code => $label ) :
            $url  = function_exists( 'viu_fcsd_switch_url' ) ? viu_fcsd_switch_url( $code ) : home_url( '/' . $code . '/' );
            $attr = $current === $code ? ' aria-current="true" class="is-active"' : '';
            $aria = sprintf( __( 'Change language to %s', 'viu-fcsd' ), $label );
        ?>
        <li>
            <a href="<?php echo esc_url( $url ); ?>"<?php echo $attr; ?> aria-label="<?php echo esc_attr( $aria ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
