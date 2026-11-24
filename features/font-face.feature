Feature: Manage WordPress font faces

  Background:
    Given a WP install

  @require-wp-6.5
  Scenario: Installing a font face
    Given I run `wp post create --post_type=wp_font_family --post_title="Test Family" --post_status=publish --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font face install {FONT_FAMILY_ID} --src="https://example.com/font.woff2" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp post get {FONT_FACE_ID} --field=post_parent`
    Then STDOUT should be:
      """
      {FONT_FAMILY_ID}
      """

  @require-wp-6.5
  Scenario: Installing a font face with custom properties
    Given I run `wp post create --post_type=wp_font_family --post_title="Test Family" --post_status=publish --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font face install {FONT_FAMILY_ID} --src="font.woff2" --font-weight=700 --font-style=italic --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp post get {FONT_FACE_ID} --field=post_title`
    Then STDOUT should contain:
      """
      700
      """
    And STDOUT should contain:
      """
      italic
      """

  @require-wp-6.5
  Scenario: Installing a font face with invalid parent
    When I try `wp font face install 999999 --src="font.woff2"`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Installing a font face without required src parameter
    Given I run `wp post create --post_type=wp_font_family --post_title="Test Family" --post_status=publish --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I try `wp font face install {FONT_FAMILY_ID}`
    Then the return code should be 1
    And STDERR should contain:
      """
      missing --src parameter
      """

  @less-than-wp-6.5
  Scenario: Font face install commands fail on WordPress < 6.5
    When I try `wp font face install 1 --src=test.woff2`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 6.5 or greater
      """
