Feature: Migrate term custom fields

  @require-wp-4.4
  Scenario: Migrate an existing term by slug
    Given a WP install

    When I run `wp term create category apple`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category apple`
    Then STDOUT should not be empty

    When I run `wp term migrate apple --by=slug --from=category --to=post_tag`
    Then STDOUT should be:
      """
      Term 'apple' assigned to post {POST_ID}.
      Term 'apple' migrated.
      Old instance of term 'apple' removed from its original taxonomy.
      Success: Migrated the term 'apple' from taxonomy 'category' to taxonomy 'post_tag' for 1 post.
      """

  @require-wp-4.4
  Scenario: Migrate an existing term by ID
    Given a WP install

    When I run `wp term create category apple --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category {TERM_ID}`
    Then STDOUT should not be empty

    When I run `wp term migrate {TERM_ID} --by=slug --from=category --to=post_tag`
    Then STDOUT should be:
      """
      Term '{TERM_ID}' assigned to post {POST_ID}.
      Term '{TERM_ID}' migrated.
      Old instance of term '{TERM_ID}' removed from its original taxonomy.
      Success: Migrated the term '{TERM_ID}' from taxonomy 'category' to taxonomy 'post_tag' for 1 post.
      """

  @require-wp-4.4
  Scenario: Migrate a term in multiple posts
    Given a WP install

    When I run `wp term create category orange`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category orange`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post 2' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID_2}

    When I run `wp post term set {POST_ID_2} category orange`
    Then STDOUT should not be empty

    When I run `wp term migrate orange --by=slug --from=category --to=post_tag`
    Then STDOUT should be:
      """
      Term 'orange' assigned to post {POST_ID}.
      Term 'orange' assigned to post {POST_ID_2}.
      Term 'orange' migrated.
      Old instance of term 'orange' removed from its original taxonomy.
      Success: Migrated the term 'orange' from taxonomy 'category' to taxonomy 'post_tag' for 2 posts.
      """

  @require-wp-4.4
  Scenario: Try to migrate a term that does not exist
    Given a WP install

    When I try `wp term migrate peach --by=slug --from=category --to=post_tag`
    Then STDERR should be:
      """
      Error: Taxonomy term 'peach' for taxonomy 'category' doesn't exist.
      """

  @require-wp-4.4
  Scenario: Migrate a term when posts have been migrated to a different post type that supports the destination taxonomy
    Given a WP install
    And a wp-content/mu-plugins/test-migrate.php file:
      """
      <?php
      // Plugin Name: Test Migrate

      add_action( 'init', function() {
        register_post_type( 'news', [ 'public' => true ] );
        register_taxonomy( 'topic', 'news', [ 'public' => true ] );
      } );
      """

    When I run `wp term create category grape`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post' --post_type=post --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category grape`
    Then STDOUT should not be empty

    When I run `wp post update {POST_ID} --post_type=news`
    Then STDOUT should not be empty

    When I run `wp term migrate grape --by=slug --from=category --to=topic`
    Then STDOUT should be:
      """
      Term 'grape' assigned to post {POST_ID}.
      Term 'grape' migrated.
      Old instance of term 'grape' removed from its original taxonomy.
      Success: Migrated the term 'grape' from taxonomy 'category' to taxonomy 'topic' for 1 post.
      """

  @require-wp-4.4
  Scenario: Migrate a term warns when post type is not registered with destination taxonomy
    Given a WP install

    When I run `wp term create category grape`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post' --post_type=post --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category grape`
    Then STDOUT should not be empty

    When I run `wp post update {POST_ID} --post_type=page`
    Then STDOUT should not be empty

    When I run `wp term migrate grape --by=slug --from=category --to=post_tag`
    Then STDERR should contain:
      """
      Warning: Term 'grape' not assigned to post {POST_ID}. Post type 'page' is not registered with taxonomy 'post_tag'.
      """
    And STDOUT should contain:
      """
      Success: Migrated the term 'grape' from taxonomy 'category' to taxonomy 'post_tag' for 0 posts.
      """
