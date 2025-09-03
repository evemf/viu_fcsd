<?php
/**
 * Archive: Productos (tienda)
 * URL: /tienda
 */
if ( ! defined('ABSPATH') ) { exit; }
get_header();

// === Parámetros de filtro/sort (GET) ===
$search       = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$cat          = isset($_GET['cat']) ? absint($_GET['cat']) : 0;
$min_price    = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price    = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$is_sub       = isset($_GET['is_sub']) ? ( $_GET['is_sub'] === '1' ? '1' : '' ) : '';
$sort         = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'relevance'; // relevance|newest|price_asc|price_desc

// === Query base ===
$paged = max(1, get_query_var('paged') ? (int) get_query_var('paged') : (int) get_query_var('page'));

$meta_query = ['relation' => 'AND'];
if ($min_price !== '') {
  $meta_query[] = [
    'key' => '_viu_price',
    'value' => $min_price,
    'type' => 'NUMERIC',
    'compare' => '>='
  ];
}
if ($max_price !== '') {
  $meta_query[] = [
    'key' => '_viu_price',
    'value' => $max_price,
    'type' => 'NUMERIC',
    'compare' => '<='
  ];
}
if ($is_sub === '1') {
  $meta_query[] = [
    'key' => '_viu_is_subscription',
    'value' => '1',
    'compare' => '='
  ];
}

$tax_query = [];
if ($cat) {
  $tax_query[] = [
    'taxonomy' => 'category',
    'field'    => 'term_id',
    'terms'    => $cat,
  ];
}

// Ordenación
$orderby = 'date';
$order   = 'DESC';
$meta_key = '';

switch ($sort) {
  case 'newest':
    $orderby = 'date';
    $order   = 'DESC';
    break;
  case 'price_asc':
    $orderby = 'meta_value_num';
    $order   = 'ASC';
    $meta_key = '_viu_price';
    break;
  case 'price_desc':
    $orderby = 'meta_value_num';
    $order   = 'DESC';
    $meta_key = '_viu_price';
    break;
  // 'relevance' => dejamos WP por defecto, pero si hay búsqueda, usa relevancia básica por título/contenido
}

// Construimos query principal de tienda
$args = [
  'post_type'      => 'product',
  's'              => $search,
  'paged'          => $paged,
  'tax_query'      => $tax_query,
  'meta_query'     => $meta_query,
  'orderby'        => $orderby,
  'order'          => $order,
  'posts_per_page' => 12,
];
if ($meta_key) { $args['meta_key'] = $meta_key; }

$q = new WP_Query($args);

// Query para destacados del carrusel
$featured = new WP_Query([
  'post_type'      => 'product',
  'meta_key'       => '_viu_featured',
  'meta_value'     => '1',
  'posts_per_page' => 12,
]);

// Categorías para filtros
$cats = get_terms([
  'taxonomy'   => 'category',
  'hide_empty' => true,
]);

?>

<main id="content" class="site-main">
  <div class="container">

    <!-- ===== Carrusel de destacados (después de header/nav) ===== -->
    <?php if ( $featured->have_posts() ) : ?>
      <section class="store-featured-carousel" aria-labelledby="store-featured-title">
        <h1 id="store-featured-title" class="store-section-title">
          <?php echo esc_html__('Destacados', 'viu-fcsd'); ?>
        </h1>

        <div class="carousel" data-carousel>
          <button class="carousel__control carousel__control--prev" data-carousel-prev aria-label="<?php esc_attr_e('Anterior','viu-fcsd'); ?>">‹</button>
          <div class="carousel__track" data-carousel-track>
            <?php while($featured->have_posts()) : $featured->the_post(); ?>
              <article class="carousel__slide">
                <a class="carousel__card" href="<?php the_permalink(); ?>">
                  <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail('medium', ['class'=>'carousel__image', 'loading'=>'lazy']); ?>
                  <?php endif; ?>
                  <h3 class="carousel__title"><?php the_title(); ?></h3>
                  <?php
                    $price    = (float) get_post_meta(get_the_ID(), '_viu_price', true);
                    $currency = get_post_meta(get_the_ID(), '_viu_currency', true) ?: 'EUR';
                  ?>
                  <div class="carousel__price"><?php echo esc_html( sprintf('%s %s', $currency, number_format_i18n($price, 2)) ); ?></div>
                </a>
              </article>
            <?php endwhile; wp_reset_postdata(); ?>
          </div>
          <button class="carousel__control carousel__control--next" data-carousel-next aria-label="<?php esc_attr_e('Siguiente','viu-fcsd'); ?>">›</button>
        </div>
      </section>
    <?php endif; ?>

    <!-- ===== Layout tienda: Sidebar filtros (25%) + Grid productos (75%) ===== -->
    <section class="store-layout" aria-labelledby="store-catalog-title">
      <h2 id="store-catalog-title" class="screen-reader-text"><?php esc_html_e('Catálogo','viu-fcsd'); ?></h2>

      <!-- Sidebar filtros -->
      <aside class="store-filters" aria-labelledby="filters-title">
        <h3 id="filters-title"><?php esc_html_e('Filtrar','viu-fcsd'); ?></h3>

        <form method="get" class="store-filters__form" action="<?php echo esc_url( get_post_type_archive_link('product') ); ?>">

          <!-- Búsqueda -->
          <label class="store-filter__block">
            <span class="store-filter__label"><?php esc_html_e('Buscar','viu-fcsd'); ?></span>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Buscar productos…','viu-fcsd'); ?>" />
          </label>

          <!-- Categoría -->
          <label class="store-filter__block">
            <span class="store-filter__label"><?php esc_html_e('Categoría','viu-fcsd'); ?></span>
            <select name="cat">
              <option value="0"><?php esc_html_e('Todas','viu-fcsd'); ?></option>
              <?php foreach( $cats as $c ) : ?>
                <option value="<?php echo esc_attr($c->term_id); ?>" <?php selected($cat, $c->term_id); ?>>
                  <?php echo esc_html($c->name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <!-- Precio -->
          <div class="store-filter__block">
            <span class="store-filter__label"><?php esc_html_e('Precio','viu-fcsd'); ?></span>
            <div class="store-filter__row">
              <input type="number" step="0.01" min="0" name="min_price" value="<?php echo esc_attr($min_price); ?>" placeholder="<?php esc_attr_e('Mín','viu-fcsd'); ?>" />
              <input type="number" step="0.01" min="0" name="max_price" value="<?php echo esc_attr($max_price); ?>" placeholder="<?php esc_attr_e('Máx','viu-fcsd'); ?>" />
            </div>
          </div>

          <!-- Tipo (suscripción) -->
          <label class="store-filter__block store-filter__checkbox">
            <input type="checkbox" name="is_sub" value="1" <?php checked($is_sub,'1'); ?> />
            <span><?php esc_html_e('Solo suscripciones','viu-fcsd'); ?></span>
          </label>

          <!-- Ordenar -->
          <label class="store-filter__block">
            <span class="store-filter__label"><?php esc_html_e('Ordenar por','viu-fcsd'); ?></span>
            <select name="sort">
              <option value="relevance"   <?php selected($sort,'relevance'); ?>><?php esc_html_e('Relevancia','viu-fcsd'); ?></option>
              <option value="newest"      <?php selected($sort,'newest'); ?>><?php esc_html_e('Novedades','viu-fcsd'); ?></option>
              <option value="price_asc"   <?php selected($sort,'price_asc'); ?>><?php esc_html_e('Precio: de menor a mayor','viu-fcsd'); ?></option>
              <option value="price_desc"  <?php selected($sort,'price_desc'); ?>><?php esc_html_e('Precio: de mayor a menor','viu-fcsd'); ?></option>
            </select>
          </label>

          <div class="store-filter__actions">
            <button type="submit" class="button"><?php esc_html_e('Aplicar filtros','viu-fcsd'); ?></button>
            <a class="button button--ghost" href="<?php echo esc_url( get_post_type_archive_link('product') ); ?>">
              <?php esc_html_e('Limpiar','viu-fcsd'); ?>
            </a>
          </div>
        </form>
      </aside>

      <!-- Grid productos -->
      <div class="store-catalog">
        <?php if ( $q->have_posts() ) : ?>
          <div class="store-grid">
            <?php
              while ( $q->have_posts() ) : $q->the_post();
                // Reutiliza tu tarjeta
                get_template_part( 'templates/partials/product-card' );
              endwhile;
              wp_reset_postdata();
            ?>
          </div>

          <!-- Paginación -->
          <nav class="pagination" aria-label="<?php esc_attr_e('Paginación','viu-fcsd'); ?>">
            <?php
              echo paginate_links([
                'total'   => $q->max_num_pages,
                'current' => $paged,
                'prev_text' => '«',
                'next_text' => '»',
              ]);
            ?>
          </nav>
        <?php else: ?>
          <p><?php esc_html_e('No se han encontrado productos con los filtros seleccionados.','viu-fcsd'); ?></p>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<?php get_footer(); ?>