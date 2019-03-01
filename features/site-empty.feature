Feature: Empty a WordPress site of its data

  Scenario: Empty a site
    Given a WP installation
    And I run `wp option update uploads_use_yearmonth_folders 0`
    And download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |

    When I run `wp media import {CACHE_DIR}/large-image.jpg --post_id=1`
    Then the wp-content/uploads/large-image.jpg file should exist

    When I try `wp site url 1`
    Then STDERR should be:
      """
      Error: This is not a multisite installation.
      """
    And the return code should be 1

    When I run `wp post create --post_title='Test post' --post_content='Test content.'`
    Then STDOUT should contain:
      """
      Success: Created post
      """

    When I run `wp term create post_tag 'Test term' --slug=test --description='This is a test term'`
    Then STDOUT should be:
      """
      Success: Created post_tag 2.
      """

    When I run `wp post create --post_type=page --post_title='Sample Privacy Page' --post_content='Sample Privacy Terms' --porcelain`
    Then save STDOUT as {PAGE_ID}

    When I run `wp option set wp_page_for_privacy_policy {PAGE_ID}`
    Then STDOUT should be:
      """
      Success: Updated 'wp_page_for_privacy_policy' option.
      """

    When I run `wp option get wp_page_for_privacy_policy`
    Then STDOUT should be:
      """
      {PAGE_ID}
      """

    When I run `wp site empty --yes`
    Then STDOUT should be:
      """
      Success: The site at 'http://example.com' was emptied.
      """
    And the wp-content/uploads/large-image.jpg file should exist

    When I run `wp post list --format=ids`
    Then STDOUT should be empty

    When I run `wp term list post_tag --format=ids`
    Then STDOUT should be empty

    When I run `wp option get wp_page_for_privacy_policy`
    Then STDOUT should be:
      """
      0
      """

  Scenario: Empty a site and its uploads directory
    Given a WP multisite installation
    And I run `wp site create --slug=foo`
    And I run `wp --url=example.com/foo option update uploads_use_yearmonth_folders 0`
    And download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |

    When I run `wp --url=example.com/foo media import {CACHE_DIR}/large-image.jpg --post_id=1`
    Then the wp-content/uploads/sites/2/large-image.jpg file should exist

    When I run `wp site empty --uploads --yes`
    Then STDOUT should not be empty
    And the wp-content/uploads/sites/2/large-image.jpg file should exist

    When I run `wp post list --format=ids`
    Then STDOUT should be empty

    When I run `wp --url=example.com/foo site empty --uploads --yes`
    Then STDOUT should be:
      """
      Success: The site at 'http://example.com/foo' was emptied.
      """
    And the wp-content/uploads/sites/2/large-image.jpg file should not exist

    When I run `wp --url=example.com/foo post list --format=ids`
    Then STDOUT should be empty
