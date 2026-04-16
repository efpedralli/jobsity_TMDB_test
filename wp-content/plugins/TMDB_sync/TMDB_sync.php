<?php
/**
 * Plugin Name: Movies Sync
 * Plugin URI: https://ojutu.com.br
 * Description: Sync moveis and actors from TMDB API to WordPress.
 * Version: 1.0
 * Author: Eduardo Pedralli
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );


require_once MS_PLUGIN_PATH . 'utils/register_image_as_thumb.php';
require_once MS_PLUGIN_PATH . 'inc/custom_post_types_register.php';
require_once MS_PLUGIN_PATH . 'utils/tmdb_api.php';
require_once MS_PLUGIN_PATH . 'inc/sync_movies.php';
require_once MS_PLUGIN_PATH . 'inc/sync_actors.php';

add_action( 'admin_menu', 'ms_register_admin_menu' );
add_action( 'admin_init', 'ms_handle_sync_actions' );

function ms_register_admin_menu() {
	add_menu_page(
		'Movies Sync',
		'Movies Sync',
		'manage_options',
		'movies-sync',
		'ms_render_admin_page',
		'dashicons-update',
		25
	);
}

function ms_render_admin_page() {
	?>
	<div class="wrap">
		<h1>Sincronização TMDB</h1>

		<?php
		if ( isset( $_GET['synced'] ) ) {
			if ( '1' === $_GET['synced'] ) {
				echo '<div class="notice notice-success is-dismissible"><p>Sincronização concluída com sucesso.</p></div>';
			} elseif ( '0' === $_GET['synced'] ) {
				echo '<div class="notice notice-error is-dismissible"><p>Erro ao conectar com a API do TMDB.</p></div>';
			}
		}
		?>

		<form method="post">
			<?php wp_nonce_field( 'ms_sync_action', 'ms_sync_nonce' ); ?>

			<p>
				<button type="submit" name="ms_sync_movies" value="1" class="button button-primary">
					Sincronizar Filmes
				</button>
			</p>

			<p>
				<button type="submit" name="ms_sync_actors" value="1" class="button button-secondary">
					Sincronizar Atores
				</button>
			</p>
		</form>
	</div>
	<?php
}

function ms_handle_sync_actions() {
	if ( ! isset( $_POST['ms_sync_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['ms_sync_nonce'], 'ms_sync_action' ) ) {
		return;
	}

	if ( isset( $_POST['ms_sync_movies'] ) ) {
		ms_sync_movies();
		wp_safe_redirect( admin_url( 'admin.php?page=movies-sync&synced=1' ) );
		exit;
	}

	if ( isset( $_POST['ms_sync_actors'] ) ) {
		ms_sync_actors();
		wp_safe_redirect( admin_url( 'admin.php?page=movies-sync&synced=1' ) );
		exit;
	}
}