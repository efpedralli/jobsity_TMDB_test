<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="site-header__inner">
		<p class="site-title">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		</p>
		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'eduardo' ); ?>">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'movie' ) ); ?>"><?php esc_html_e( 'Movies', 'eduardo' ); ?></a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'actor' ) ); ?>"><?php esc_html_e( 'Actors', 'eduardo' ); ?></a>
		</nav>
	</div>
</header>

<main class="site-main">
