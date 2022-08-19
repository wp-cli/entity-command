Feature: List WordPress users

  @require-wp-4.4
  Scenario: List users of specific roles
    Given a WP install
    And I run `wp user create bobjones bob@example.com --role=author`
    And I run `wp user create sally sally@example.com --role=editor`

    When I run `wp user list --field=user_login`
    Then STDOUT should be:
      """
      admin
      bobjones
      sally
      """

    When I run `wp user list --role__in=administrator,editor --field=user_login`
    Then STDOUT should be:
      """
      admin
      sally
      """

    When I run `wp user list --role__not_in=administrator,editor --field=user_login`
    Then STDOUT should be:
      """
      bobjones
      """

  Scenario: List users without roles
    Given a WP install
    When I run `wp user generate --count=1 --format=ids`
    Then save STDOUT as {USER_ID}

    And I run `wp user remove-role {USER_ID} subscriber`

    When I run `wp user list --role=none --field=id`
    Then STDOUT should be:
      """
      {USER_ID}
      """
