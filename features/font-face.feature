Feature: Manage WordPress font faces

  Background:
    Given a WP install

  @require-wp-6.5
  Scenario: Creating a font face
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font face create --post_parent={FONT_FAMILY_ID} --post_title="Regular" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp font face get {FONT_FACE_ID} --field=name`
    Then STDOUT should be:
      """
      Regular
      """

  @require-wp-6.5
  Scenario: Listing font faces
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}
    And I run `wp font face create --post_parent={FONT_FAMILY_ID} --post_title="Regular" --porcelain`
    And save STDOUT as {FACE1}
    And I run `wp font face create --post_parent={FONT_FAMILY_ID} --post_title="Bold" --porcelain`
    And save STDOUT as {FACE2}

    When I run `wp font face list --format=csv --fields=ID,name,parent`
    Then STDOUT should contain:
      """
      Regular
      """
    And STDOUT should contain:
      """
      Bold
      """

  @require-wp-6.5
  Scenario: Listing font faces by family
    Given I run `wp font family create --post_title="Family One" --porcelain`
    And save STDOUT as {FAMILY1}
    And I run `wp font family create --post_title="Family Two" --porcelain`
    And save STDOUT as {FAMILY2}
    And I run `wp font face create --post_parent={FAMILY1} --post_title="F1 Regular" --porcelain`
    And I run `wp font face create --post_parent={FAMILY2} --post_title="F2 Regular" --porcelain`

    When I run `wp font face list --post_parent={FAMILY1} --format=csv --fields=name`
    Then STDOUT should contain:
      """
      F1 Regular
      """
    And STDOUT should not contain:
      """
      F2 Regular
      """

  @require-wp-6.5
  Scenario: Getting a font face
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}
    And I run `wp font face create --post_parent={FONT_FAMILY_ID} --post_title="Bold" --porcelain`
    And save STDOUT as {FONT_FACE_ID}

    When I try `wp font face get {FONT_FACE_ID}`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Bold
      """

  @require-wp-6.5
  Scenario: Updating a font face
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}
    And I run `wp font face create --post_parent={FONT_FAMILY_ID} --post_title="Old Name" --porcelain`
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp font face update {FONT_FACE_ID} --post_title="New Name"`
    Then STDOUT should contain:
      """
      Success: Updated font face
      """

    When I run `wp font face get {FONT_FACE_ID} --field=name`
    Then STDOUT should be:
      """
      New Name
      """

  @require-wp-6.5
  Scenario: Deleting a font face
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}
    And I run `wp font face create --post_parent={FONT_FAMILY_ID} --post_title="Delete Me" --porcelain`
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp font face delete {FONT_FACE_ID}`
    Then STDOUT should contain:
      """
      Success: Deleted font face
      """

    When I try `wp font face get {FONT_FACE_ID}`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Creating a font face requires parent
    When I try `wp font face create --post_title="Regular"`
    Then the return code should be 1
    And STDERR should contain:
      """
      missing --post_parent parameter
      """

  @require-wp-6.5
  Scenario: Creating a font face with invalid parent
    When I try `wp font face create --post_parent=999999 --post_title="Regular"`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Installing a font face
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font face install {FONT_FAMILY_ID} --src="https://example.com/font.woff2" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp font face get {FONT_FACE_ID} --field=parent`
    Then STDOUT should be:
      """
      {FONT_FAMILY_ID}
      """

  @require-wp-6.5
  Scenario: Installing a font face with custom properties
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font face install {FONT_FAMILY_ID} --src="font.woff2" --font-weight=700 --font-style=italic --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FACE_ID}

    When I run `wp font face get {FONT_FACE_ID} --field=name`
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
    Given I run `wp font family create --post_title="Test Family" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I try `wp font face install {FONT_FAMILY_ID}`
    Then the return code should be 1
    And STDERR should contain:
      """
      missing --src parameter
      """

  @less-than-wp-6.5
  Scenario: Font face commands fail on WordPress < 6.5
    When I try `wp font face list`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 6.5 or greater
      """
