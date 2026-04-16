<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'jobsity_test' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ';5_xpv).5GvdL<};SS0(x&E53BKJEzHifKV+2G+o4APtuY#tA$?rGeM`ttQ#;>^#' );
define( 'SECURE_AUTH_KEY',  '`%qcylWQ$I>s?>]H#8hdt3d4Eo7KO**trudP%-IoBKIV@f/ny3mcmVbMC+kxpn<R' );
define( 'LOGGED_IN_KEY',    '{V9|LHG3O}~Jcj$ >pOW;^w{(88pb3OC64:ll1Xef`CIwO}zZxA$=oKaoeeYX/Ac' );
define( 'NONCE_KEY',        'yc_eQc/AoqiTE2|%0SEhQB82*09jyDK)ENu^+Kf{82Ko1+5-<F00vAc;r#J-p%b.' );
define( 'AUTH_SALT',        'i,E}$L6PEPr;]=ARVZi,w30fj:+QmB0!K6oZ^Jl>SO+rR%<~A-%dS&KLJ:$Ie*oR' );
define( 'SECURE_AUTH_SALT', '=rY`~ip1Z|Kg=>60{GlT5=j&{,MYkx6.`,{#jfUiFP%E;?t6@E8?Pa2HP&~y %3M' );
define( 'LOGGED_IN_SALT',   'z;^&LU2UO0.GPuK$:HKDIaU,;r(/DbFFQKAL]+D#FNazu8<yKw4L77<`o28Egq@s' );
define( 'NONCE_SALT',       '2tf{Mk`~%{1bX1nqBjs3Ja+bKg@L<Gss^UnfcKCT} iK j/Vx/kE?9b1vGOZ _11' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_jobsity_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', TRUE );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

define( 'TMDB_API_KEY', '140994c38192be8a9a610d7b31cd9d00' );
define( 'TMDB_API_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxNDA5OTRjMzgxOTJiZThhOWE2MTBkN2IzMWNkOWQwMCIsIm5iZiI6MTc3NjM0MzMyNS4yNCwic3ViIjoiNjllMGQ5MWQxMzEwYTIxNDlhYjgwMTIwIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.93P8I0B4LzQWFZv-5hWCbkd8YcG3ruJQClium8vbyDA' );	