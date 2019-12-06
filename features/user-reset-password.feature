Feature: Reset passwords for one or more WordPress users.

  @require-wp-4.3
  Scenario: Reset the password of a WordPress user
    Given a WP installation

    When I run `wp user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `wp user reset-password 1`
    Then STDOUT should contain:
      """
      Reset password for admin.
      Success: Password reset for 1 user.
      """
    And an email should be sent

    When I run `wp user get 1 --field=user_pass`
    Then STDOUT should not contain:
      """
      {ORIGINAL_PASSWORD}
      """

  @require-wp-4.3
  Scenario: Reset the password of a WordPress user, but skip emailing them
    Given a WP installation

    When I run `wp user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `wp user reset-password 1 --skip-email`
    Then STDOUT should contain:
      """
      Reset password for admin.
      Success: Password reset for 1 user.
      """
    And an email should not be sent

    When I run `wp user get 1 --field=user_pass`
    Then STDOUT should not contain:
      """
      {ORIGINAL_PASSWORD}
      """

  @require-wp-4.3
  Scenario: Reset the password of a invalid WordPress user
    Given a WP installation

    When I try `wp user reset-password invalid-user-name`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: invalid user(s).
      """

  @require-wp-4.3
  Scenario: Reset the password of a invalid WordPress users
    Given a WP installation

    When I try `wp user reset-password invalid-user-name 999 invalid.user@example.com`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: invalid user(s).
      """
