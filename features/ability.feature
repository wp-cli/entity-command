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
    When I run `wp ability list --format=count`
      Then save STDOUT as {ABILITIES_COUNT}

    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'wp_abilities_api_init', function() {
        wp_register_ability( 'my-plugin/test-ability-1', array(
          'label' => 'Test Ability 1',
          'category' => 'site',
          'description' => 'Test ability one',
          'permission_callback' => '__return_true',
          'execute_callback' => function( $input ) {
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

        wp_register_ability( 'my-plugin/test-ability-2', array(
        'label' => 'Test Ability 2',
          'category' => 'user',
          'description' => 'Test ability two',
          'permission_callback' => '__return_true',
          'execute_callback' => function( $input ) {
            return array( 'result' => 'done' );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I run `wp ability list --format=count`
    Then STDOUT should not contain:
      """
      {ABILITIES_COUNT}
      """

    When I run `wp ability list --fields=name,category,description --format=csv`
    Then STDOUT should contain:
      """
      my-plugin/test-ability-1,site,"Test ability one"
      """
    And STDOUT should contain:
      """
      my-plugin/test-ability-2,user,"Test ability two"
      """

  @require-wp-6.9
  Scenario: Get a specific ability
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'wp_abilities_api_init', function() {
        wp_register_ability( 'my-plugin/get-test-post', array(
          'label' => 'Get Test Post',
          'category' => 'site',
          'description' => 'Gets a test post',
          'permission_callback' => '__return_true',
          'execute_callback' => function( $input ) {
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

    When I run `wp ability get my-plugin/get-test-post --field=category`
    Then STDOUT should be:
      """
      site
      """

    When I run `wp ability get my-plugin/get-test-post --field=description`
    Then STDOUT should be:
      """
      Gets a test post
      """

  @require-wp-6.9
  Scenario: Check if an ability exists
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'wp_abilities_api_init', function() {
        wp_register_ability( 'my-plugin/test-exists', array(
          'label' => 'Test Exists',
          'category' => 'site',
          'description' => 'Test exists',
          'permission_callback' => '__return_true',
          'execute_callback' => function( $input ) {
            return array( 'result' => 'ok' );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I try `wp ability exists my-plugin/test-exists`
    Then the return code should be 0

    When I try `wp ability exists non-existent-ability`
    Then the return code should be 1

  @require-wp-6.9
  Scenario: Execute an ability with JSON input
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'wp_abilities_api_init', function() {
        wp_register_ability( 'my-plugin/echo-input', array(
          'label' => 'Echo Input',
          'category' => 'site',
          'description' => 'Echoes input',
          'permission_callback' => '__return_true',
          'execute_callback' => function( $input ) {
            return array( 'echoed' => $input );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I try `wp ability execute non-existent-ability '{"test": "data"}'`
    Then STDERR should contain:
      """
      Error: Ability non-existent-ability doesn't exist.
      """
    And the return code should be 1

    When I run `wp ability execute my-plugin/echo-input '{"message": "hello"}'`
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

  @require-wp-6.9
  Scenario: Execute an ability with input from STDIN
    Given a wp-content/mu-plugins/test-abilities.php file:
      """
      <?php
      add_action( 'wp_abilities_api_init', function() {
        wp_register_ability( 'my-plugin/process-input', array(
          'label' => 'Process Input',
          'category' => 'site',
          'description' => 'Processes input',
          'permission_callback' => '__return_true',
          'execute_callback' => function( $input ) {
            return array( 'processed' => true, 'data' => $input );
          },
          'input_schema' => array( 'type' => 'object' ),
          'output_schema' => array( 'type' => 'object' ),
        ) );
      } );
      """

    When I run `echo '{"value": 42}' | wp ability execute my-plugin/process-input`
    Then STDOUT should contain:
      """
      "processed": true
      """
    And STDOUT should contain:
      """
      "value": 42
      """
