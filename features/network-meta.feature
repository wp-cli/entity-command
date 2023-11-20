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

  Scenario: Suggest 'meta' when 'option' subcommand is run
    Given a WP install

    When I try `wp network option`
    Then STDERR should contain:
      """
      Error: 'option' is not a registered subcommand of 'network'. See 'wp help network' for available subcommands.
      Did you mean 'meta'?
      """
    And the return code should be 1
