<?php
/**
 * Archive template: Movies.
 */

get_header();

global $wpdb;

$filter_title = isset( $_GET['filter_title'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_title'] ) ) : '';
$filter_name  = isset( $_GET['filter_name'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_name'] ) ) : '';
$filter_year  = isset( $_GET['filter_year'] ) ? absint( $_GET['filter_year'] ) : 0;
$filter_genre = isset( $_GET['filter_genre'] ) ? absint( $_GET['filter_genre'] ) : 0;

$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

$args = array(
	'post_type'      => 'movie',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'orderby'        => 'title',
	'order'          => 'ASC',
);

$meta_query = array();

if ( $filter_year > 0 ) {
	$meta_query[] = array(
		'key'     => 'release_date',
		'value'   => array( $filter_year . '-01-01', $filter_year . '-12-31' ),
		'compare' => 'BETWEEN',
		'type'    => 'DATE',
	);
}

if ( '' !== $filter_name ) {
	$meta_query[] = array(
		'key'     => 'alternative_titles',
		'value'   => $filter_name,
		'compare' => 'LIKE',
	);
}

if ( ! empty( $meta_query ) ) {
	if ( count( $meta_query ) > 1 ) {
		$meta_query['relation'] = 'AND';
	}
	$args['meta_query'] = $meta_query;
}

$tax_query = array();

if ( $filter_genre > 0 ) {
	$tax_query[] = array(
		'taxonomy' => 'genre',
		'field'    => 'term_id',
		'terms'    => $filter_genre,
	);
}

if ( ! empty( $tax_query ) ) {
	$args['tax_query'] = $tax_query;
}

$title_where_cb = null;

if ( '' !== $filter_title ) {
	$title_where_cb = function ( $where ) use ( $wpdb, $filter_title ) {
		$like = '%' . $wpdb->esc_like( $filter_title ) . '%';
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $like );

		return $where;
	};

	add_filter( 'posts_where', $title_where_cb, 10, 1 );
}

$query = new WP_Query( $args );

if ( null !== $title_where_cb ) {
	remove_filter( 'posts_where', $title_where_cb, 10 );
}

$genre_terms = get_terms(
	array(
		'taxonomy'   => 'genre',
		'hide_empty' => true,
	)
);
?>

<section class="archive archive--movies">
	<header class="archive__header">
		<h1 class="archive__title"><?php esc_html_e( 'Movies', '' ); ?></h1>
	</header>

	<form class="filters filters--movies" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>">
		<div class="filters__row">
			<label class="filters__field">
				<span class="filters__label"><?php esc_html_e( 'Title', '' ); ?></span>
				<input type="search" name="filter_title" value="<?php echo esc_attr( $filter_title ); ?>" placeholder="<?php esc_attr_e( 'Filter by title…', '' ); ?>">
			</label>
			<label class="filters__field">
				<span class="filters__label"><?php esc_html_e( 'Name (alternative titles)', '' ); ?></span>
				<input type="search" name="filter_name" value="<?php echo esc_attr( $filter_name ); ?>" placeholder="<?php esc_attr_e( 'Alternative title…', '' ); ?>">
			</label>
			<label class="filters__field">
				<span class="filters__label"><?php esc_html_e( 'Year', '' ); ?></span>
				<input type="number" name="filter_year" value="<?php echo $filter_year ? esc_attr( (string) $filter_year ) : ''; ?>" min="1900" max="2100" placeholder="<?php esc_attr_e( 'e.g. 2024', '' ); ?>">
			</label>
			<label class="filters__field">
				<span class="filters__label"><?php esc_html_e( 'Genre', '' ); ?></span>
				<select name="filter_genre">
					<option value=""><?php esc_html_e( 'All genres', '' ); ?></option>
					<?php if ( ! is_wp_error( $genre_terms ) && ! empty( $genre_terms ) ) : ?>
						<?php foreach ( $genre_terms as $term ) : ?>
							<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $filter_genre, (int) $term->term_id ); ?>>
								<?php echo esc_html( $term->name ); ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</label>
			<div class="filters__actions">
				<button type="submit" class="button"><?php esc_html_e( 'Apply filters', '' ); ?></button>
			</div>
		</div>
	</form>

	<?php if ( $query->have_posts() ) : ?>
		<ul class="card-grid card-grid--movies">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$poster = jobsity_movie_poster_url( get_the_ID() );
				$rd     = get_post_meta( get_the_ID(), 'release_date', true );
				?>
				<li class="card card--movie">
					<a class="card__link" href="<?php the_permalink(); ?>">
						<?php if ( $poster ) : ?>
							<img class="card__image" src="<?php echo esc_url( $poster ); ?>" alt="" loading="lazy" width="300" height="450">
						<?php else : ?>
							<div class="card__placeholder" aria-hidden="true"></div>
						<?php endif; ?>
						<div class="card__body">
							<h2 class="card__title"><?php the_title(); ?></h2>
							<?php if ( is_string( $rd ) && '' !== $rd ) : ?>
								<p class="card__meta"><?php echo esc_html( $rd ); ?></p>
							<?php endif; ?>
							<?php
							$g = jobsity_movie_genres_string( get_the_ID() );
							if ( $g ) :
								?>
								<p class="card__meta card__meta--muted"><?php echo esc_html( $g ); ?></p>
							<?php endif; ?>
						</div>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>

		<nav class="pagination" aria-label="<?php esc_attr_e( 'Movies pagination', '' ); ?>">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'total'   => max( 1, (int) $query->max_num_pages ),
						'current' => $paged,
						'type'    => 'list',
						'add_args' => array_filter(
							array(
								'filter_title' => $filter_title,
								'filter_name'  => $filter_name,
								'filter_year'  => $filter_year ? (string) $filter_year : '',
								'filter_genre' => $filter_genre ? (string) $filter_genre : '',
							)
						),
					)
				)
			);
			?>
		</nav>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<p class="empty-state"><?php esc_html_e( 'No movies found.', '' ); ?></p>
	<?php endif; ?>
</section>

<?php
get_footer();
