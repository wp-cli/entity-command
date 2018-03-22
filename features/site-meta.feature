Feature: Manage site-wide custom fields.

  @require-wp-5.0
  Scenario: Non-multisite
    Given a WP install

    When I run `wp site-meta`
    Then STDOUT should contain:
      """
      usage: wp site meta
      """

    When I try `wp site-meta get 1 test`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      This is not a multisite install.
      """
    And the return code should be 1
