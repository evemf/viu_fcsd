<?php
/**
 * Site branding.
 */

$langs      = function_exists( 'viu_fcsd_languages' ) ? viu_fcsd_languages() : [ 'ca' ];
$default    = $langs[0];
$current    = function_exists( 'viu_fcsd_current_lang' ) ? viu_fcsd_current_lang() : $default;
$home       = $current === $default ? home_url( '/' ) : home_url( '/' . $current . '/' );
$site_name  = get_bloginfo( 'name' );
$has_logo   = has_custom_logo();

// Fallback logo: check several possible extensions so admins can replace the placeholder
// with an image in PNG, SVG, JPG or JPEG format.
$logo_path  = '';
$logo_uri   = '';
$extensions = [ 'png', 'svg', 'jpg', 'jpeg' ];
foreach ( $extensions as $ext ) {
    $file = get_template_directory() . "/assets/img/logo-placeholder.$ext";
    if ( file_exists( $file ) ) {
        $logo_path = $file;
        $logo_uri  = get_template_directory_uri() . "/assets/img/logo-placeholder.$ext";
        break;
    }
}
?>
<div class="c-branding">
    <a href="<?php echo esc_url( $home ); ?>" class="c-branding__link" rel="home">
        <?php if ( $has_logo ) :
            $logo_id = get_theme_mod( 'custom_logo' );
            echo wp_get_attachment_image( $logo_id, 'full', false, [ 'class' => 'c-branding__logo', 'alt' => $site_name ] );
        elseif ( $logo_path ) : ?>
            <img class="c-branding__logo" src="<?php echo esc_url( $logo_uri ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" />
        <?php else : ?>
            <span class="c-branding__logo-text"><?php echo esc_html( $site_name ); ?></span>
        <?php endif; ?>
        <span class="c-branding__name"><?php echo esc_html( $site_name ); ?></span>
    </a>
</div>
