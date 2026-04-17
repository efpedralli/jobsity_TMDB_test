<?php
/**
 * Search form (movies + actors).
 */
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="search-form__label">
		<span class="screen-reader-text"><?php esc_html_e( 'Search', '' ); ?></span>
		<input
			type="search"
			class="search-form__input"
			placeholder="<?php echo esc_attr( 'Search for movie or actor' ); ?>"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			name="s"
		/>
	</label>
	<button type="submit" class="search-form__submit button"><?php echo esc_html( 'Find' ); ?></button>
</form>

