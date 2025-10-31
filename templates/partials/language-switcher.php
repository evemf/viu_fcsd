<?php
/**
 * Language switcher.
 */

$langs = [
    'ca' => __( 'Català', 'viu-fcsd' ),
    'es' => __( 'Español', 'viu-fcsd' ),
    'en' => __( 'English', 'viu-fcsd' ),
];
$current = function_exists( 'viu_fcsd_current_lang' ) ? viu_fcsd_current_lang() : 'ca';
$type    = $args['type'] ?? 'list';

if ( 'dropdown' === $type ) : ?>
<form class="language-switcher" aria-label="<?php esc_attr_e( 'Language selector', 'viu-fcsd' ); ?>">
    <label for="footer-lang" class="screen-reader-text"><?php esc_html_e( 'Choose language', 'viu-fcsd' ); ?></label>
    <select id="footer-lang" class="language-select">
        <?php foreach ( $langs as $code => $label ) :
            $url      = function_exists( 'viu_fcsd_switch_url' ) ? viu_fcsd_switch_url( $code ) : home_url( '/' . $code . '/' );
            $selected = $current === $code ? ' selected aria-current="true"' : '';
        ?>
        <option value="<?php echo esc_url( $url ); ?>"<?php echo $selected; ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>
</form>
<?php else : ?>
<ul class="language-switcher" aria-label="<?php esc_attr_e( 'Language selector', 'viu-fcsd' ); ?>">
    <?php foreach ( $langs as $code => $label ) :
        $url  = function_exists( 'viu_fcsd_switch_url' ) ? viu_fcsd_switch_url( $code ) : home_url( '/' . $code . '/' );
        $attr = $current === $code ? ' aria-current="true" class="is-active"' : '';
    ?>
    <li><a href="<?php echo esc_url( $url ); ?>"<?php echo $attr; ?>><?php echo esc_html( $label ); ?></a></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
