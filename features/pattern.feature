Feature: Manage WordPress block patterns

  Background:
    Given a WP install

  @require-wp-5.5
  Scenario: Listing block patterns
    When I run `wp pattern list --format=csv`
    Then STDOUT should contain:
      """
      name,title
      """

  @require-wp-5.5
  Scenario: Getting a specific block pattern
    Given I run `wp pattern list --format=ids`
    And save STDOUT as {PATTERN_NAME}

    When I run `wp pattern get {PATTERN_NAME}`
    Then STDOUT should contain:
      """
      name
      """
    And STDOUT should contain:
      """
      title
      """

  @require-wp-5.5
  Scenario: Getting a non-existent block pattern
    When I try `wp pattern get nonexistent/pattern`
    Then STDERR should contain:
      """
      Error: Block pattern 'nonexistent/pattern' is not registered.
      """
    And the return code should be 1

  @require-wp-5.5
  Scenario: Listing block patterns in JSON format
    When I run `wp pattern list --format=json`
    Then STDOUT should be valid JSON

  @require-wp-5.5
  Scenario: Count block patterns
    When I run `wp pattern list --format=count`
    Then STDOUT should match /^\d+$/

  @require-wp-4.9
  Scenario: Pattern commands require WordPress 5.5+
    When I try `wp pattern list`
    Then STDERR should contain:
      """
      Error: The pattern commands require WordPress 5.5 or greater.
      """
    And the return code should be 1
