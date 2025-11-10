Feature: Manage WordPress abilities

  Background:
    Given a WP install

  @less-than-wp-6.9
  Scenario: Ability commands require WordPress 6.9+
    When I try `wp ability list`
    Then STDERR should contain:
      """
      Error: Requires WordPress 6.9 or greater.
      """
    And the return code should be 1

  @require-wp-6.9
  Scenario: List abilities
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
          return;
        }
        
        wp_register_ability( 'test_ability_1', array(
          'category' => 'content',
          'description' => 'Test ability one',
          'callback' => function( $input ) {
            return array( 'result' => 'success', 'input' => $input );
          },
          'input_schema' => array(
            'type' => 'object',
            'properties' => array(
              'id' => array( 'type' => 'integer' ),
            ),
          ),
          'output_schema' => array(
            'type' => 'object',
          ),
        ) );
        
        wp_register_ability( 'test_ability_2', array(
          'category' => 'users',
          'description' => 'Test ability two',
          'callback' => function( $input ) {
            return array( 'result' => 'done' );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I run `wp ability list --format=count`
    Then STDOUT should contain:
      """
      2
      """

    When I run `wp ability list --fields=name,category,description --format=csv`
    Then STDOUT should contain:
      """
      test_ability_1,content,"Test ability one"
      """
    And STDOUT should contain:
      """
      test_ability_2,users,"Test ability two"
      """

    When I run `wp ability list --category=content --format=count`
    Then STDOUT should contain:
      """
      1
      """

  @require-wp-6.9
  Scenario: Get a specific ability
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
          return;
        }
        
        wp_register_ability( 'get_test_post', array(
          'category' => 'content',
          'description' => 'Gets a test post',
          'callback' => function( $input ) {
            return array( 'id' => $input['id'], 'title' => 'Test Post' );
          },
          'input_schema' => array(
            'type' => 'object',
            'properties' => array(
              'id' => array( 'type' => 'integer' ),
            ),
          ),
          'output_schema' => array(
            'type' => 'object',
          ),
        ) );
      } );
      """

    When I try `wp ability get invalid_ability`
    Then STDERR should contain:
      """
      Error: Ability invalid_ability doesn't exist.
      """
    And the return code should be 1

    When I run `wp ability get get_test_post --field=category`
    Then STDOUT should be:
      """
      content
      """

    When I run `wp ability get get_test_post --field=description`
    Then STDOUT should be:
      """
      Gets a test post
      """

  @require-wp-6.9
  Scenario: Check if an ability exists
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
          return;
        }
        
        wp_register_ability( 'test_exists', array(
          'category' => 'content',
          'description' => 'Test exists',
          'callback' => function( $input ) {
            return array( 'result' => 'ok' );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I try `wp ability exists test_exists`
    Then the return code should be 0

    When I try `wp ability exists non_existent_ability`
    Then the return code should be 1

  @require-wp-6.9
  Scenario: Execute an ability with JSON input
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
          return;
        }
        
        wp_register_ability( 'echo_input', array(
          'category' => 'testing',
          'description' => 'Echoes input',
          'callback' => function( $input ) {
            return array( 'echoed' => $input );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I try `wp ability execute non_existent_ability '{"test": "data"}'`
    Then STDERR should contain:
      """
      Error: Ability non_existent_ability doesn't exist.
      """
    And the return code should be 1

    When I run `wp ability execute echo_input '{"message": "hello"}'`
    Then STDOUT should contain:
      """
      "echoed"
      """
    And STDOUT should contain:
      """
      "message"
      """
    And STDOUT should contain:
      """
      "hello"
      """
    And STDOUT should contain:
      """
      Success: Ability executed successfully.
      """

  @require-wp-6.9
  Scenario: Execute an ability with input from STDIN
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability' ) ) {
          return;
        }
        
        wp_register_ability( 'process_input', array(
          'category' => 'testing',
          'description' => 'Processes input',
          'callback' => function( $input ) {
            return array( 'processed' => true, 'data' => $input );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I run `echo '{"value": 42}' | wp ability execute process_input`
    Then STDOUT should contain:
      """
      "processed": true
      """
    And STDOUT should contain:
      """
      "value": 42
      """
    And STDOUT should contain:
      """
      Success: Ability executed successfully.
      """
