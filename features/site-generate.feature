Feature: Generate new WordPress sites

  Scenario: Generate on single site
    Given a WP install
    When I try `wp site generate`
    Then STDERR should contain:
    """
    This is not a multisite installation.
    """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Generate a specific number of sites
    Given a WP multisite install
    When I run `wp site generate --count=10`
    And I run `wp site list --format=count`
    Then STDOUT should be:
      """
      11
      """

  Scenario: Generate sites assigned to a specific network
    Given a WP multisite install
    When I try `wp site generate --count=4 --network_id=2`
    Then STDERR should contain:
      """
      Network with id 2 does not exist.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Generate sites and output ids
    Given a WP multisite install
    When I run `wp site generate --count=3 --format=ids`
    When I run `wp site list --format=ids`
    Then STDOUT should be:
      """
      1 2 3 4
      """
    And STDERR should be empty
    And the return code should be 0

  Scenario: Generate subdomain sites
    Given a WP multisite subdomain install

    When I run `wp site generate --count=1`
    Then STDOUT should be empty

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | https://example.com/       |
      | 2       | http://site1.example.com/ |
    When I run `wp site list --format=ids`
    Then STDOUT should be:
      """
      1 2
      """

  Scenario: Generate subdirectory sites
    Given a WP multisite subdirectory install
    When I run `wp site generate --count=1`
    Then STDOUT should be empty
    And I run `wp site list --site__in=2 --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SCHEME}

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | https://example.com/       |
      | 2       | {SCHEME}://example.com/site1/ |
    When I run `wp site list --format=ids`
    Then STDOUT should be:
      """
      1 2
      """

  Scenario: Generate sites with a slug
	  Given a WP multisite subdirectory install
    When I run `wp site generate --count=2 --slug=subsite`
    Then STDOUT should be empty
    And I run `wp site list --site__in=2 --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SCHEME1}
    And I run `wp site list --site__in=3 --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SCHEME2}

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | https://example.com/        |
      | 2       | {SCHEME1}://example.com/subsite1/   |
      | 3       | {SCHEME2}://example.com/subsite2/   |
    When I run `wp site list --format=ids`
    Then STDOUT should be:
      """
      1 2 3
      """

  Scenario: Generate sites with reserved slug
    Given a WP multisite subdirectory install
    When I try `wp site generate --count=2 --slug=page`
    Then STDERR should contain:
      """
      The following words are reserved and cannot be used as blog names: page, comments, blog, files, feed
      """
    And STDOUT should be empty
    And the return code should be 1
