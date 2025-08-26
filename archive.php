<?php get_header(); ?>
<main id="content" class="site-main">
  <?php if ( have_posts() ) : ?>
    <header class="page-header">
      <h1><?php the_archive_title(); ?></h1>
    </header>
    <?php while ( have_posts() ) : the_post(); ?>
      <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php the_excerpt(); ?>
      </article>
    <?php endwhile; ?>
    <?php the_posts_navigation(); ?>
  <?php else : ?>
    <p><?php esc_html_e( 'No posts found', 'viu-fcsd' ); ?></p>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
