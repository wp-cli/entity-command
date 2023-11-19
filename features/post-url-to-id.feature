Feature: Get the post ID for a given URL

  Background:
    Given a WP install

  Scenario: Get the post ID for a given URL
    When I run `wp post get 1 --field=url`
    Then STDOUT should be:
      """
      https://example.com/?p=1
      """
    And save STDOUT as {POST_URL}

    When I run `wp post url-to-id {POST_URL}`
    Then STDOUT should contain:
      """
      1
      """

    When I try `wp post url-to-id 'https://example.com/?p=404'`
    Then STDERR should contain:
      """
      Could not get post with url https://example.com/?p=404.
      """