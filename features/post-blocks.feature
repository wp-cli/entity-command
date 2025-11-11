Feature: Manage WordPress post blocks

  Background:
    Given a WP install

  @require-wp-5.0
  Scenario: Check if a post has blocks
    When I run `wp post create --post_title='Block post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post has-blocks {POST_ID}`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} has blocks.
      """
    And the return code should be 0

  @require-wp-5.0
  Scenario: Check if a post does not have blocks
    When I run `wp post create --post_title='Regular post' --post_content='<p>Hello World</p>' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I try `wp post has-blocks {POST_ID}`
    Then STDERR should be empty
    And the return code should be 1

  @require-wp-5.0
  Scenario: Check if a post contains a specific block type
    When I run `wp post create --post_title='Block post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post has-block {POST_ID} core/paragraph`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains the block 'core/paragraph'.
      """
    And the return code should be 0

    When I try `wp post has-block {POST_ID} core/image`
    Then STDERR should be empty
    And the return code should be 1

  @require-wp-5.0
  Scenario: Parse blocks from a post
    When I run `wp post create --post_title='Block post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post parse-blocks {POST_ID}`
    Then STDOUT should be JSON containing:
      """
      [
          {
              "blockName": "core/paragraph",
              "attrs": [],
              "innerBlocks": [],
              "innerHTML": "<p>Hello World</p>",
              "innerContent": [
                  "<p>Hello World</p>"
              ]
          }
      ]
      """

    When I run `wp post parse-blocks {POST_ID} --format=yaml`
    Then STDOUT should contain:
      """
      blockName:
      """
    And STDOUT should contain:
      """
      core/paragraph
      """

  @require-wp-5.0
  Scenario: Render blocks from a post
    When I run `wp post create --post_title='Block post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post render-blocks {POST_ID}`
    Then STDOUT should contain:
      """
      <p>Hello World</p>
      """

  @require-wp-5.0
  Scenario: Post get command includes block_version field
    When I run `wp post create --post_title='Block post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --field=block_version`
    Then STDOUT should match /^\d+$/

  @less-than-wp-5.0
  Scenario: Post block commands require WordPress 5.0+
    When I try `wp post has-blocks 1`
    Then STDERR should contain:
      """
      Error: This command requires WordPress 5.0 or greater.
      """
    And the return code should be 1
