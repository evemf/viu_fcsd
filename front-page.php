<?php get_header(); ?>
<main id="content" class="site-main">
  <section class="hero">
    <div class="container">
      <h1><?php echo esc_html__( 'Welcome to FCSD', 'viu-fcsd' ); ?></h1>
      <p><?php echo esc_html__( 'Together we build the future.', 'viu-fcsd' ); ?></p>
      <a class="button" href="#donate"><?php echo esc_html__( 'Donate', 'viu-fcsd' ); ?></a>
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
      $featured = new WP_Query([
        'post_type' => 'product',
        'meta_key'  => '_viu_featured',
        'meta_value'=> '1',
        'posts_per_page' => 6,
      ]);
      if ( $featured->have_posts() ) :
    ?>
      <section class="front-featured-products">
        <h2><?php esc_html_e('Productos destacados','viu-fcsd'); ?></h2>
        <div class="catalog__grid">
          <?php while( $featured->have_posts() ) : $featured->the_post();
            get_template_part( 'templates/partials/product-card' );
          endwhile; wp_reset_postdata(); ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Secciones existentes -->
    <?php echo do_shortcode('[digital_store title="Store" limit="12"]'); ?>
    <?php echo do_shortcode('[services_section title="How can we help you?" index="03/05" limit="6"]'); ?>
  </div>

</main>
<?php get_footer(); ?>
