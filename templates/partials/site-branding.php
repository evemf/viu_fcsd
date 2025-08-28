<?php
/**
 * Site branding.
 */

$current    = function_exists( 'viu_fcsd_current_lang' ) ? viu_fcsd_current_lang() : 'ca';
$home       = home_url( '/' . $current . '/' );
$site_name  = get_bloginfo( 'name' );
$logo_path  = get_template_directory() . '/assets/img/logo-placeholder.svg';
$logo_uri   = get_template_directory_uri() . '/assets/img/logo-placeholder.svg';
$has_logo   = has_custom_logo();
?>
<div class="c-branding">
    <a href="<?php echo esc_url( $home ); ?>" class="c-branding__link" rel="home">
        <?php if ( $has_logo ) :
            $logo_id = get_theme_mod( 'custom_logo' );
            echo wp_get_attachment_image( $logo_id, 'full', false, [ 'class' => 'c-branding__logo', 'alt' => $site_name ] );
        elseif ( file_exists( $logo_path ) ) : ?>
            <img class="c-branding__logo" src="<?php echo esc_url( $logo_uri ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" />
        <?php else : ?>
            <span class="c-branding__logo-text"><?php echo esc_html( $site_name ); ?></span>
        <?php endif; ?>
        <span class="c-branding__name"><?php echo esc_html( $site_name ); ?></span>
    </a>
</div>
