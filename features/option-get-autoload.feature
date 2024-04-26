Feature: Get 'autoload' value for an option

  Scenario: Option doesn't exist
    Given a WP install

    When I try `wp option get-autoload foo`
    Then STDERR should be:
      """
      Error: Could not get 'foo' option. Does it exist?
      """
  @less-than-wp-6.6
  Scenario: Displays 'autoload' value
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
  @require-wp-6.6
  Scenario: Displays 'autoload' value
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
