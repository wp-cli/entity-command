Feature: Manage WordPress font families

  Background:
    Given a WP install

  @require-wp-6.5
  Scenario: Creating a font family
    When I run `wp font family create --post_title="Test Font" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font family get {FONT_FAMILY_ID} --field=name`
    Then STDOUT should be:
      """
      Test Font
      """

  @require-wp-6.5
  Scenario: Listing font families
    Given I run `wp font family create --post_title="Font One" --porcelain`
    And save STDOUT as {FONT1}
    And I run `wp font family create --post_title="Font Two" --porcelain`
    And save STDOUT as {FONT2}

    When I run `wp font family list --format=csv --fields=ID,name`
    Then STDOUT should contain:
      """
      Font One
      """
    And STDOUT should contain:
      """
      Font Two
      """

  @require-wp-6.5
  Scenario: Getting a font family
    Given I run `wp font family create --post_title="Test Font" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I try `wp font family get {FONT_FAMILY_ID}`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Test Font
      """

  @require-wp-6.5
  Scenario: Updating a font family
    Given I run `wp font family create --post_title="Old Name" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font family update {FONT_FAMILY_ID} --post_title="New Name"`
    Then STDOUT should contain:
      """
      Success: Updated font family
      """

    When I run `wp font family get {FONT_FAMILY_ID} --field=name`
    Then STDOUT should be:
      """
      New Name
      """

  @require-wp-6.5
  Scenario: Deleting a font family
    Given I run `wp font family create --post_title="Delete Me" --porcelain`
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font family delete {FONT_FAMILY_ID}`
    Then STDOUT should contain:
      """
      Success: Deleted font family
      """

    When I try `wp font family get {FONT_FAMILY_ID}`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Getting a non-existent font family
    When I try `wp font family get 999999`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-6.5
  Scenario: Installing a font family from a collection
    When I run `wp font family install google-fonts "roboto" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FONT_FAMILY_ID}

    When I run `wp font family get {FONT_FAMILY_ID} --field=post_title`
    Then STDOUT should contain:
      """
      Roboto
      """

    When I run `wp font face list --post_parent={FONT_FAMILY_ID} --format=count`
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
  Scenario: Font family commands fail on WordPress < 6.5
    When I try `wp font family list`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 6.5 or greater
      """
