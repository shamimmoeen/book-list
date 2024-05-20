<?php
/**
 * Plugin Name:       Book List
 * Plugin URI:        https://github.com/shamimmoeen/book-list
 * Description:       A simple plugin to manage a list of books and their authors.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mainul Hassan Main
 * Author URI:        https://mainulhassan.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/shamimmoeen/book-list
 * Text Domain:       book-list
 * Domain Path:       /languages
 *
 * @package           Book_List
 */

/**
 * Creates the necessary database table when activating the plugin.
 *
 * @noinspection SqlNoDataSourceInspection
 */
function book_list_plugin_activate() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'books';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        book_name varchar(50) NOT NULL,
        author_name varchar(50) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'book_list_plugin_activate' );

/**
 * Load the required files.
 *
 * phpcs:disable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar
 */
// require_once plugin_dir_path( __FILE__ ) . 'includes/api-endpoints.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-book-list-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
// phpcs:enable
