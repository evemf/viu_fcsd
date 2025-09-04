<?php get_header(); ?>
<main id="content" class="site-main">
  <section class="hero">
    <div class="container">
      <h1><?php echo esc_html__( 'Benvinguts a la FCSD', 'viu-fcsd' ); ?></h1>
      <p><?php echo esc_html__( 'Junts construïm el futur.', 'viu-fcsd' ); ?></p>
      <a class="button" href="#donate"><?php echo esc_html__( 'Fes una donació', 'viu-fcsd' ); ?></a>
    </div>
  </section>

  <div class="container">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <h2><?php the_title(); ?></h2>
        <div class="entry-content">
          <?php the_content(); ?>
        </div>
      </article>
    <?php endwhile; endif; ?>

    <?php
      // SOLO destacados en portada
      $featured = new WP_Query([
        'post_type'      => 'product',
        'meta_key'       => '_viu_featured',
        'meta_value'     => '1',
        'posts_per_page' => 6,
      ]);
      if ( $featured->have_posts() ) :
    ?>
      <section class="front-featured-products" aria-labelledby="front-featured-title">
        <h2 id="front-featured-title"><?php esc_html_e('Productes destacats','viu-fcsd'); ?></h2>
        <div class="catalog__grid">
          <?php while( $featured->have_posts() ) : $featured->the_post();
            get_template_part( 'templates/partials/product-card' );
          endwhile; wp_reset_postdata(); ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Mantén servicios si procede (no muestra productos) -->
    <?php echo do_shortcode('[services_section index="03/05" limit="6"]'); ?>
  </div>
</main>
<?php get_footer(); ?>
