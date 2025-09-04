<?php
/**
 * Archivo de Servicios
 */
get_header(); ?>

<main id="content" class="site-content site-content--services-archive">
  <section class="services section-padding" aria-labelledby="services-archive-title">
    <div class="container">
      <small class="small-title">
        <?php esc_html_e('Serveis', 'viu-fcsd'); ?>
        <strong class="text-warning">•</strong>
      </small>
      <h1 id="services-archive-title"><?php esc_html_e('Els nostres serveis', 'viu-fcsd'); ?></h1>

      <?php if ( have_posts() ) : ?>
        <div class="services-archive__grid">
          <?php while ( have_posts() ) : the_post(); ?>
            <article <?php post_class('service-card'); ?>>
              <a href="<?php the_permalink(); ?>" class="service-card__media">
                <?php if ( has_post_thumbnail() ) {
                    the_post_thumbnail('medium_large', ['class'=>'service-card__image','alt'=>esc_attr(get_the_title())]);
                } ?>
              </a>
              <div class="service-card__body">
                <h2 class="service-card__title">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <?php if ( has_excerpt() ) : ?>
                  <p class="service-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                <?php endif; ?>
                <p class="service-card__cta">
                  <a class="custom-btn btn custom-link" href="<?php the_permalink(); ?>">
                    <?php esc_html_e('Veure servei', 'viu-fcsd'); ?>
                  </a>
                </p>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <?php the_posts_pagination(); ?>

      <?php else : ?>
        <p><?php esc_html_e('Encara no hi ha serveis.', 'viu-fcsd'); ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer();
