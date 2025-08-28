<?php
/**
 * Servicios (CPT) + Shortcode de sección de servicios.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Registrar Custom Post Type: service
 */
function viu_register_services_cpt() {
    $labels = [
        'name'               => _x( 'Servicios', 'post type general name', 'viu-fcsd' ),
        'singular_name'      => _x( 'Servicio', 'post type singular name', 'viu-fcsd' ),
        'menu_name'          => _x( 'Servicios', 'admin menu', 'viu-fcsd' ),
        'name_admin_bar'     => _x( 'Servicio', 'add new on admin bar', 'viu-fcsd' ),
        'add_new'            => _x( 'Añadir nuevo', 'service', 'viu-fcsd' ),
        'add_new_item'       => __( 'Añadir nuevo servicio', 'viu-fcsd' ),
        'new_item'           => __( 'Nuevo servicio', 'viu-fcsd' ),
        'edit_item'          => __( 'Editar servicio', 'viu-fcsd' ),
        'view_item'          => __( 'Ver servicio', 'viu-fcsd' ),
        'all_items'          => __( 'Todos los servicios', 'viu-fcsd' ),
        'search_items'       => __( 'Buscar servicios', 'viu-fcsd' ),
        'parent_item_colon'  => __( 'Servicio padre:', 'viu-fcsd' ),
        'not_found'          => __( 'No se han encontrado servicios.', 'viu-fcsd' ),
        'not_found_in_trash' => __( 'No hay servicios en la papelera.', 'viu-fcsd' )
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-hammer', // cambia si quieres
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'servicios', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 21,
        'supports'           => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ],
        'show_in_rest'       => true,
    ];

    register_post_type( 'service', $args );
}
add_action( 'init', 'viu_register_services_cpt' );

/**
 * Shortcode: [services_section title="How can we help you?" limit="6"]
 * Renderiza la sección tipo Waso con pestañas (tabs) y CTA a cada servicio individual.
 */
function viu_services_section_shortcode( $atts = [] ) {
    $atts = shortcode_atts( [
        'title' => __( 'How can we help you?', 'viu-fcsd' ),
        'subtitle' => __( 'Services', 'viu-fcsd' ),
        'index' => '03/05',
        'limit' => 6,
        'order' => 'ASC',
        'orderby' => 'menu_order',
        'ids' => '', // opcional: "12,31,44" para forzar orden
        'image_size' => 'large',
        'show_excerpt' => '1',
        'cta_label' => __( 'Ver servicio', 'viu-fcsd' ),
    ], $atts, 'services_section' );

    $ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );

    $q_args = [
        'post_type'      => 'service',
        'posts_per_page' => (int) $atts['limit'],
        'order'          => $atts['order'],
        'orderby'        => $atts['orderby'],
    ];

    if ( ! empty( $ids ) ) {
        $q_args['post__in'] = $ids;
        $q_args['orderby']  = 'post__in';
    }

    $q = new WP_Query( $q_args );
    ob_start();

    ?>
    <section class="services section-padding" id="section_3" aria-labelledby="services-title">
        <div class="container">
            <div class="row">
                <div class="col col--intro">
                    <h2 id="services-title"><?php echo esc_html( $atts['title'] ); ?></h2>
                </div>

                <?php if ( $q->have_posts() ) : ?>
                    <div class="services__wrap">
                        <nav class="services__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Servicios', 'viu-fcsd' ); ?>">
                            <?php
                            $i = 0;
                            while ( $q->have_posts() ) :
                                $q->the_post();
                                $active = $i === 0 ? 'true' : 'false';
                                ?>
                                <button
                                    class="services__tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                                    id="tab-<?php the_ID(); ?>"
                                    role="tab"
                                    aria-selected="<?php echo esc_attr( $active ); ?>"
                                    aria-controls="panel-<?php the_ID(); ?>"
                                    data-tab-target="panel-<?php the_ID(); ?>"
                                >
                                    <h3 class="services__tab-title"><?php the_title(); ?></h3>
                                    <?php if ( has_excerpt() ) : ?>
                                        <span class="services__tab-desc"><?php echo esc_html( get_the_excerpt() ); ?></span>
                                    <?php endif; ?>
                                </button>
                                <?php
                                $i++;
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </nav>

                        <div class="services__panels">
                            <?php
                            $i = 0;
                            $q2 = new WP_Query( $q_args );
                            while ( $q2->have_posts() ) :
                                $q2->the_post();
                                $hidden = $i === 0 ? '' : ' hidden';
                                ?>
                                <div
                                    class="services__panel<?php echo $i === 0 ? ' is-active' : ''; ?>"
                                    id="panel-<?php the_ID(); ?>"
                                    role="tabpanel"
                                    aria-labelledby="tab-<?php the_ID(); ?>"
                                    <?php echo $hidden; ?>
                                >
                                    <div class="services__panel-media">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( $atts['image_size'], [ 'class' => 'services__image', 'alt' => esc_attr( get_the_title() ) ] ); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="services__panel-body">
                                        <h4 class="services__panel-title"><?php the_title(); ?></h4>

                                        <?php if ( $atts['show_excerpt'] && has_excerpt() ) : ?>
                                            <p class="services__panel-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                                        <?php else : ?>
                                            <div class="services__panel-content">
                                                <?php the_content(); ?>
                                            </div>
                                        <?php endif; ?>

                                        <p class="services__panel-cta">
                                            <a class="custom-btn btn custom-link" href="<?php the_permalink(); ?>">
                                                <?php echo esc_html( $atts['cta_label'] ); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                                <?php
                                $i++;
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e( 'Aún no hay servicios publicados.', 'viu-fcsd' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}
add_shortcode( 'services_section', 'viu_services_section_shortcode' );
