<?php
/**
 * Single de Servicio
 */
get_header(); ?>

<main id="content" class="site-content site-content--service">
  <article <?php post_class('service-single'); ?>>
    <header class="service-single__header container">
      <p class="small-title">
        <?php esc_html_e('Servei', 'viu-fcsd'); ?>
        <strong class="text-warning">•</strong>
      </p>
      <h1 class="service-single__title"><?php the_title(); ?></h1>
      <?php if ( has_post_thumbnail() ) : ?>
        <div class="service-single__thumb">
          <?php the_post_thumbnail('large', ['class' => 'service-single__image', 'alt' => esc_attr(get_the_title())]); ?>
        </div>
      <?php endif; ?>
    </header>

    <div class="service-single__content container">
      <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
      <p class="service-single__back">
        <a class="custom-btn btn custom-link" href="<?php echo esc_url( get_post_type_archive_link('service') ); ?>">
          <?php esc_html_e('Torna als serveis', 'viu-fcsd'); ?>
        </a>
      </p>
    </div>
  </article>
</main>

<?php get_footer();
