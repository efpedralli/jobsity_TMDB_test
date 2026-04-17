<?php
/**
 * Archive template: Actors.
 */

get_header();

global $wpdb;

$filter_name  = isset( $_GET['filter_name'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_name'] ) ) : '';
$filter_movie = isset( $_GET['filter_movie'] ) ? absint( $_GET['filter_movie'] ) : 0;

$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

$args = array(
	'post_type'      => 'actor',
	'post_status'    => 'publish',
	'posts_per_page' => 24,
	'paged'          => $paged,
	'orderby'        => 'title',
	'order'          => 'ASC',
);

$meta_query = array();

if ( $filter_movie > 0 ) {
	$meta_query[] = array(
		'key'     => 'related_movie_ids',
		'value'   => 'i:' . $filter_movie . ';',
		'compare' => 'LIKE',
	);
}

if ( ! empty( $meta_query ) ) {
	$args['meta_query'] = $meta_query;
}

$title_where_cb = null;

if ( '' !== $filter_name ) {
	$title_where_cb = function ( $where ) use ( $wpdb, $filter_name ) {
		$like = '%' . $wpdb->esc_like( $filter_name ) . '%';
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $like );

		return $where;
	};

	add_filter( 'posts_where', $title_where_cb, 10, 1 );
}

$query = new WP_Query( $args );

if ( null !== $title_where_cb ) {
	remove_filter( 'posts_where', $title_where_cb, 10 );
}

$movies_for_filter = get_posts(
	array(
		'post_type' => 'movie',
		'post_status'            => 'publish',
		'posts_per_page'         => 500,
		'orderby'                => 'title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'fields'                 => 'ids',
	)
);

$movie_options = array();

foreach ( $movies_for_filter as $mid ) {
	$movie_options[] = get_post( $mid );
}
?>

<section class="archive archive--actors">
	<header class="archive__header">
		<h1 class="archive__title"><?php esc_html_e( 'Actors', 'eduardo' ); ?></h1>
	</header>

	<form class="filters filters--actors" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>">
		<div class="filters__row">
			<label class="filters__field">
				<span class="filters__label"><?php esc_html_e( 'Name', 'eduardo' ); ?></span>
				<input type="search" name="filter_name" value="<?php echo esc_attr( $filter_name ); ?>" placeholder="<?php esc_attr_e( 'Actor name…', 'eduardo' ); ?>">
			</label>
			<label class="filters__field">
				<span class="filters__label"><?php esc_html_e( 'Movie', 'eduardo' ); ?></span>
				<select name="filter_movie">
					<option value=""><?php esc_html_e( 'All movies', 'eduardo' ); ?></option>
					<?php foreach ( $movie_options as $m ) : ?>
						<?php if ( $m instanceof WP_Post ) : ?>
							<option value="<?php echo esc_attr( (string) $m->ID ); ?>" <?php selected( $filter_movie, (int) $m->ID ); ?>>
								<?php echo esc_html( get_the_title( $m ) ); ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="filters__actions">
				<button type="submit" class="button"><?php esc_html_e( 'Apply filters', 'eduardo' ); ?></button>
			</div>
		</div>
	</form>

	<?php if ( $query->have_posts() ) : ?>
		<ul class="card-grid card-grid--actors">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$photo = jobsity_actor_photo_url( get_the_ID() );
				?>
				<li class="card card--actor">
					<a class="card__link" href="<?php the_permalink(); ?>">
						<?php if ( $photo ) : ?>
							<img class="card__image card__image--round" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" width="200" height="200">
						<?php else : ?>
							<div class="card__placeholder card__placeholder--round" aria-hidden="true"></div>
						<?php endif; ?>
						<div class="card__body">
							<h2 class="card__title"><?php the_title(); ?></h2>
						</div>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>

		<nav class="pagination" aria-label="<?php esc_attr_e( 'Actors pagination', 'eduardo' ); ?>">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'total'    => max( 1, (int) $query->max_num_pages ),
						'current'  => $paged,
						'type'     => 'list',
						'add_args' => array_filter(
							array(
								'filter_name'  => $filter_name,
								'filter_movie' => $filter_movie ? (string) $filter_movie : '',
							)
						),
					)
				)
			);
			?>
		</nav>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<p class="empty-state"><?php esc_html_e( 'No actors found.', '' ); ?></p>
	<?php endif; ?>
</section>

<?php
get_footer();
