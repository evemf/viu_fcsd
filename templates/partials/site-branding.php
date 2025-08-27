<?php
/**
 * Site branding.
 */

$current = function_exists( 'viu_fcsd_current_lang' ) ? viu_fcsd_current_lang() : 'ca';
$home    = home_url( '/' . $current . '/' );
?>
<div class="site-branding">
<?php if ( has_custom_logo() ) :
    $logo_id = get_theme_mod( 'custom_logo' );
    $logo    = wp_get_attachment_image( $logo_id, 'full', false, [ 'class' => 'custom-logo', 'alt' => get_bloginfo( 'name' ) ] );
    ?>
    <a href="<?php echo esc_url( $home ); ?>" class="custom-logo-link"><?php echo $logo; ?></a>
<?php else : ?>
    <a class="site-title" href="<?php echo esc_url( $home ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
<?php endif; ?>
</div>
