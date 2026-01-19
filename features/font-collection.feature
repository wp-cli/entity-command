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

  @require-wp-6.5
  Scenario: Listing font families in a collection
    When I run `wp font collection list-families google-fonts --format=count`
    Then STDOUT should be a number

  @require-wp-6.5
  Scenario: Listing font families in a collection with fields
    When I run `wp font collection list-families google-fonts --fields=slug,name --format=csv`
    Then STDOUT should contain:
      """
      slug,name
      """

  @require-wp-6.5
  Scenario: Filtering font families by category
    When I run `wp font collection list-families google-fonts --category=sans-serif --format=count`
    Then STDOUT should be a number

  @require-wp-6.5
  Scenario: Listing categories in a collection
    When I run `wp font collection list-categories google-fonts --format=csv`
    Then STDOUT should contain:
      """
      slug,name
      """

  @require-wp-6.5
  Scenario: Getting a non-existent collection for list-families
    When I try `wp font collection list-families nonexistent-collection`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Getting a non-existent collection for list-categories
    When I try `wp font collection list-categories nonexistent-collection`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @less-than-wp-6.5
  Scenario: Font collection commands fail on WordPress < 6.5
    Given a WP install
    When I try `wp font collection list`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 6.5 or greater
      """
