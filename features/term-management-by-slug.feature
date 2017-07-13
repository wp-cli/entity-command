Feature: Manage WordPress terms by slug or ID

  Background:
    Given a WP install

  Scenario: Deleting a term by slog or ID
    When I run `wp term create category Apple --description="A type of fruit"`
    Then STDOUT should be:
    """
    Success: Created category 2.
    """

    When I run `wp term create category Orange --description="A type of fruit"`
    Then STDOUT should be:
    """
    Success: Created category 3.
    """

    When I run `wp term create category Mango --description="A type of fruit"`
    Then STDOUT should be:
    """
    Success: Created category 4.
    """

    When I run `wp term get category 2 --field=slug --format=json`
    Then STDOUT should be:
      """
      "apple"
      """

    When I run `wp term delete category apple --by=slug`
    Then STDOUT should be:
      """
      Deleted category 2.
      Success: Deleted 1 of 1 terms.
      """

    When I run `wp term delete category 3 --by=id`
    Then STDOUT should be:
      """
      Deleted category 3.
      Success: Deleted 1 of 1 terms.
      """

    When I run `wp term delete category 4`
    Then STDOUT should be:
      """
      Deleted category 4.
      Success: Deleted 1 of 1 terms.
      """