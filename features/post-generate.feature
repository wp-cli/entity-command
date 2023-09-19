Feature: Generate new WordPress posts

  Background:
    Given a WP install

  Scenario: Generating posts
    When I run `echo "Content generated by wp post generate" | wp post generate --count=1 --post_content`
    And I run `wp post list --field=post_content`
    Then STDOUT should contain:
      """
      Content generated by wp post generate
      """
    And STDERR should be empty

  @broken
  Scenario: Using --post-content requires STDIN input
    When I try `wp post generate --count=1 --post_content`
    Then STDERR should contain:
      """
      Error: The parameter `post_content` reads from STDIN.
      """

  Scenario: Generating posts by a specific author

    When I run `wp user create dummyuser dummy@example.com --porcelain`
    Then save STDOUT as {AUTHOR_ID}

    When I run `wp post generate --post_author={AUTHOR_ID} --post_type=post --count=16`
    And I run `wp post list --post_type=post --author={AUTHOR_ID} --format=count`
    Then STDOUT should contain:
      """
      16
      """

  Scenario: Generating pages
    When I run `wp post generate --post_type=page --max_depth=10`
    And I run `wp post list --post_type=page --field=post_parent`
    Then STDOUT should contain:
      """
      1
      """

  Scenario: Generating posts and outputting ids
    When I run `wp post generate --count=1 --format=ids`
    Then save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_title="foo"`
    Then STDOUT should contain:
      """
      Success:
      """

  Scenario: Generating post and outputting title and name
    When I run `wp post generate --count=3 --post_title=Howdy!`
    And I run `wp post list --field=post_title --posts_per_page=4 --orderby=ID --order=asc`
    Then STDOUT should contain:
      """
      Hello world!
      Howdy!
      Howdy! 2
      Howdy! 3
      """
    And STDERR should be empty
    And I run `wp post list --field=post_name --posts_per_page=4 --orderby=ID --order=asc`
    Then STDOUT should contain:
      """
      hello-world
      howdy
      howdy-2
      howdy-3
      """
    And STDERR should be empty

  Scenario: Generating posts with post_date argument without time
    When I run `wp post generate --count=1 --post_date="2018-07-01"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      2018-07-01 00:00:00
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2018-07-01 00:00:00
      """

  Scenario: Generating posts with post_date argument with time
    When I run `wp post generate --count=1 --post_date="2018-07-02 02:21:05"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      2018-07-02 02:21:05
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2018-07-02 02:21:05
      """

  Scenario: Generating posts with post_date_gmt argument without time
    When I run `wp post generate --count=1 --post_date_gmt="2018-07-03"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      2018-07-03 00:00:00
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2018-07-03 00:00:00
      """

  Scenario: Generating posts with post_date_gmt argument with time
    When I run `wp post generate --count=1 --post_date_gmt="2018-07-04 12:34:56"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      2018-07-04 12:34:56
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2018-07-04 12:34:56
      """

  Scenario: Generating posts with post_date argument with hyphenated time
    When I run `wp post generate --count=1 --post_date="2018-07-05-17:17:17"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      2018-07-05 17:17:17
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2018-07-05 17:17:17
      """

  Scenario: Generating posts with post_date_gmt argument with hyphenated time
    When I run `wp post generate --count=1 --post_date_gmt="2018-07-06-12:12:12"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      2018-07-06 12:12:12
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2018-07-06 12:12:12
      """

  Scenario: Generating posts with different post_date & post_date_gmt argument without time
    When I run `wp post generate --count=1 --post_date="1999-12-31" --post_date_gmt="2000-01-01"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      1999-12-31 00:00:00
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2000-01-01 00:00:00
      """

	Scenario: Generating posts with different post_date & post_date_gmt argument with time
    When I run `wp post generate --count=1 --post_date="1999-12-31 11:11:00" --post_date_gmt="2000-01-01 02:11:00"`
    And I run `wp post list --field=post_date`
    Then STDOUT should contain:
      """
      1999-12-31 11:11:00
      """
    And I run `wp post list --field=post_date_gmt`
    Then STDOUT should contain:
      """
      2000-01-01 02:11:00
      """

  Scenario: Generating posts when the site timezone is ahead of UTC
    When I run `wp option update timezone_string "Europe/Helsinki"`
    And I run `wp post delete 1 --force`

    When I run `wp post list --field=post_status`
    Then STDOUT should be empty
    
    When I run `wp post generate --count=1`
    And I run `wp post list --field=post_status`
    Then STDOUT should be:
      """
      publish
      """