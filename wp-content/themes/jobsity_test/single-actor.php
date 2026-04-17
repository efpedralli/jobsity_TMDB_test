<?php
/**
 * Single actor template.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$actor_id     = get_the_ID();
	$photo        = jobsity_actor_photo_url( $actor_id );
	$birthday     = get_post_meta( $actor_id, 'birthday', true );
	$pob          = get_post_meta( $actor_id, 'place_of_birth', true );
	$deathday     = get_post_meta( $actor_id, 'deathday', true );
	$website      = get_post_meta( $actor_id, 'website', true );
	$popularity   = get_post_meta( $actor_id, 'popularity', true );
	$bio_meta     = get_post_meta( $actor_id, 'bio', true );
	$movie_ids    = get_post_meta( $actor_id, 'related_movie_ids', true );
	$body         = get_the_content();

	if ( ! is_array( $movie_ids ) ) {
		$movie_ids = array();
	}

	$gallery = jobsity_get_actor_gallery_urls( $actor_id, 10 );

	$film_rows = array();

	foreach ( $movie_ids as $mid ) {
		$mid = (int) $mid;
		if ( $mid <= 0 ) {
			continue;
		}
		$m = get_post( $mid );
		if ( ! $m || 'movie' !== $m->post_type || 'publish' !== $m->post_status ) {
			continue;
		}
		$rd = get_post_meta( $mid, 'release_date', true );
		$film_rows[] = array(
			'post'         => $m,
			'release_date' => is_string( $rd ) ? $rd : '',
			'character'    => get_post_meta( $actor_id, 'character_name_' . $mid, true ),
		);
	}

	usort(
		$film_rows,
		function ( $a, $b ) {
			$da = ! empty( $a['release_date'] ) && is_string( $a['release_date'] ) ? $a['release_date'] : '0000-00-00';
			$db = ! empty( $b['release_date'] ) && is_string( $b['release_date'] ) ? $b['release_date'] : '0000-00-00';

			return strcmp( $db, $da );
		}
	);
	?>

	<article <?php post_class( 'single single--actor' ); ?>>
		<header class="single__header single__header--actor">
			<?php if ( $photo ) : ?>
				<img class="single__photo" src="<?php echo esc_url( $photo ); ?>" alt="" width="280" height="280" loading="lazy">
			<?php endif; ?>
			<div class="single__heading">
				<h1 class="single__title"><?php the_title(); ?></h1>
				<?php if ( '' !== (string) $popularity ) : ?>
					<p class="single__popularity"><?php esc_html_e( 'Popularity', '' ); ?>: <?php echo esc_html( (string) $popularity ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<dl class="meta-list meta-list--inline">
			<?php if ( is_string( $birthday ) && '' !== $birthday ) : ?>
				<div class="meta-list__row">
					<dt><?php esc_html_e( 'Birthday', '' ); ?></dt>
					<dd><?php echo esc_html( $birthday ); ?></dd>
				</div>
			<?php endif; ?>
			<?php if ( is_string( $pob ) && '' !== $pob ) : ?>
				<div class="meta-list__row">
					<dt><?php esc_html_e( 'Place of birth', '' ); ?></dt>
					<dd><?php echo esc_html( $pob ); ?></dd>
				</div>
			<?php endif; ?>
			<?php if ( is_string( $deathday ) && '' !== $deathday ) : ?>
				<div class="meta-list__row">
					<dt><?php esc_html_e( 'Day of death', '' ); ?></dt>
					<dd><?php echo esc_html( $deathday ); ?></dd>
				</div>
			<?php endif; ?>
			<?php if ( is_string( $website ) && '' !== $website ) : ?>
				<div class="meta-list__row">
					<dt><?php esc_html_e( 'Website', '' ); ?></dt>
					<dd><a href="<?php echo esc_url( $website ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( $website ); ?></a></dd>
				</div>
			<?php endif; ?>
		</dl>

		<section class="block">
			<h2 class="block__title"><?php esc_html_e( 'Bio', '' ); ?></h2>
			<div class="block__content prose">
				<?php
				if ( '' !== trim( (string) $body ) ) {
					the_content();
				} elseif ( is_string( $bio_meta ) && '' !== $bio_meta ) {
					echo wp_kses_post( wpautop( $bio_meta ) );
				}
				?>
			</div>
		</section>

		<?php if ( ! empty( $gallery ) ) : ?>
			<section class="block">
				<h2 class="block__title"><?php esc_html_e( 'Gallery', '' ); ?></h2>
				<ul class="gallery-grid">
					<?php foreach ( $gallery as $gurl ) : ?>
						<li class="gallery-grid__item">
							<a href="<?php echo esc_url( $gurl ); ?>" target="_blank" rel="noopener noreferrer">
								<img src="<?php echo esc_url( $gurl ); ?>" alt="" loading="lazy" width="200" height="200">
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $film_rows ) ) : ?>
			<section class="block">
				<h2 class="block__title"><?php esc_html_e( 'Movies', '' ); ?></h2>
				<ul class="filmography">
					<?php foreach ( $film_rows as $row ) : ?>
						<?php
						/** @var WP_Post $mp */
						$mp = $row['post'];
						$mposter = jobsity_movie_poster_url( $mp->ID );
						$cname   = isset( $row['character'] ) && is_string( $row['character'] ) ? $row['character'] : '';
						$rdate   = isset( $row['release_date'] ) ? $row['release_date'] : '';
						?>
						<li class="filmography__row">
							<a class="filmography__poster" href="<?php echo esc_url( get_permalink( $mp ) ); ?>">
								<?php if ( $mposter ) : ?>
									<img src="<?php echo esc_url( $mposter ); ?>" alt="" loading="lazy" width="92" height="138">
								<?php else : ?>
									<span class="filmography__placeholder" aria-hidden="true"></span>
								<?php endif; ?>
							</a>
							<div class="filmography__body">
								<h3 class="filmography__title"><a href="<?php echo esc_url( get_permalink( $mp ) ); ?>"><?php echo esc_html( get_the_title( $mp ) ); ?></a></h3>
								<?php if ( '' !== $cname ) : ?>
									<p class="filmography__character"><?php echo esc_html( $cname ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $rdate ) : ?>
									<p class="filmography__date"><?php echo esc_html( $rdate ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</article>

	<?php
endwhile;

get_footer();
