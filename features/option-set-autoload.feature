Feature: Set 'autoload' value for an option

  Scenario: Option doesn't exist
    Given a WP install

    When I try `wp option set-autoload foo yes`
    Then STDERR should be:
      """
      Error: Could not get 'foo' option. Does it exist?
      """

  Scenario: Invalid 'autoload' value provided
    Given a WP install

    When I run `wp option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I try `wp option set-autoload foo invalid`
    Then STDERR should be:
      """
      Error: Invalid value specified for positional arg.
      """

  @less-than-wp-6.6
  Scenario: Successfully updates autoload value
    Given a WP install

    When I run `wp option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp option get-autoload foo`
    Then STDOUT should be:
      """
      yes
      """

    When I run `wp option set-autoload foo no`
    Then STDOUT should be:
      """
      Success: Updated autoload value for 'foo' option.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Autoload value passed for 'foo' option is unchanged.
      """

    When I run `wp option get-autoload foo`
    Then STDOUT should be:
      """
      no
      """

  @require-wp-6.6
  Scenario: Successfully updates autoload value
    Given a WP install

    When I run `wp option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp option get-autoload foo`
    Then STDOUT should be:
      """
      on
      """

    When I run `wp option set-autoload foo off`
    Then STDOUT should be:
      """
      Success: Updated autoload value for 'foo' option.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Autoload value passed for 'foo' option is unchanged.
      """

    When I run `wp option get-autoload foo`
    Then STDOUT should be:
      """
      off
      """

  @require-wp-6.6
  Scenario: Updates the cached alloptions in the same request
    Given a WP install
    And I run `wp option add foo bar --autoload=on`
    And I run `wp option add baz qux --autoload=off`

    When I run `wp eval 'wp_load_alloptions(); WP_CLI::runcommand( "option set-autoload foo off", array( "launch" => false ) ); var_dump( array_key_exists( "foo", wp_load_alloptions() ) );'`
    Then STDOUT should contain:
      """
      bool(false)
      """

    When I run `wp eval 'wp_load_alloptions(); get_option( "baz" ); WP_CLI::runcommand( "option set-autoload baz on", array( "launch" => false ) ); var_dump( array_key_exists( "baz", wp_load_alloptions() ) );'`
    Then STDOUT should contain:
      """
      bool(true)
      """
