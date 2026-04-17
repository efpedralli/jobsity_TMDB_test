<?php
/**
 * Search results template: movies + actors.
 *
 * We rank results in PHP using (V * P) / D. Doing this in SQL across multiple
 * meta fields gets messy fast, so we fetch a reasonable set of matches,
 * compute the score here, then paginate the sorted array.
 */

get_header();

$raw_query = get_search_query();
$q         = sanitize_text_field( (string) $raw_query );

$paged    = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$per_page = 10;

$base_ids = array();

if ( '' !== $q ) {
	// Default search: title/content for movies + actors.
	$base_ids = get_posts(
		array(
			'post_type'              => array( 'movie', 'actor' ),
			'post_status'            => 'publish',
			's'                      => $q,
			'fields'                 => 'ids',
			'posts_per_page'         => 200,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	// Extra: movies alternative_titles meta (when saved).
	$alt_ids = get_posts(
		array(
			'post_type'              => 'movie',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => 200,
			'no_found_rows'          => true,
			'meta_query'             => array(
				array(
					'key'     => 'alternative_titles',
					'value'   => $q,
					'compare' => 'LIKE',
				),
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$all_ids = array_merge( $base_ids, $alt_ids );
	$all_ids = array_values( array_unique( array_map( 'absint', $all_ids ) ) );
} else {
	$all_ids = array();
}

$rows = array();

if ( ! empty( $all_ids ) ) {
	$posts = get_posts(
		array(
			'post_type'      => array( 'movie', 'actor' ),
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'post__in'       => $all_ids,
			'orderby'        => 'post__in',
		)
	);

	foreach ( $posts as $p ) {
		if ( ! $p instanceof WP_Post ) {
			continue;
		}
		$rows[] = array(
			'post'  => $p,
			'score' => jobsity_search_score( $p ),
		);
	}

	usort(
		$rows,
		function ( $a, $b ) {
			$sa = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
			$sb = isset( $b['score'] ) ? (float) $b['score'] : 0.0;

			if ( $sb === $sa ) {
				$ta = isset( $a['post'] ) && $a['post'] instanceof WP_Post ? $a['post']->post_title : '';
				$tb = isset( $b['post'] ) && $b['post'] instanceof WP_Post ? $b['post']->post_title : '';

				return strcasecmp( $ta, $tb );
			}

			return ( $sb < $sa ) ? -1 : 1;
		}
	);
}

$total       = count( $rows );
$total_pages = (int) ceil( $total / $per_page );
$offset      = ( $paged - 1 ) * $per_page;

$page_rows = array_slice( $rows, $offset, $per_page );
?>

<section class="search-results">
	<header class="archive__header">
		<h1 class="archive__title">
			<?php
			printf(
				/* translators: %s: search query */
				esc_html__( 'Search results for “%s”', '' ),
				esc_html( $q )
			);
			?>
		</h1>
		<?php if ( $total > 0 ) : ?>
			<p class="search-results__meta"><?php echo esc_html( sprintf( _n( '%d result', '%d results', $total, '' ), $total ) ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( '' === $q ) : ?>
		<p class="empty-state"><?php esc_html_e( 'Type something to search for movies or actors.', '' ); ?></p>
	<?php elseif ( empty( $page_rows ) ) : ?>
		<p class="empty-state"><?php esc_html_e( 'No results found.', '' ); ?></p>
	<?php else : ?>
		<ul class="result-list">
			<?php foreach ( $page_rows as $row ) : ?>
				<?php
				if ( ! is_array( $row ) || empty( $row['post'] ) || ! ( $row['post'] instanceof WP_Post ) ) {
					continue;
				}

				$p       = $row['post'];
				$post_id = (int) $p->ID;
				$type    = $p->post_type;

				$image = ( 'movie' === $type ) ? jobsity_movie_poster_url( $post_id ) : jobsity_actor_photo_url( $post_id );
				$more  = ( 'movie' === $type ) ? 'More info about this movie' : 'More info about this actor';
				$snip  = jobsity_search_snippet( $p );
				?>
				<li class="result">
					<a class="result__link" href="<?php echo esc_url( get_permalink( $p ) ); ?>">
						<?php if ( $image ) : ?>
							<img class="result__image <?php echo ( 'actor' === $type ) ? 'result__image--round' : ''; ?>" src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" width="160" height="240">
						<?php else : ?>
							<span class="result__placeholder" aria-hidden="true"></span>
						<?php endif; ?>
						<div class="result__body">
							<h2 class="result__title"><?php echo esc_html( get_the_title( $p ) ); ?></h2>
							<?php if ( '' !== $snip ) : ?>
								<p class="result__snippet"><?php echo esc_html( $snip ); ?></p>
							<?php endif; ?>
							<span class="result__more"><?php echo esc_html( $more ); ?></span>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $total_pages > 1 ) : ?>
			<nav class="pagination" aria-label="<?php esc_attr_e( 'Search pagination', '' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'total'   => max( 1, $total_pages ),
							'current' => $paged,
							'type'    => 'list',
							'add_args' => array(
								's' => $q,
							),
						)
					)
				);
				?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</section>

<?php
get_footer();

