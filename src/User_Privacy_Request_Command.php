<?php

use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Manages user privacy requests (GDPR personal data export and erasure).
 *
 * ## EXAMPLES
 *
 *     # List all privacy requests.
 *     $ wp user privacy-request list
 *     +----+-------------------+----------------------+-------------------+--------------------+
 *     | ID | user_email        | action_name          | status            | created_timestamp  |
 *     +----+-------------------+----------------------+-------------------+--------------------+
 *     | 1  | bob@example.com   | export_personal_data | request-pending   | 1713779524         |
 *     +----+-------------------+----------------------+-------------------+--------------------+
 *
 *     # Create a new data export request.
 *     $ wp user privacy-request create bob@example.com export_personal_data
 *     Success: Created privacy request 1.
 *
 *     # Erase personal data for request 1.
 *     $ wp user privacy-request erase 1
 *     Success: Erased personal data for request 1.
 *
 *     # Export personal data for request 1.
 *     $ wp user privacy-request export 1
 *     Success: Exported personal data to: /var/www/html/wp-content/uploads/wp-personal-data-exports/wp-personal-data-export-bob-example-com-1.zip
 *
 *     # Mark request 1 as complete.
 *     $ wp user privacy-request complete 1
 *     Success: Completed 1 of 1 privacy requests.
 *
 *     # Delete request 1.
 *     $ wp user privacy-request delete 1
 *     Success: Deleted 1 of 1 privacy requests.
 *
 * @package wp-cli
 */
final class User_Privacy_Request_Command {

	/**
	 * Default fields for displaying privacy requests.
	 *
	 * @var array<string>
	 */
	const REQUEST_FIELDS = [
		'ID',
		'user_email',
		'action_name',
		'status',
		'created_timestamp',
	];

	/**
	 * Lists privacy requests.
	 *
	 * ## OPTIONS
	 *
	 * [--action-type=<action-type>]
	 * : Filter the list by action type.
	 * ---
	 * options:
	 *   - export_personal_data
	 *   - remove_personal_data
	 * ---
	 *
	 * [--status=<status>]
	 * : Filter the list by request status.
	 * ---
	 * options:
	 *   - request-pending
	 *   - request-confirmed
	 *   - request-failed
	 *   - request-completed
	 * ---
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each request.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each request:
	 *
	 * * ID
	 * * user_email
	 * * action_name
	 * * status
	 * * created_timestamp
	 *
	 * These fields are optionally available:
	 *
	 * * user_id
	 * * confirmed_timestamp
	 * * completed_timestamp
	 *
	 * ## EXAMPLES
	 *
	 *     # List all privacy requests.
	 *     $ wp user privacy-request list
	 *     +----+-------------------+----------------------+-------------------+--------------------+
	 *     | ID | user_email        | action_name          | status            | created_timestamp  |
	 *     +----+-------------------+----------------------+-------------------+--------------------+
	 *     | 1  | bob@example.com   | export_personal_data | request-pending   | 1713779524         |
	 *     +----+-------------------+----------------------+-------------------+--------------------+
	 *
	 *     # List only export requests.
	 *     $ wp user privacy-request list --action-type=export_personal_data
	 *
	 *     # List only completed requests.
	 *     $ wp user privacy-request list --status=request-completed
	 *
	 *     # List request IDs only.
	 *     $ wp user privacy-request list --format=ids
	 *     1 2
	 *
	 * @subcommand list
	 *
	 * @param list<string> $args Positional arguments.
	 * @param array{action-type?: string, status?: string, field?: string, fields?: string, format?: string} $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$query_args = [
			'post_type'      => 'user_request',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		];

		$action_type = Utils\get_flag_value( $assoc_args, 'action-type' );
		if ( $action_type ) {
			$query_args['post_name__in'] = [ sanitize_key( $action_type ) ];
		}

		$status = Utils\get_flag_value( $assoc_args, 'status' );
		if ( $status ) {
			$query_args['post_status'] = sanitize_key( $status );
		}

		$posts    = get_posts( $query_args );
		$requests = array_map( [ $this, 'get_request_data' ], $posts );
		$requests = array_filter( $requests );

		$format = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = self::REQUEST_FIELDS;
		}

		$formatter = new Formatter( $assoc_args, self::REQUEST_FIELDS );

		if ( 'ids' === $format ) {
			WP_CLI::line( implode( ' ', wp_list_pluck( $requests, 'ID' ) ) );
		} else {
			$formatter->display_items( $requests );
		}
	}

	/**
	 * Creates a privacy request for a user.
	 *
	 * ## OPTIONS
	 *
	 * <email>
	 * : The email address of the user to create the request for.
	 *
	 * <action-type>
	 * : The type of personal data request.
	 * ---
	 * options:
	 *   - export_personal_data
	 *   - remove_personal_data
	 * ---
	 *
	 * [--status=<status>]
	 * : The initial status of the request.
	 * ---
	 * default: pending
	 * options:
	 *   - pending
	 *   - confirmed
	 * ---
	 *
	 * [--send-email]
	 * : If set, sends a confirmation email to the user.
	 *
	 * [--porcelain]
	 * : Output just the new request ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a new data export request with pending status.
	 *     $ wp user privacy-request create bob@example.com export_personal_data
	 *     Success: Created privacy request 1.
	 *
	 *     # Create a confirmed data erasure request.
	 *     $ wp user privacy-request create bob@example.com remove_personal_data --status=confirmed
	 *     Success: Created privacy request 2.
	 *
	 *     # Get just the new request ID.
	 *     $ wp user privacy-request create bob@example.com export_personal_data --porcelain
	 *     3
	 *
	 * @param array{string, string} $args Positional arguments.
	 * @param array{status?: string, send-email?: bool, porcelain?: bool} $assoc_args Associative arguments.
	 */
	public function create( $args, $assoc_args ) {
		list( $email_address, $action_name ) = $args;

		$status = Utils\get_flag_value( $assoc_args, 'status', 'pending' );

		$request_id = wp_create_user_request( $email_address, $action_name, [], $status );

		if ( is_wp_error( $request_id ) ) {
			WP_CLI::error( $request_id );
		}

		if ( Utils\get_flag_value( $assoc_args, 'send-email', false ) ) {
			wp_send_user_request( $request_id );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain', false ) ) {
			WP_CLI::line( (string) $request_id );
			return;
		}

		WP_CLI::success( "Created privacy request {$request_id}." );
	}

	/**
	 * Deletes one or more privacy requests.
	 *
	 * ## OPTIONS
	 *
	 * <request-id>...
	 * : One or more IDs of the privacy requests to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete privacy request 1.
	 *     $ wp user privacy-request delete 1
	 *     Privacy request 1 deleted.
	 *     Success: Deleted 1 of 1 privacy requests.
	 *
	 *     # Delete multiple privacy requests.
	 *     $ wp user privacy-request delete 1 2 3
	 *     Privacy request 1 deleted.
	 *     Privacy request 2 deleted.
	 *     Privacy request 3 deleted.
	 *     Success: Deleted 3 of 3 privacy requests.
	 *
	 * @param list<string> $args Positional arguments.
	 * @param array{} $assoc_args Associative arguments.
	 */
	public function delete( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$successes = 0;
		$errors    = 0;

		foreach ( $args as $request_id ) {
			$request_id = (int) $request_id;
			$request    = $this->get_request( $request_id );

			if ( ! $request ) {
				WP_CLI::warning( "Could not find privacy request with ID {$request_id}." );
				++$errors;
				continue;
			}

			$result = wp_delete_post( $request_id, true );

			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( "Failed deleting privacy request {$request_id}: " . $result->get_error_message() );
				++$errors;
			} else {
				WP_CLI::log( "Privacy request {$request_id} deleted." );
				++$successes;
			}
		}

		$count = count( $args );
		Utils\report_batch_operation_results( 'privacy request', 'delete', $count, $successes, $errors );
	}

	/**
	 * Erases personal data for a given privacy request.
	 *
	 * Runs all registered data erasers for the email address associated with the
	 * request, then marks the request as completed.
	 *
	 * ## OPTIONS
	 *
	 * <request-id>
	 * : The ID of the remove_personal_data privacy request to process.
	 *
	 * ## EXAMPLES
	 *
	 *     # Erase personal data for request 1.
	 *     $ wp user privacy-request erase 1
	 *     Success: Erased personal data for request 1.
	 *
	 * @param array{string} $args Positional arguments.
	 * @param array{} $assoc_args Associative arguments.
	 */
	public function erase( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		list( $request_id ) = $args;
		$request_id         = (int) $request_id;

		$request = $this->get_request_check( $request_id );

		if ( 'remove_personal_data' !== $request->action_name ) {
			WP_CLI::error( "Request {$request_id} is not a 'remove_personal_data' request." );
		}

		$email_address = $request->email;
		$erasers       = apply_filters( 'wp_privacy_personal_data_erasers', [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$messages      = [];

		foreach ( $erasers as $eraser_key => $eraser ) {
			if ( ! isset( $eraser['callback'] ) || ! is_callable( $eraser['callback'] ) ) {
				WP_CLI::warning( "Eraser '{$eraser_key}' does not have a valid callback." );
				continue;
			}

			$page = 1;
			do {
				$response = call_user_func( $eraser['callback'], $email_address, $page );

				if ( ! is_array( $response ) ) {
					WP_CLI::warning( "Eraser '{$eraser_key}' returned an invalid response." );
					break;
				}

				if ( ! empty( $response['messages'] ) ) {
					$messages = array_merge( $messages, (array) $response['messages'] );
				}

				$done = ! empty( $response['done'] );
				++$page;
			} while ( ! $done );
		}

		wp_update_post(
			[
				'ID'          => $request_id,
				'post_status' => 'request-completed',
			]
		);
		update_post_meta( $request_id, '_wp_user_request_completed_timestamp', time() );

		foreach ( $messages as $message ) {
			if ( is_scalar( $message ) ) {
				WP_CLI::log( (string) $message );
			}
		}

		WP_CLI::success( "Erased personal data for request {$request_id}." );
	}

	/**
	 * Exports personal data for a given privacy request.
	 *
	 * Runs all registered data exporters for the email address associated with
	 * the request, generates a ZIP file containing the data, then marks the
	 * request as completed.
	 *
	 * ## OPTIONS
	 *
	 * <request-id>
	 * : The ID of the export_personal_data privacy request to process.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export personal data for request 1.
	 *     $ wp user privacy-request export 1
	 *     Success: Exported personal data to: /var/www/html/wp-content/uploads/wp-personal-data-exports/wp-personal-data-export-bob-example-com-1.zip
	 *
	 * @param array{string} $args Positional arguments.
	 * @param array{} $assoc_args Associative arguments.
	 */
	public function export( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		list( $request_id ) = $args;
		$request_id         = (int) $request_id;

		$request = $this->get_request_check( $request_id );

		if ( 'export_personal_data' !== $request->action_name ) {
			WP_CLI::error( "Request {$request_id} is not an 'export_personal_data' request." );
		}

		$email_address = $request->email;
		$exporters     = apply_filters( 'wp_privacy_personal_data_exporters', [] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$groups        = [];

		foreach ( $exporters as $exporter_key => $exporter ) {
			if ( ! isset( $exporter['callback'] ) || ! is_callable( $exporter['callback'] ) ) {
				WP_CLI::warning( "Exporter '{$exporter_key}' does not have a valid callback." );
				continue;
			}

			$page = 1;
			do {
				$response = call_user_func( $exporter['callback'], $email_address, $page );

				if ( ! is_array( $response ) ) {
					WP_CLI::warning( "Exporter '{$exporter_key}' returned an invalid response." );
					break;
				}

				if ( ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
					foreach ( $response['data'] as $export_datum ) {
						if ( ! is_array( $export_datum ) ) {
							continue;
						}
						if ( ! isset( $export_datum['group_id'], $export_datum['item_id'] ) ) {
							continue;
						}
						if ( ! is_scalar( $export_datum['group_id'] ) || ! is_scalar( $export_datum['item_id'] ) ) {
							continue;
						}
						$group_id = (string) $export_datum['group_id'];
						$item_id  = (string) $export_datum['item_id'];

						if ( ! isset( $groups[ $group_id ] ) ) {
							$groups[ $group_id ] = [
								'group_label'       => isset( $export_datum['group_label'] ) && is_scalar( $export_datum['group_label'] ) ? (string) $export_datum['group_label'] : '',
								'group_description' => isset( $export_datum['group_description'] ) && is_scalar( $export_datum['group_description'] ) ? (string) $export_datum['group_description'] : '',
								'items'             => [],
							];
						}
						if ( ! isset( $groups[ $group_id ]['items'][ $item_id ] ) ) {
							$groups[ $group_id ]['items'][ $item_id ] = [];
						}
						if ( isset( $export_datum['data'] ) && is_array( $export_datum['data'] ) ) {
							$groups[ $group_id ]['items'][ $item_id ] = array_merge( $groups[ $group_id ]['items'][ $item_id ], $export_datum['data'] );
						}
					}
				}

				$done = ! empty( $response['done'] );
				++$page;
			} while ( ! $done );
		}

		update_post_meta( $request_id, '_export_data_grouped', $groups );

		// Files were moved in WP 5.3.0, see https://core.trac.wordpress.org/ticket/43895.
		if ( file_exists( ABSPATH . 'wp-admin/includes/privacy-tools.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/privacy-tools.php';
		} else {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		wp_privacy_generate_personal_data_export_file( $request_id );

		$file_name = get_post_meta( $request_id, '_export_file_name', true );

		if ( ! is_string( $file_name ) || '' === $file_name ) {
			WP_CLI::error( 'Failed to generate the personal data export file.' );
		}

		$exports_dir = wp_privacy_exports_dir();
		$file_path   = $exports_dir . $file_name;

		wp_update_post(
			[
				'ID'          => $request_id,
				'post_status' => 'request-completed',
			]
		);
		update_post_meta( $request_id, '_wp_user_request_completed_timestamp', time() );

		WP_CLI::success( "Exported personal data to: {$file_path}" );
	}

	/**
	 * Marks one or more privacy requests as completed.
	 *
	 * ## OPTIONS
	 *
	 * <request-id>...
	 * : One or more IDs of the privacy requests to complete.
	 *
	 * ## EXAMPLES
	 *
	 *     # Mark request 1 as completed.
	 *     $ wp user privacy-request complete 1
	 *     Privacy request 1 completed.
	 *     Success: Completed 1 of 1 privacy requests.
	 *
	 *     # Mark multiple requests as completed.
	 *     $ wp user privacy-request complete 1 2
	 *     Privacy request 1 completed.
	 *     Privacy request 2 completed.
	 *     Success: Completed 2 of 2 privacy requests.
	 *
	 * @param list<string> $args Positional arguments.
	 * @param array{} $assoc_args Associative arguments.
	 */
	public function complete( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$successes = 0;
		$errors    = 0;

		foreach ( $args as $request_id ) {
			$request_id = (int) $request_id;
			$request    = $this->get_request( $request_id );

			if ( ! $request ) {
				WP_CLI::warning( "Could not find privacy request with ID {$request_id}." );
				++$errors;
				continue;
			}

			$result = wp_update_post(
				[
					'ID'          => $request_id,
					'post_status' => 'request-completed',
				],
				true
			);

			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( "Failed completing privacy request {$request_id}: " . $result->get_error_message() );
				++$errors;
			} else {
				update_post_meta( $request_id, '_wp_user_request_completed_timestamp', time() );
				WP_CLI::log( "Privacy request {$request_id} completed." );
				++$successes;
			}
		}

		$count = count( $args );
		Utils\report_batch_operation_results( 'privacy request', 'complete', $count, $successes, $errors );
	}

	/**
	 * Gets a user privacy request object.
	 *
	 * @param int $request_id Request ID.
	 * @return WP_User_Request|false The request if found; false otherwise.
	 */
	private function get_request( $request_id ) {
		if ( function_exists( 'wp_get_user_request' ) ) {
			return wp_get_user_request( $request_id );
		}

		return wp_get_user_request_data( $request_id ); // phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_get_user_request_dataFound -- Fallback for WP < 5.4. // @phpstan-ignore function.deprecated
	}

	/**
	 * Gets a user privacy request object or exits with an error.
	 *
	 * @param int $request_id Request ID.
	 * @return WP_User_Request The request.
	 */
	private function get_request_check( $request_id ) {
		$request = $this->get_request( $request_id );

		if ( ! $request ) {
			WP_CLI::error( "Could not find privacy request with ID {$request_id}." );
		}

		return $request;
	}

	/**
	 * Converts a WP_Post (user_request post type) to an associative array for display.
	 *
	 * @param WP_Post $post Post object of type user_request.
	 * @return array<string, mixed>|false Array of request data, or false on failure.
	 */
	private function get_request_data( $post ) {
		if ( function_exists( 'wp_get_user_request' ) ) {
			$request = wp_get_user_request( $post->ID );
		} else {
			$request = wp_get_user_request_data( $post->ID ); // phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_get_user_request_dataFound -- Fallback for WP < 5.4. // @phpstan-ignore function.deprecated
		}

		if ( ! $request ) {
			return false;
		}

		return [
			'ID'                  => $request->ID,
			'user_id'             => $request->user_id,
			'user_email'          => $request->email,
			'action_name'         => $request->action_name,
			'status'              => $request->status,
			'created_timestamp'   => $request->created_timestamp,
			'confirmed_timestamp' => $request->confirmed_timestamp,
			'completed_timestamp' => $request->completed_timestamp,
		];
	}
}
