Feature: Manage WordPress font collections

  Background:
    Given a WP install

  @require-wp-6.5
  Scenario: Listing font collections
    When I try `wp font collection list`
    Then STDOUT should be a table containing rows:
      | slug         | name         | description                                                                |
      | google-fonts | Google Fonts | Install from Google Fonts. Fonts are copied to and served from your site.  |

  @require-wp-6.5
  Scenario: Getting a non-existent font collection
    When I try `wp font collection get nonexistent-collection`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Checking whether a font collection is registered
    When I try `wp font collection is-registered nonexistent-collection`
    Then the return code should be 1

    When I run `wp font collection is-registered google-fonts`
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
