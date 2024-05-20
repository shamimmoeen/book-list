<?php
/**
 * API Endpoints for Book List.
 *
 * @package Book_List
 */

/**
 * Register custom REST API endpoints for book insertion and retrieval.
 */
function book_list_rest_endpoints() {
	register_rest_route(
		'book-list/v1',
		'/books',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'book_list_rest_get_books',
				'permission_callback' => 'book_list_view_books_permission_check',
				'args'                => book_list_rest_get_books_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'book_list_rest_create_book',
				'permission_callback' => 'book_list_creat_book_permission_check',
				'args'                => book_list_rest_create_book_params(),
			),
			'schema' => 'book_list_rest_get_book_schema',
		)
	);
}

add_action( 'rest_api_init', 'book_list_rest_endpoints' );

/**
 * Callback function for retrieving books via REST API.
 *
 * @param WP_REST_Request $request The REST API request object.
 *
 * @return WP_REST_Response Response object.
 */
function book_list_rest_get_books( $request ) {
	$page     = $request->get_param( 'page' );
	$per_page = $request->get_param( 'per_page' );
	$orderby  = $request->get_param( 'orderby' );
	$order    = $request->get_param( 'order' );
	$search   = $request->get_param( 'search' );

	$results = book_list_get_books( $page, $per_page, $orderby, $order, $search );

	$books = array();

	foreach ( $results as $result ) {
		$data    = book_list_rest_prepare_item_for_response( $result );
		$books[] = book_list_rest_prepare_response_for_collection( $data );
	}

	return rest_ensure_response( $books );
}

/**
 * Matches the book data to the schema we want.
 *
 * @param object $item The book object.
 *
 * @return WP_REST_Response Response object.
 */
function book_list_rest_prepare_item_for_response( $item ) {
	$data = array();

	$schema = book_list_rest_get_book_schema();

	if ( isset( $schema['properties']['id'] ) ) {
		$data['id'] = $item->id;
	}

	if ( isset( $schema['properties']['book_name'] ) ) {
		$data['book_name'] = $item->book_name;
	}

	if ( isset( $schema['properties']['author_name'] ) ) {
		$data['author_name'] = $item->author_name;
	}

	return rest_ensure_response( $data );
}

/**
 * Prepare a response for inserting into a collection of responses.
 *
 * @param WP_REST_Response $response Response object.
 *
 * @return array|WP_REST_Response Response data, ready for insertion into collection data.
 */
function book_list_rest_prepare_response_for_collection( $response ) {
	if ( ! ( $response instanceof WP_REST_Response ) ) {
		return $response;
	}

	$data  = (array) $response->get_data();
	$links = rest_get_server()::get_compact_response_links( $response );

	if ( ! empty( $links ) ) {
		$data['_links'] = $links;
	}

	return $data;
}

/**
 * Retrieves the query params for book collections.
 *
 * @return array[]
 */
function book_list_rest_get_books_collection_params() {
	// Default query params for collections.
	$query_params = array(
		'page'     => array(
			'description'       => __( 'Current page of the collection.', 'book-list' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		),
		'per_page' => array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'book-list' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		),
		'search'   => array(
			'description'       => __( 'Limit results to those matching a string.', 'book-list' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		),
	);

	// Additional query params for our collections.
	$query_params['order'] = array(
		'default'     => 'ASC',
		'description' => __( 'Order sort attribute ascending or descending.', 'book-list' ),
		'enum'        => array( 'ASC', 'DESC' ),
		'type'        => 'string',
	);

	$query_params['orderby'] = array(
		'default'     => 'book_name',
		'description' => __( 'Sort collection by book attribute.', 'book-list' ),
		'enum'        => array( 'id', 'book_name', 'author_name' ),
		'type'        => 'string',
	);

	return $query_params;
}

/**
 * Callback function for adding a new book via REST API.
 *
 * @param WP_REST_Request $request The REST API request object.
 *
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function book_list_rest_create_book( $request ) {
	$book_id = book_list_add_book(
		$request->get_param( 'book_name' ),
		$request->get_param( 'author_name' )
	);

	if ( is_wp_error( $book_id ) ) {
		return $book_id;
	}

	$request->set_param( 'id', $book_id );

	$item = book_list_rest_prepare_item_for_database( $request );
	$data = book_list_rest_prepare_item_for_response( $item );

	$response = rest_ensure_response( $data );
	$response->set_status( 201 );

	return $response;
}

/**
 * Prepares a single book for creation or update.
 *
 * @param WP_REST_Request $request Full details about the request.
 *
 * @return object The book object.
 */
function book_list_rest_prepare_item_for_database( $request ) {
	$prepared_book = new stdClass();

	$schema = book_list_rest_get_book_schema();

	if ( isset( $request['id'] ) && ! empty( $schema['properties']['id'] ) ) {
		$prepared_book->id = $request['id'];
	}

	if ( isset( $request['book_name'] ) && ! empty( $schema['properties']['book_name'] ) ) {
		$prepared_book->book_name = $request['book_name'];
	}

	if ( isset( $request['author_name'] ) && ! empty( $schema['properties']['author_name'] ) ) {
		$prepared_book->author_name = $request['author_name'];
	}

	return $prepared_book;
}

/**
 * Retrieves the query params for create book.
 *
 * @return array[]
 */
function book_list_rest_create_book_params() {
	return rest_get_endpoint_args_for_schema( book_list_rest_get_book_schema() );
}

/**
 * Get our schema for books.
 */
function book_list_rest_get_book_schema() {
	return array(
		'$schema'    => 'http://json-schema.org/draft-04/schema#',
		'title'      => 'book',
		'type'       => 'object',
		'properties' => array(
			'id'          => array(
				'description' => __( 'Unique identifier for the book.', 'book-list' ),
				'type'        => 'integer',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			),
			'book_name'   => array(
				'description' => __( 'Display name for the book.', 'book-list' ),
				'type'        => 'string',
				'minLength'   => 2,
				'maxLength'   => 50,
				'context'     => array( 'embed', 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
				'required'    => true,
			),
			'author_name' => array(
				'description' => __( 'Author name for the book.', 'book-list' ),
				'type'        => 'string',
				'minLength'   => 2,
				'maxLength'   => 50,
				'context'     => array( 'embed', 'view', 'edit' ),
				'arg_options' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
				'required'    => true,
			),
		),
	);
}
