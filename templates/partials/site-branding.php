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
    <a class="site-logo-link" href="<?php echo esc_url( $home ); ?>">
        <img class="site-logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-placeholder.svg' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
    </a>
<?php endif; ?>
</div>
