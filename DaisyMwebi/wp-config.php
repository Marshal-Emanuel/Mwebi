<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'daisymwebi' );

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
define( 'AUTH_KEY',         'WNXS=?<?sIC{fPwXCcz]%rU>iFW7&Ua^_fxHY^X!fV?DSbB_m}HJHED^!!-5STmR' );
define( 'SECURE_AUTH_KEY',  'U^_%ryvRq4=:/w_iA/ bC*KOy!A+e4bEsg,[Ga.S$!raAwLa-3;p|$#(yIfgj`Jh' );
define( 'LOGGED_IN_KEY',    '7M? ~<pqg-q;QTiiN0GTcfg{$YRrIpemdh/}}A%=rh &T0$}q;UA<1^#Kg$x$@-h' );
define( 'NONCE_KEY',        'gPjt~C|mitm-h`FKwrB-~fG/rn6$h,~Pes^K76xov6Nqxkf/HNq([lBIu](`;iT0' );
define( 'AUTH_SALT',        '_4EY1VE.ZBb;Lv=^ngsNa)]}{%aB+*RSdTqo>Rs%X,:u=))p`0oe+<qoU-=pr C?' );
define( 'SECURE_AUTH_SALT', '}dst$<P NV@U1e779r[Z60v;;Tt?]2f?7,]x}5YUfm_:/>ean.h!18;{}A{vLq@I' );
define( 'LOGGED_IN_SALT',   ')BuR$$0*)o3< J* v$%Faa7Ff$P>2OhL.ggYw{MM.}3e*h[%XL9I3^B6M%hJYlJf' );
define( 'NONCE_SALT',       'd 4N?g4(AU!a[39$D@Z$B4PF*CHs/_2Wk&?3m:f%iQ9xIrR:SusPNUG*m? SB!N#' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
