<?php
/**
 * The shortcodes for Book List.
 *
 * @package Book_List
 */

/**
 * Shortcode callback to display the book submission form.
 *
 * @return string HTML content of the form.
 */
function book_list_form_shortcode() {
	if ( ! book_list_creat_book_permission_check() ) {
		return esc_html__( 'You do not have permission to add book.', 'book-list' );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$book_added = isset( $_GET['book_added'] );
	ob_start();
	?>
	<form method="post" id="book-form">
		<?php if ( $book_added ) : ?>
			<p><?php esc_html_e( 'Book added successfully!', 'book-list' ); ?></p>
		<?php endif; ?>

		<?php wp_nonce_field( 'submit_book', 'book_nonce' ); ?>

		<label for="book-name"><?php esc_html_e( 'Book Name', 'book-list' ); ?>:</label><br>
		<input type="text" id="book-name" name="book_name"><br>

		<label for="author-name"><?php esc_html_e( 'Author Name', 'book-list' ); ?>:</label><br>
		<input type="text" id="author-name" name="author_name"><br><br>

		<input type="submit" name="submit_book" value="<?php esc_attr_e( 'Submit', 'book-list' ); ?>">
	</form>
	<?php
	return ob_get_clean();
}

add_shortcode( 'book_form', 'book_list_form_shortcode' );

/**
 * Handle the form submission for adding a new book.
 */
function book_list_handle_form_submission() {
	if ( isset( $_POST['submit_book'] ) ) {
		$nonce = isset( $_POST['book_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['book_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'submit_book' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'book-list' ) );
		}

		if ( ! book_list_creat_book_permission_check() ) {
			wp_die( esc_html__( 'Permission denied.', 'book-list' ) );
		}

		$book_name   = isset( $_POST['book_name'] ) ? sanitize_text_field( wp_unslash( $_POST['book_name'] ) ) : '';
		$author_name = isset( $_POST['author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '';

		$result = book_list_add_book( $book_name, $author_name );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		// Redirect to avoid form resubmission.
		wp_safe_redirect( add_query_arg( 'book_added', 'true' ) );
		exit;
	}
}

add_action( 'init', 'book_list_handle_form_submission' );

/**
 * Shortcode callback to display the list of books.
 *
 * @return string HTML content of the book list.
 */
function book_list_shortcode() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page     = isset( $_GET['book_page'] ) ? absint( $_GET['book_page'] ) : 1;
	$per_page = 10;
	$orderby  = 'book_name';
	$order    = 'ASC';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$search = isset( $_GET['book_search'] ) ? sanitize_text_field( wp_unslash( $_GET['book_search'] ) ) : '';

	$books = book_list_get_books( $page, $per_page, $orderby, $order, $search );

	ob_start();
	?>
	<form method="get" id="search-form">
		<label for="book_search"><?php esc_html_e( 'Search', 'book-list' ); ?>:</label>
		<input type="text" id="book_search" name="book_search" value="<?php echo esc_attr( $search ); ?>">
		<input type="submit" value="<?php esc_attr_e( 'Search', 'book-list' ); ?>">
	</form>

	<table class="book-list">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Book Name', 'book-list' ); ?></th>
				<th><?php esc_html_e( 'Author Name', 'book-list' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $books ) : ?>
				<?php foreach ( $books as $book ) : ?>
					<tr>
						<td><?php echo esc_html( $book->book_name ); ?></td>
						<td><?php echo esc_html( $book->author_name ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="2"><?php esc_html_e( 'No books found.', 'book-list' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php
	return ob_get_clean();
}

add_shortcode( 'book_list', 'book_list_shortcode' );
