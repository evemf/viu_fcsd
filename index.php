<?php get_header(); ?>
<main>
  <h1><?php esc_html_e( 'viu_fcsd theme', 'viu-fcsd' ); ?></h1>
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
    the_title('<h2>','</h2>');
    the_content();
  endwhile; endif; ?>
</main>
<?php get_footer(); ?>
