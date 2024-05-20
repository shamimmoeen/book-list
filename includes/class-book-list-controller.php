<?php
/**
 * REST API: Book_List_Controller class
 *
 * @package Book_List
 */

/**
 * Core class used to manage books via the REST API.
 *
 * @see WP_REST_Controller
 */
class Book_List_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'book-list/v1';
		$this->rest_base = 'books';
	}

	/**
	 * Register the routes for books controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

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
	 * Get the books.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$book_id = book_list_add_book(
			$request->get_param( 'book_name' ),
			$request->get_param( 'author_name' )
		);

		if ( is_wp_error( $book_id ) ) {
			return $book_id;
		}

		$request->set_param( 'id', $book_id );

		$item = $this->prepare_item_for_database( $request );
		$data = $this->prepare_item_for_response( $item, $request );

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
	protected function prepare_item_for_database( $request ) {
		$prepared_book = new stdClass();

		$schema = $this->get_item_schema();

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
	 * Retrieves the book's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
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

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Prepares a single book output for response.
	 *
	 * @param object          $item    The book object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$book = $item;

		$fields = $this->get_fields_for_response( $request );
		$data   = array();

		if ( in_array( 'id', $fields, true ) ) {
			$data['id'] = $book->id;
		}

		if ( in_array( 'book_name', $fields, true ) ) {
			$data['book_name'] = $book->book_name;
		}

		if ( in_array( 'author_name', $fields, true ) ) {
			$data['author_name'] = $book->author_name;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		return rest_ensure_response( $data );
	}

	/**
	 * Checks if a given request has access create books.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool True if the request has access to create items, false otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		return book_list_creat_book_permission_check();
	}

	/**
	 * Get the items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function get_items( $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$orderby  = $request->get_param( 'orderby' );
		$order    = $request->get_param( 'order' );
		$search   = $request->get_param( 'search' );

		$results = book_list_get_books( $page, $per_page, $orderby, $order, $search );

		$books = array();

		foreach ( $results as $result ) {
			$data    = $this->prepare_item_for_response( $result, $request );
			$books[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $books );
	}

	/**
	 * Permissions check for getting all books.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool True if the request has access to create items, false otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return book_list_view_books_permission_check();
	}
}

/**
 * Register the routes from our books controller.
 */
function book_list_register_books_controller() {
	$controller = new Book_List_Controller();
	$controller->register_routes();
}

add_action( 'rest_api_init', 'book_list_register_books_controller' );
