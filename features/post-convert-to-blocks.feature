Feature: Convert classic post content to blocks

  # The conversion depends on the server-side block conversion from
  # https://github.com/WordPress/gutenberg/pull/82013 (branch
  # `try/13163-php-block-conversion`), which no released Gutenberg build
  # provides yet. The scenarios tagged @broken below need a Gutenberg build
  # of that PR. To run them locally, build the plugin ZIP from that branch,
  # remove the @broken tags, and add the following step after the
  # `Given a WP install` step in each scenario:
  #
  #   And I run `wp plugin install /path/to/gutenberg.zip --activate`

  Scenario: Conversion requires the Gutenberg build with server-side block conversion
    Given a WP install

    When I try `wp post convert-to-blocks 1`
    Then STDERR should be:
      """
      Error: Server-side block conversion is not available. Activate the Gutenberg plugin from https://github.com/WordPress/gutenberg/pull/82013.
      """
    And STDOUT should be empty
    And the return code should be 1

  @broken
  Scenario: Convert a classic post to blocks
    Given a WP install

    When I run `wp post create --post_title='Classic post' --post_content='<h2>Title</h2><p>Text</p>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post convert-to-blocks {POST_ID}`
    Then STDOUT should be:
      """
      Converted post {POST_ID}.
      Success: Converted 1 of 1 posts.
      """
    And STDERR should be empty

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      <!-- wp:heading -->
      """
    And STDOUT should contain:
      """
      <!-- wp:paragraph -->
      """

    When I run `wp post has-blocks {POST_ID}`
    Then STDOUT should be:
      """
      Success: Post {POST_ID} contains blocks.
      """

  @broken
  Scenario: Preview a conversion with --dry-run
    Given a WP install

    When I run `wp post create --post_title='Classic post' --post_content='<h2>Title</h2><p>Text</p>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post convert-to-blocks {POST_ID} --dry-run`
    Then STDOUT should be:
      """
      Would convert post {POST_ID}.
      Success: Would convert 1 of 1 posts.
      """
    And STDERR should be empty

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should be:
      """
      <h2>Title</h2><p>Text</p>
      """

  @broken
  Scenario: Skip posts that already contain blocks
    Given a WP install

    When I run `wp post create --post_title='Block post' --post_content='<!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post convert-to-blocks {POST_ID}`
    Then STDERR should be:
      """
      Warning: Post {POST_ID} already contains blocks.
      """
    And STDOUT should be:
      """
      Success: Converted 0 of 1 posts (1 skipped).
      """
    And the return code should be 0

  @broken
  Scenario: Skip posts without content
    Given a WP install

    When I run `wp post create --post_title='Empty post' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post convert-to-blocks {POST_ID}`
    Then STDERR should be:
      """
      Warning: Post {POST_ID} has no content to convert.
      """
    And STDOUT should be:
      """
      Success: Converted 0 of 1 posts (1 skipped).
      """
    And the return code should be 0

  @broken
  Scenario: Report missing posts as failures
    Given a WP install

    When I run `wp post create --post_title='Classic post' --post_content='<p>Text</p>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post convert-to-blocks {POST_ID} 99999`
    Then STDERR should contain:
      """
      Warning: Could not find the post with ID 99999.
      """
    And STDERR should contain:
      """
      Error: Only converted 1 of 2 posts (1 failed).
      """
    And STDOUT should be:
      """
      Converted post {POST_ID}.
      """
    And the return code should be 1
