<?php get_header(); ?>
<main id="content" class="site-main">
  <div class="container catalog">
    <aside class="catalog__filters" aria-labelledby="catalog-filter-title">
      <h2 id="catalog-filter-title"><?php esc_html_e('Filtrar','viu-fcsd'); ?></h2>
      <?php
        $current_cat  = sanitize_text_field( $_GET['category'] ?? '' );
        $min_price    = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
        $max_price    = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
        $name         = sanitize_text_field( $_GET['name'] ?? '' );
      ?>
      <form method="get">
        <label for="f-category"><?php esc_html_e('Categoría','viu-fcsd'); ?></label>
        <select id="f-category" name="category">
          <option value=""><?php esc_html_e('Todas','viu-fcsd'); ?></option>
          <?php foreach ( get_terms( ['taxonomy'=>'category','hide_empty'=>false] ) as $term ) : ?>
            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected( $current_cat, $term->slug ); ?>><?php echo esc_html($term->name); ?></option>
          <?php endforeach; ?>
        </select>
        <label for="f-min"><?php esc_html_e('Precio mínimo','viu-fcsd'); ?></label>
        <input type="number" step="0.01" id="f-min" name="min_price" value="<?php echo esc_attr($min_price); ?>">
        <label for="f-max"><?php esc_html_e('Precio máximo','viu-fcsd'); ?></label>
        <input type="number" step="0.01" id="f-max" name="max_price" value="<?php echo esc_attr($max_price); ?>">
        <label for="f-name"><?php esc_html_e('Nombre','viu-fcsd'); ?></label>
        <input type="text" id="f-name" name="name" value="<?php echo esc_attr($name); ?>">
        <button type="submit" class="button"><?php esc_html_e('Aplicar filtros','viu-fcsd'); ?></button>
        <a href="<?php echo esc_url( get_post_type_archive_link('product') ); ?>" class="filter-reset"><?php esc_html_e('Reiniciar','viu-fcsd'); ?></a>
      </form>
    </aside>
    <div class="catalog__grid">
      <?php
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $args = [
          'post_type' => 'product',
          'paged' => $paged,
        ];
        $tax_query = [];
        if ( $current_cat ) {
          $tax_query[] = [
            'taxonomy' => 'category',
            'field' => 'slug',
            'terms' => $current_cat,
          ];
        }
        if ( $tax_query ) { $args['tax_query'] = $tax_query; }
        $meta_query = [];
        if ( $min_price !== '' ) {
          $meta_query[] = [
            'key' => '_viu_price',
            'value' => $min_price,
            'type' => 'NUMERIC',
            'compare' => '>=',
          ];
        }
        if ( $max_price !== '' ) {
          $meta_query[] = [
            'key' => '_viu_price',
            'value' => $max_price,
            'type' => 'NUMERIC',
            'compare' => '<=',
          ];
        }
        if ( $meta_query ) { $args['meta_query'] = $meta_query; }
        if ( $name ) { $args['s'] = $name; }
        $q = new WP_Query( $args );
        if ( $q->have_posts() ) :
          while( $q->have_posts() ) : $q->the_post();
            get_template_part( 'templates/partials/product-card' );
          endwhile;
          echo '<div class="catalog__pagination">';
          echo paginate_links( [ 'total' => $q->max_num_pages ] );
          echo '</div>';
          wp_reset_postdata();
        else :
          echo '<p>'.esc_html__( 'Sin resultados.', 'viu-fcsd' ).' <a href="'.esc_url( get_post_type_archive_link('product') ).'">'.esc_html__( 'Limpiar filtros', 'viu-fcsd' ).'</a></p>';
        endif;
      ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>
