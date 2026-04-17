<?php
/**
 * Template Name: Wishlist
 *
 * A simple wishlist page that lists movies saved in the logged-in user's meta.
 */

get_header();

?>

<section class="wishlist">
	<header class="archive__header">
		<h1 class="archive__title"><?php esc_html_e( 'Your wishlist', '' ); ?></h1>
	</header>

	<?php if ( ! is_user_logged_in() ) : ?>
		<div class="notice notice--info">
			<p>
				<?php esc_html_e( 'You need to be logged in to see your wishlist.', '' ); ?>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', '' ); ?></a>
				<?php esc_html_e( 'or', '' ); ?>
				<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'register', '' ); ?></a>.
			</p>
		</div>
	<?php else : ?>
		<?php
		$list = jobsity_get_user_movie_wishlist();

		if ( empty( $list ) ) :
			?>
			<p class="empty-state"><?php esc_html_e( 'No movies saved yet.', '' ); ?></p>
		<?php else : ?>
			<?php
			$q = new WP_Query(
				array(
					'post_type'      => 'movie',
					'post_status'    => 'publish',
					'posts_per_page' => 60,
					'post__in'       => $list,
					'orderby'        => 'post__in',
				)
			);
			?>

			<?php if ( $q->have_posts() ) : ?>
				<ul class="card-grid card-grid--movies">
					<?php
					while ( $q->have_posts() ) :
						$q->the_post();

						$movie_id = get_the_ID();
						$poster   = jobsity_movie_poster_url( $movie_id );
						$rd       = get_post_meta( $movie_id, 'release_date', true );
						$genres   = jobsity_movie_genres_string( $movie_id );
						?>
						<li class="card card--movie">
							<div class="card__link card__link--static">
								<a class="card__media" href="<?php the_permalink(); ?>">
									<?php if ( $poster ) : ?>
										<img class="card__image" src="<?php echo esc_url( $poster ); ?>" alt="" loading="lazy" width="300" height="450">
									<?php else : ?>
										<div class="card__placeholder" aria-hidden="true"></div>
									<?php endif; ?>
								</a>
								<div class="card__body">
									<h2 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
									<?php if ( is_string( $rd ) && '' !== $rd ) : ?>
										<p class="card__meta"><?php echo esc_html( $rd ); ?></p>
									<?php endif; ?>
									<?php if ( '' !== $genres ) : ?>
										<p class="card__meta card__meta--muted"><?php echo esc_html( $genres ); ?></p>
									<?php endif; ?>

									<div class="card__actions">
										<button
											type="button"
											class="button button--ghost js-wishlist-toggle"
											data-movie-id="<?php echo esc_attr( (string) $movie_id ); ?>"
											data-in-wishlist="1"
											aria-pressed="true"
										>
											<?php echo esc_html( 'Remove from wishlist' ); ?>
										</button>
									</div>
								</div>
							</div>
						</li>
					<?php endwhile; ?>
				</ul>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p class="empty-state"><?php esc_html_e( 'No movies found.', '' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</section>

<?php
get_footer();

