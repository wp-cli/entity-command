Feature: Manage WordPress font collections

  Background:
    Given a WP install

  @require-wp-6.5
  Scenario: Listing font collections
    When I try `wp font collection list`
    Then the return code should be 0

  @require-wp-6.5
  Scenario: Getting a font collection
    When I try `wp font collection get google-fonts`
    Then the return code should be 0
    Or STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Font collection commands require WordPress 6.5+
    Given a WP install
    When I try `wp font collection list`
    Then the return code should be 0

  @less-than-wp-6.5
  Scenario: Font collection commands fail on WordPress < 6.5
    Given a WP install
    When I try `wp font collection list`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 6.5 or greater
      """
