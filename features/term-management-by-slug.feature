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

  Scenario: Fetch term by slug or ID
    When I run `wp term create category Apple --description="A type of fruit"`
    Then STDOUT should be:
    """
    Success: Created category 2.
    """

    When I run `wp term get category 2 --by=id --format=json --fields=term_id,name,slug,count`
    Then STDOUT should be:
    """
    {"term_id":2,"name":"Apple","slug":"apple","count":0}
    """

    When I run `wp term get category apple --by=slug --format=json --fields=term_id,name,slug,count`
    Then STDOUT should be:
    """
    {"term_id":2,"name":"Apple","slug":"apple","count":0}
    """

  Scenario: Update term by slug or ID
    When I run `wp term create category Apple --description="A type of fruit"`
    Then STDOUT should be:
    """
    Success: Created category 2.
    """

    When I run `wp term update category apple --by=slug --name=PineApple`
    Then STDOUT should be:
    """
    Success: Term updated.
    """

    When I run `wp term update category 2 --by=id --description="This is testing description"`
    Then STDOUT should be:
    """
    Success: Term updated.
    """