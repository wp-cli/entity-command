Feature: Manage WordPress font families

  Background:
    Given a WP install

  @require-wp-6.5
  Scenario: Installing a font family from a collection
    When I run `wp font family install google-fonts "roboto" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp post get {FONT_FAMILY_ID} --field=post_title`
    Then STDOUT should contain:
      """
      Roboto
      """

    When I run `wp post list --post_type=wp_font_face --post_parent={FONT_FAMILY_ID} --format=count`
    Then STDOUT should be a number

  @require-wp-6.5
  Scenario: Installing a font family from a non-existent collection
    When I try `wp font family install nonexistent-collection roboto`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Installing a non-existent font family from a collection
    When I try `wp font family install google-fonts nonexistent-family`
    Then the return code should be 1
    And STDERR should contain:
      """
      not found
      """

  @less-than-wp-6.5
  Scenario: Font family install commands fail on WordPress < 6.5
    When I try `wp font family install google-fonts roboto`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 6.5 or greater
      """
