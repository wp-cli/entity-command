Feature: Manage WordPress block types

  Background:
    Given a WP install

  @require-wp-5.0
  Scenario: Listing block types
    When I run `wp block list --format=csv`
    Then STDOUT should contain:
      """
      name,title
      """
    And STDOUT should contain:
      """
      core/paragraph
      """

  @require-wp-5.0
  Scenario: Listing block types with specific fields
    When I run `wp block list --fields=name,title,category`
    Then STDOUT should be a table containing rows:
      | name            | title     | category |
      | core/paragraph  | Paragraph | text     |

  @require-wp-5.0
  Scenario: Getting a specific block type
    When I run `wp block get core/paragraph --fields=name,title,category`
    Then STDOUT should be a table containing rows:
      | Field    | Value     |
      | name     | core/paragraph |
      | title    | Paragraph |
      | category | text      |

  @require-wp-5.0
  Scenario: Getting a non-existent block type
    When I try `wp block get core/nonexistent-block`
    Then STDERR should contain:
      """
      Error: Block type 'core/nonexistent-block' is not registered.
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Getting a specific field from a block type
    When I run `wp block get core/paragraph --field=title`
    Then STDOUT should be:
      """
      Paragraph
      """

  @require-wp-5.0
  Scenario: Listing block types in JSON format
    When I run `wp block list --format=json`
    Then STDOUT should contain:
      """
      {"name":"core\/paragraph","title":"Paragraph","description":"Start with the basic building block of all narrative.","category":"text"}
      """

  @require-wp-5.0
  Scenario: Count block types
    When I run `wp block list --format=count`
    Then STDOUT should match /^\d+$/

  @less-than-wp-5.0
  Scenario: Block commands require WordPress 5.0+
    When I try `wp block list`
    Then STDERR should contain:
      """
      Error: Requires WordPress 5.0 or greater.
      """
    And the return code should be 1
