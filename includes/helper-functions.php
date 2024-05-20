<?php
/**
 * The helper functions for Book List.
 *
 * @noinspection SqlNoDataSourceInspection
 *
 * @package      Book_List
 */

/**
 * Retrieve books from the database with optional search, pagination, and sorting.
 *
 * @param int    $page     Current page number.
 * @param int    $per_page Number of items per page.
 * @param string $orderby  Column to order by.
 * @param string $order    Sort order (ASC/DESC).
 * @param string $search   Search term.
 *
 * @return array Array of book objects.
 */
function book_list_get_books( $page = 1, $per_page = 10, $orderby = 'book_name', $order = 'ASC', $search = '' ) {
	global $wpdb;

	// Define allowed columns for ordering.
	$allowed_columns = array( 'id', 'book_name', 'author_name' );

	// Define valid order values.
	$valid_order  = strtoupper( $order );
	$valid_orders = array( 'ASC', 'DESC' );

	// Validate parameters.
	$page     = max( 1, absint( $page ) );
	$per_page = max( 1, absint( $per_page ) );
	$orderby  = in_array( $orderby, $allowed_columns, true ) ? $orderby : 'book_name';
	$order    = in_array( $valid_order, $valid_orders, true ) ? $valid_order : 'ASC';

	$table_name = $wpdb->prefix . 'books';
	$offset     = ( $page - 1 ) * $per_page;

	// Generate a unique cache key based on the function arguments.
	$args        = compact( 'page', 'per_page', 'orderby', 'order', 'search' );
	$args_json   = wp_json_encode( $args );
	$cache_key   = 'book_list_' . md5( $args_json );
	$cache_group = 'book_list';

	// Try to get cached data.
	$books = wp_cache_get( $cache_key, $cache_group );

	if ( false === $books ) {
		$search_query = '';

		if ( ! empty( $search ) ) {
			$search = '%' . $wpdb->esc_like( $search ) . '%';

			$search_query = $wpdb->prepare(
				'WHERE `book_name` LIKE %s OR `author_name` LIKE %s',
				$search,
				$search
			);
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$books = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name $search_query ORDER BY $orderby $order LIMIT %d, %d",
				$offset,
				$per_page
			)
		);
		// phpcs:enable

		// Store the results in cache.
		wp_cache_set( $cache_key, $books, $cache_group );
	}

	return $books;
}

/**
 * Adds a new book to the database.
 *
 * @param string $book_name   The name of the book.
 * @param string $author_name The name of the author.
 *
 * @return int|WP_Error The book id on success, or WP_Error on failure.
 */
function book_list_add_book( $book_name, $author_name ) {
	// Define the minimum and maximum lengths.
	$min_length = 2;
	$max_length = 50;

	// Check for empty fields.
	if ( empty( $book_name ) || empty( $author_name ) ) {
		return new WP_Error(
			'invalid_input',
			__( 'Invalid book name or author.', 'book-list' ),
			array( 'status' => 400 )
		);
	}

	// Check for minimum and maximum lengths.
	if ( mb_strlen( $book_name ) < $min_length || mb_strlen( $book_name ) > $max_length ) {
		/* translators: 1: Minimum length, 2: Maximum length */
		$message = sprintf( __( 'Book name must be between %1$d and %2$d characters.', 'book-list' ), $min_length, $max_length );

		return new WP_Error( 'invalid_input_length', $message, array( 'status' => 400 ) );
	}

	if ( mb_strlen( $author_name ) < $min_length || mb_strlen( $author_name ) > $max_length ) {
		/* translators: 1: Minimum length, 2: Maximum length */
		$message = sprintf( __( 'Author name must be between %1$d and %2$d characters.', 'book-list' ), $min_length, $max_length );

		return new WP_Error( 'invalid_input_length', $message, array( 'status' => 400 ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'books';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->insert(
		$table_name,
		array(
			'book_name'   => $book_name,
			'author_name' => $author_name,
		),
		array( '%s', '%s' )
	);

	if ( false === $result ) {
		$last_wpdb_error = $wpdb->last_error;
		$generic_message = __( 'Failed to add the book into the database.', 'book-list' );
		$error_message   = ! empty( $last_wpdb_error ) ? $last_wpdb_error : $generic_message;

		return new WP_Error( 'db_insert_error', $error_message, array( 'status' => 500 ) );
	}

	$book_id = $wpdb->insert_id;

	// Invalidate the 'book_list' cache group or flush all cache if group flushing is not supported.
	if ( wp_cache_supports( 'flush_group' ) ) {
		wp_cache_flush_group( 'book_list' );
	} else {
		wp_cache_flush();
	}

	return $book_id;
}

/**
 * Check if the current user has the create book permission.
 *
 * @return bool True if the user has permission, false otherwise.
 */
function book_list_create_book_permission_check() {
	return current_user_can( 'publish_posts' );
}

/**
 * Check if the current user has the view books permission.
 *
 * @return bool True if the user has permission, false otherwise.
 */
function book_list_view_books_permission_check() {
	return true;
}
