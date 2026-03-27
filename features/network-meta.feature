Feature: Manage network-wide custom fields.

  Scenario: Non-multisite
    Given a WP install

    When I run `wp network-meta`
    Then STDOUT should contain:
      """
      usage: wp network meta
      """

    When I try `wp network-meta get 1 site_admins`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      This is not a multisite install
      """
    And the return code should be 1

  Scenario: Network meta is actually network options
    Given a WP multisite install

    When I run `wp eval 'update_network_option( 1, "mykey", "123" );'`
    And I run `wp eval 'echo get_network_option( 1, "mykey" );'`
    Then STDOUT should be:
      """
      123
      """

    When I run `wp network meta update 1 mykey 456`
    Then STDOUT should be:
      """
      Success: Updated custom field 'mykey'.
      """

    When I run `wp network meta get 1 mykey`
    Then STDOUT should be:
      """
      456
      """

    When I run `wp eval 'echo get_network_option( 1, "mykey" );'`
    Then STDOUT should be:
      """
      456
      """

  @require-object-cache
  Scenario: Object cache correctly handles network meta updates
    Given a WP multisite install

    When I run `wp eval 'update_network_option( 1, "objkey", "123" );'`

    And I run `wp network meta get 1 objkey`
    Then STDOUT should be:
      """
      123
      """

    When I run `wp eval 'update_network_option( 1, "objkey", "456" );'`

    And I run `wp network meta get 1 objkey`
    Then STDOUT should be:
      """
      456
      """

    When I run `wp network meta update 1 objkey 789`
    Then STDOUT should be:
      """
      Success: Updated custom field 'objkey'.
      """

    When I run `wp network meta get 1 objkey`
    Then STDOUT should be:
      """
      789
      """

    When I run `wp eval 'echo get_network_option( 1, "objkey" );'`
    Then STDOUT should be:
      """
      789
      """