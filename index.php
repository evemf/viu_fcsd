<?php get_header(); ?>
<main id="content" class="site-main">
  <h1><?php esc_html_e( 'Entrades recents', 'viu-fcsd' ); ?></h1>
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
      <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
      <?php the_excerpt(); ?>
    </article>
  <?php endwhile; the_posts_navigation(); else : ?>
    <p><?php esc_html_e( 'No s\'han trobat entrades', 'viu-fcsd' ); ?></p>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
