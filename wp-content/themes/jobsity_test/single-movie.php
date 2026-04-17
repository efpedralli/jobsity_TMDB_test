<?php
/**
 * Single movie template.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$movie_id            = get_the_ID();
	$trailer_url         = get_post_meta( $movie_id, 'trailer_url', true );
	$embed               = is_string( $trailer_url ) ? jobsity_youtube_embed_url( $trailer_url ) : '';
	$poster              = jobsity_movie_poster_url( $movie_id );
	$release_date        = get_post_meta( $movie_id, 'release_date', true );
	$original_language   = get_post_meta( $movie_id, 'original_language', true );
	$popularity          = get_post_meta( $movie_id, 'popularity', true );
	$alt_titles          = get_post_meta( $movie_id, 'alternative_titles', true );
	$production = get_post_meta( $movie_id, 'production_companies', true );
	$reviews             = get_post_meta( $movie_id, 'reviews', true );
	$similar             = get_post_meta( $movie_id, 'similar_movies', true );
	$actor_ids           = get_post_meta( $movie_id, 'related_actor_ids', true );
	$overview = get_post_meta( $movie_id, 'overview', true );
	$body = get_the_content();

	if ( ! is_array( $alt_titles ) ) {
		$alt_titles = array();
	}
	if ( ! is_array( $production ) ) {
		$production = array();
	}
	if ( ! is_array( $reviews ) ) {
		$reviews = array();
	}
	if ( ! is_array( $similar ) ) {
		$similar = array();
	}
	if ( ! is_array( $actor_ids ) ) {
		$actor_ids = array();
	}

	$genres_str = jobsity_movie_genres_string( $movie_id );
	?>

	<article <?php post_class( 'single single--movie' ); ?>>
		<header class="single__header">
			<h1 class="single__title"><?php the_title(); ?></h1>
			<?php if ( '' !== (string) $popularity ) : ?>
				<p class="single__popularity"><?php esc_html_e( 'Popularity', '' ); ?>: <?php echo esc_html( (string) $popularity ); ?></p>
			<?php endif; ?>
		</header>

		<div class="single__layout single__layout--movie">
			<div class="single__primary">
				<?php if ( $embed ) : ?>
					<div class="embed embed--trailer">
						<iframe
							src="<?php echo esc_url( $embed ); ?>"
							title="<?php echo esc_attr( get_the_title() . ' — ' . __( 'Trailer', '' ) ); ?>"
							loading="lazy"
							allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
							allowfullscreen
						></iframe>
					</div>
				<?php elseif ( is_string( $trailer_url ) && '' !== $trailer_url ) : ?>
					<p class="single__trailer-link"><a href="<?php echo esc_url( $trailer_url ); ?>"><?php esc_html_e( 'Watch trailer', '' ); ?></a></p>
				<?php endif; ?>

				<section class="block">
					<h2 class="block__title"><?php esc_html_e( 'Overview', '' ); ?></h2>
					<div class="block__content prose">
						<?php
						if ( '' !== trim( (string) $body ) ) {
							the_content();
						} elseif ( is_string( $overview ) && '' !== $overview ) {
							echo wp_kses_post( wpautop( $overview ) );
						}
						?>
					</div>
				</section>

				<?php if ( ! empty( $reviews ) ) : ?>
					<section class="block">
						<h2 class="block__title"><?php esc_html_e( 'Reviews', '' ); ?></h2>
						<ul class="review-list">
							<?php foreach ( $reviews as $rev ) : ?>
								<?php
								if ( ! is_array( $rev ) ) {
									continue;
								}
								$author = isset( $rev['author'] ) ? (string) $rev['author'] : '';
								$content = isset( $rev['content'] ) ? (string) $rev['content'] : '';
								?>
								<li class="review-list__item">
									<?php if ( '' !== $author ) : ?>
										<p class="review-list__author"><strong><?php echo esc_html( $author ); ?></strong></p>
									<?php endif; ?>
									<?php if ( '' !== $content ) : ?>
										<div class="review-list__content prose"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $similar ) ) : ?>
					<section class="block">
						<h2 class="block__title"><?php esc_html_e( 'Similar movies', '' ); ?></h2>
						<ul class="card-grid card-grid--compact">
							<?php foreach ( $similar as $item ) : ?>
								<?php
								if ( ! is_array( $item ) ) {
									continue;
								}
								$stmdb = isset( $item['tmdb_id'] ) ? $item['tmdb_id'] : '';
								$stitle = isset( $item['title'] ) ? (string) $item['title'] : '';
								$sdate  = isset( $item['release_date'] ) ? (string) $item['release_date'] : '';
								$spath  = isset( $item['poster_path'] ) ? (string) $item['poster_path'] : '';
								$sposter = '' !== $spath ? 'https://image.tmdb.org/t/p/w342' . $spath : '';
								$local = jobsity_find_movie_by_tmdb_id( $stmdb );
								$link    = $local ? get_permalink( $local ) : '';
								?>
								<li class="card card--movie card--compact">
									<?php if ( $link ) : ?>
										<a class="card__link" href="<?php echo esc_url( $link ); ?>">
									<?php else : ?>
										<div class="card__link card__link--static">
									<?php endif; ?>
										<?php if ( $sposter ) : ?>
											<img class="card__image" src="<?php echo esc_url( $sposter ); ?>" alt="" loading="lazy" width="171" height="256">
										<?php else : ?>
											<div class="card__placeholder" aria-hidden="true"></div>
										<?php endif; ?>
										<div class="card__body">
											<h3 class="card__title"><?php echo esc_html( $stitle ); ?></h3>
											<?php if ( '' !== $sdate ) : ?>
												<p class="card__meta"><?php echo esc_html( $sdate ); ?></p>
											<?php endif; ?>
										</div>
									<?php if ( $link ) : ?>
										</a>
									<?php else : ?>
										</div>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			</div>

			<aside class="single__aside">
				<?php if ( $poster ) : ?>
					<figure class="single__poster">
						<img src="<?php echo esc_url( $poster ); ?>" alt="" width="400" height="600" loading="lazy">
					</figure>
				<?php endif; ?>

				<dl class="meta-list">
					<?php if ( $genres_str ) : ?>
						<div class="meta-list__row">
							<dt><?php esc_html_e( 'Genre', '' ); ?></dt>
							<dd><?php echo esc_html( $genres_str ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $alt_titles ) ) : ?>
						<div class="meta-list__row">
							<dt><?php esc_html_e( 'Alternative titles', '' ); ?></dt>
							<dd><?php echo esc_html( implode( ', ', array_map( 'strval', $alt_titles ) ) ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( is_string( $release_date ) && '' !== $release_date ) : ?>
						<div class="meta-list__row">
							<dt><?php esc_html_e( 'Release date', '' ); ?></dt>
							<dd><?php echo esc_html( $release_date ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( is_string( $original_language ) && '' !== $original_language ) : ?>
						<div class="meta-list__row">
							<dt><?php esc_html_e( 'Original language', '' ); ?></dt>
							<dd><?php echo esc_html( strtoupper( $original_language ) ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $production ) ) : ?>
						<div class="meta-list__row">
							<dt><?php esc_html_e( 'Production companies', '' ); ?></dt>
							<dd><?php echo esc_html( implode( ', ', array_map( 'strval', $production ) ) ); ?></dd>
						</div>
					<?php endif; ?>
				</dl>
			</aside>
		</div>

		<?php if ( ! empty( $actor_ids ) ) : ?>
			<section class="block block--cast">
				<h2 class="block__title"><?php esc_html_e( 'Cast', '' ); ?></h2>
				<ul class="cast-list">
					<?php foreach ( $actor_ids as $aid ) : ?>
						<?php
						$aid = (int) $aid;
						if ( $aid <= 0 ) {
							continue;
						}
						$char = get_post_meta( $aid, 'character_name_' . $movie_id, true );
						?>
						<li class="cast-list__item">
							<a href="<?php echo esc_url( get_permalink( $aid ) ); ?>"><?php echo esc_html( get_the_title( $aid ) ); ?></a>
							<?php if ( is_string( $char ) && '' !== $char ) : ?>
								<span class="cast-list__character"><?php echo esc_html( $char ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</article>

	<?php
endwhile;

get_footer();
