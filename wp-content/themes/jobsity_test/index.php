<?php
/**
 * Main template: homepage (blog index when no front page template).
 */

get_header();

$today = gmdate( 'Y-m-d' );

$upcoming_query = new WP_Query(
	array(
		'post_type'           => 'movie',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'meta_key'            => 'release_date',
		'orderby'             => 'meta_value',
		'order'               => 'ASC',
		'meta_type'           => 'DATE',
		'meta_query'          => array(
			array(
				'key'     => 'release_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'ignore_sticky_posts' => true,
	)
);

$grouped_movies = array();

if ( $upcoming_query->have_posts() ) {
	while ( $upcoming_query->have_posts() ) {
		$upcoming_query->the_post();
		$rd = get_post_meta( get_the_ID(), 'release_date', true );
		if ( ! is_string( $rd ) || '' === $rd ) {
			$label = __( 'Date TBD', '' );
		} else {
			$ts = strtotime( $rd . ' UTC' );
			if ( false === $ts ) {
				$label = $rd;
			} else {
				$label = gmdate( 'F Y', $ts );
			}
		}
		if ( ! isset( $grouped_movies[ $label ] ) ) {
			$grouped_movies[ $label ] = array();
		}
		$grouped_movies[ $label ][] = get_post();
	}
	wp_reset_postdata();
}

$actors_query = new WP_Query(
	array(
		'post_type'           => 'actor',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'meta_key'            => 'popularity',
		'orderby'             => 'meta_value_num',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	)
);
?>

<div class="home">
	<section class="home__section">
		<h1 class="home__heading"><?php esc_html_e( 'Upcoming movies', '' ); ?></h1>

		<?php if ( ! empty( $grouped_movies ) ) : ?>
			<?php foreach ( $grouped_movies as $month_label => $posts ) : ?>
				<section class="home__month" aria-labelledby="month-<?php echo esc_attr( sanitize_title( $month_label ) ); ?>">
					<h2 class="home__subheading" id="month-<?php echo esc_attr( sanitize_title( $month_label ) ); ?>"><?php echo esc_html( $month_label ); ?></h2>
					<ul class="card-grid card-grid--movies">
						<?php foreach ( $posts as $p ) : ?>
							<?php
							if ( ! $p instanceof WP_Post ) {
								continue;
							}
							$poster = jobsity_movie_poster_url( $p->ID );
							$rd     = get_post_meta( $p->ID, 'release_date', true );
							$genres = jobsity_movie_genres_string( $p->ID );
							?>
							<li class="card card--movie">
								<a class="card__link" href="<?php echo esc_url( get_permalink( $p ) ); ?>">
									<?php if ( $poster ) : ?>
										<img class="card__image" src="<?php echo esc_url( $poster ); ?>" alt="" loading="lazy" width="300" height="450">
									<?php else : ?>
										<div class="card__placeholder" aria-hidden="true"></div>
									<?php endif; ?>
									<div class="card__body">
										<h3 class="card__title"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
										<?php if ( is_string( $rd ) && '' !== $rd ) : ?>
											<p class="card__meta"><?php echo esc_html( $rd ); ?></p>
										<?php endif; ?>
										<?php if ( '' !== $genres ) : ?>
											<p class="card__meta card__meta--muted"><?php echo esc_html( $genres ); ?></p>
										<?php endif; ?>
									</div>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="empty-state"><?php esc_html_e( 'No upcoming movies found.', '' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="home__section">
		<h2 class="home__heading"><?php esc_html_e( 'Popular actors', '' ); ?></h2>

		<?php if ( $actors_query->have_posts() ) : ?>
			<ul class="card-grid card-grid--actors">
				<?php
				while ( $actors_query->have_posts() ) :
					$actors_query->the_post();
					$aphoto = jobsity_actor_photo_url( get_the_ID() );
					?>
					<li class="card card--actor">
						<a class="card__link" href="<?php the_permalink(); ?>">
							<?php if ( $aphoto ) : ?>
								<img class="card__image card__image--round" src="<?php echo esc_url( $aphoto ); ?>" alt="" loading="lazy" width="200" height="200">
							<?php else : ?>
								<div class="card__placeholder card__placeholder--round" aria-hidden="true"></div>
							<?php endif; ?>
							<div class="card__body">
								<h3 class="card__title"><?php the_title(); ?></h3>
							</div>
						</a>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<p class="empty-state"><?php esc_html_e( 'No actors found.', '' ); ?></p>
		<?php endif; ?>
	</section>
</div>

<?php
get_footer();
