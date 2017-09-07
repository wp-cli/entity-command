<?php

/**
 * Manage a user's sessions.
 *
 * ## EXAMPLES
 *
 *     # List a user's sessions.
 *     $ wp user session list admin@example.com --format=csv
 *     login_time,expiration_time,ip,ua
 *     "2016-01-01 12:34:56","2016-02-01 12:34:56",127.0.0.1,"Mozilla/5.0..."
 *
 *     # Destroy the most recent session of the given user.
 *     $ wp user session destroy admin
 *     Success: Destroyed session. 3 sessions remaining.
 *
 * @package wp-cli
 */
class User_Session_Command extends WP_CLI_Command {

	private $fields = array(
		'verifier',
		'login_time',
		'expiration_time',
		'ip',
		'ua',
	);

	public function __construct() {
		$this->fetcher = new \WP_CLI\Fetchers\User;
	}

	/**
	 * Destroy a session for the given user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * [<verifier>]
	 * : The verifier of the session to destroy. Defaults to the most recently created session.
	 *
	 * [--all]
	 * : Destroy all of the user's sessions.
	 *
	 * ## EXAMPLES
	 *
	 *     # Destroy the most recent session of the given user.
	 *     $ wp user session destroy admin
	 *     Success: Destroyed session. 3 sessions remaining.
	 *
	 *     # Destroy a specific session of the given user.
	 *     $ wp user session destroy admin e073ad8540a9c2...
	 *     Success: Destroyed session. 2 sessions remaining.
	 *
	 *     # Destroy all the sessions of the given user.
	 *     $ wp user session destroy admin --all
	 *     Success: Destroyed all sessions.
	 *
	 *     # Destroy all sessions for all users.
	 *     $ wp user list --field=ID | xargs -n 1 wp user session destroy --all
	 *     Success: Destroyed all sessions.
	 *     Success: Destroyed all sessions.
	 */
	public function destroy( $args, $assoc_args ) {
		$user    = $this->fetcher->get_check( $args[0] );
		$verifier = \WP_CLI\Utils\get_flag_value( $args, 1, null );
		$all     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );
		$manager = WP_Session_Tokens::get_instance( $user->ID );

		if ( $verifier && $all ) {
			WP_CLI::error( 'The --all flag cannot be specified along with a session verifier.' );
		}

		if ( $all ) {
			$manager->destroy_all();
			WP_CLI::success( 'Destroyed all sessions.' );
			return;
		}

		$sessions = $this->get_all_sessions( $manager );

		if ( ! $verifier ) {
			if ( empty( $sessions ) ) {
				WP_CLI::success( 'No sessions to destroy.' );
			}
			$last = end( $sessions );
			$verifier = $last['verifier'];
		}

		if ( ! isset( $sessions[ $verifier ] ) ) {
			WP_CLI::error( 'Session not found.' );
		}

		$this->destroy_session( $manager, $verifier );
		$remaining = count( $manager->get_all() );

		WP_CLI::success( sprintf( 'Destroyed session. %s remaining.', $remaining ) );
	}

	/**
	 * List sessions for the given user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 *   - count
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each session:
	 *
	 * * token
	 * * login_time
	 * * expiration_time
	 * * ip
	 * * ua
	 *
	 * These fields are optionally available:
	 *
	 * * expiration
	 * * login
	 *
	 * ## EXAMPLES
	 *
	 *     # List a user's sessions.
	 *     $ wp user session list admin@example.com --format=csv
	 *     login_time,expiration_time,ip,ua
	 *     "2016-01-01 12:34:56","2016-02-01 12:34:56",127.0.0.1,"Mozilla/5.0..."
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$user      = $this->fetcher->get_check( $args[0] );
		$formatter = $this->get_formatter( $assoc_args );
		$manager   = WP_Session_Tokens::get_instance( $user->ID );
		$sessions  = $this->get_all_sessions( $manager );

		if ( 'ids' == $formatter->format ) {
			echo implode( ' ', array_keys( $sessions ) );
		} else {
			$formatter->display_items( $sessions );
		}
	}

	protected function get_all_sessions( WP_Session_Tokens $manager ) {
		// Make the private session data accessible to WP-CLI
		$get_sessions = new ReflectionMethod( $manager, 'get_sessions' );
		$get_sessions->setAccessible( true );
		$sessions = $get_sessions->invoke( $manager );

		array_walk( $sessions, function( & $session, $verifier ) {
			$session['verifier']        = $verifier;
			$session['login_time']      = date( 'Y-m-d H:i:s', $session['login'] );
			$session['expiration_time'] = date( 'Y-m-d H:i:s', $session['expiration'] );
		} );

		return $sessions;
	}

	protected function destroy_session( WP_Session_Tokens $manager, $verifier ) {
		$update_session = new ReflectionMethod( $manager, 'update_session' );
		$update_session->setAccessible( true );
		return $update_session->invoke( $manager, $verifier, null );
	}

	private function get_formatter( &$assoc_args ) {
		return new \WP_CLI\Formatter( $assoc_args, $this->fields );
	}

}
