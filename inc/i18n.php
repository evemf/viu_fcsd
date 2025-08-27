<?php
/**
 * Internationalization helpers and routing.
 */

function viu_fcsd_languages() {
    return [ 'ca', 'es', 'en' ];
}

function viu_fcsd_current_lang() {
    $langs = viu_fcsd_languages();
    $lang  = get_query_var( 'lang' );
    $lang  = sanitize_key( $lang );

    if ( ! in_array( $lang, $langs, true ) ) {
        $lang = isset( $_COOKIE['viu_lang'] ) ? sanitize_key( $_COOKIE['viu_lang'] ) : '';
        if ( ! in_array( $lang, $langs, true ) ) {
            $lang = 'ca';
        }
    }

    if ( ! isset( $_COOKIE['viu_lang'] ) || $_COOKIE['viu_lang'] !== $lang ) {
        setcookie( 'viu_lang', $lang, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }

    return $lang;
}

function viu_fcsd_switch_url( $lang ) {
    $lang = sanitize_key( $lang );
    if ( ! in_array( $lang, viu_fcsd_languages(), true ) ) {
        return home_url( '/' );
    }

    $path     = trim( $_SERVER['REQUEST_URI'], '/' );
    $segments = $path ? explode( '/', $path ) : [];

    if ( $segments && in_array( $segments[0], viu_fcsd_languages(), true ) ) {
        array_shift( $segments );
    }

    $path = implode( '/', $segments );

    return esc_url( home_url( '/' . $lang . '/' . $path ) );
}

add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'lang';
    return $vars;
} );

add_action( 'init', function () {
    $langs = implode( '|', viu_fcsd_languages() );
    add_rewrite_rule( '^(' . $langs . ')/(.*)/?$', 'index.php?lang=$matches[1]&pagename=$matches[2]', 'top' );
    add_rewrite_rule( '^(' . $langs . ')/?$', 'index.php?lang=$matches[1]&pagename=$matches[1]', 'top' );
} );

add_filter( 'locale', function ( $locale ) {
    $map = [
        'ca' => 'ca',
        'es' => 'es_ES',
        'en' => 'en_US',
    ];
    $lang = viu_fcsd_current_lang();
    return $map[ $lang ] ?? $locale;
} );

add_filter( 'determine_locale', function ( $locale ) {
    $map = [
        'ca' => 'ca',
        'es' => 'es_ES',
        'en' => 'en_US',
    ];
    $lang = viu_fcsd_current_lang();
    return $map[ $lang ] ?? $locale;
} );

if ( defined( 'VIU_FCSD_I18N_DEMO' ) && VIU_FCSD_I18N_DEMO ) {
    add_filter( 'gettext', function ( $translated, $text, $domain ) {
        if ( 'viu-fcsd' !== $domain ) {
            return $translated;
        }

        $demo = [
            'ca' => [
                'Skip to content' => 'Salta al contingut',
                'Menu'            => 'Menú',
                'User access'     => 'Accés usuari',
            ],
            'es' => [
                'Skip to content' => 'Saltar al contenido',
                'Menu'            => 'Menú',
                'User access'     => 'Acceso usuario',
            ],
            'en' => [
                'Skip to content' => 'Skip to content',
                'Menu'            => 'Menu',
                'User access'     => 'User access',
            ],
        ];

        $lang = viu_fcsd_current_lang();
        return $demo[ $lang ][ $text ] ?? $translated;
    }, 10, 3 );
}
