<?php
/**
 * Language switcher.
 */

$langs = [
    'ca' => __( 'Català', 'viu-fcsd' ),
    'es' => __( 'Español', 'viu-fcsd' ),
    'en' => __( 'English', 'viu-fcsd' ),
];
$current = viu_fcsd_current_lang();
?>
<ul class="language-switcher">
<?php foreach ( $langs as $code => $label ) : ?>
    <li>
        <a href="<?php echo esc_url( viu_fcsd_switch_url( $code ) ); ?>"
           aria-label="<?php echo esc_attr( sprintf( __( 'Switch to %s', 'viu-fcsd' ), $label ) ); ?>"
           <?php echo $current === $code ? 'aria-current="true"' : ''; ?>>
            <?php echo esc_html( $code ); ?>
        </a>
    </li>
<?php endforeach; ?>
</ul>
