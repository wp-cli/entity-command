Feature: Prune unused taxonomy terms

  Background:
    Given a WP install

  Scenario: Prune terms with no published posts
    When I run `wp term create post_tag 'Unused Tag' --slug=unused-tag --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp term prune post_tag`
    Then STDOUT should contain:
      """
      Deleted post_tag {TERM_ID}.
      """
    And STDOUT should contain:
      """
      Success:
      """
    And the return code should be 0

    When I try `wp term get post_tag {TERM_ID}`
    Then STDERR should contain:
      """
      Error: Term doesn't exist.
      """

  Scenario: Does not prune terms with more than one published post
    When I run `wp term create post_tag 'Popular Tag' --slug=popular-tag --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp post create --post_title='Post 1' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID_1}

    When I run `wp post create --post_title='Post 2' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID_2}

    When I run `wp post term set {POST_ID_1} post_tag {TERM_ID} --by=id`
    Then STDOUT should not be empty

    When I run `wp post term set {POST_ID_2} post_tag {TERM_ID} --by=id`
    Then STDOUT should not be empty

    When I run `wp term prune post_tag`
    Then STDOUT should not contain:
      """
      Deleted post_tag {TERM_ID}.
      """

    When I run `wp term get post_tag {TERM_ID} --field=name`
    Then STDOUT should be:
      """
      Popular Tag
      """

  Scenario: Prune terms with exactly one published post
    When I run `wp term create post_tag 'Single Post Tag' --slug=single-post-tag --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp post create --post_title='Post 1' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} post_tag {TERM_ID} --by=id`
    Then STDOUT should not be empty

    When I run `wp term prune post_tag`
    Then STDOUT should contain:
      """
      Deleted post_tag {TERM_ID}.
      """
    And the return code should be 0

    When I try `wp term get post_tag {TERM_ID}`
    Then STDERR should contain:
      """
      Error: Term doesn't exist.
      """

  Scenario: Dry run previews terms without deleting them
    When I run `wp term create post_tag 'Unused Tag' --slug=unused-tag --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp term prune post_tag --dry-run`
    Then STDOUT should contain:
      """
      Would delete post_tag {TERM_ID}.
      """
    And STDOUT should contain:
      """
      Success:
      """
    And the return code should be 0

    When I run `wp term get post_tag {TERM_ID} --field=name`
    Then STDOUT should be:
      """
      Unused Tag
      """

  Scenario: Prune with an invalid taxonomy
    When I try `wp term prune nonexistent_taxonomy`
    Then STDERR should be:
      """
      Error: Taxonomy nonexistent_taxonomy doesn't exist.
      """
    And the return code should be 1

  Scenario: Prune multiple taxonomies at once
    # Assign an extra post to the default Uncategorized category so its count
    # exceeds the prune threshold and it won't interfere with the test.
    When I run `wp post create --post_title='Extra Post' --post_status=publish --post_category=1 --porcelain`
    Then STDOUT should be a number

    When I run `wp term create post_tag 'Unused Tag' --slug=unused-tag --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TAG_TERM_ID}

    When I run `wp term create category 'Unused Category' --slug=unused-category --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CAT_TERM_ID}

    When I run `wp term prune post_tag category`
    Then STDOUT should contain:
      """
      Deleted post_tag {TAG_TERM_ID}.
      """
    And STDOUT should contain:
      """
      Deleted category {CAT_TERM_ID}.
      """
    And the return code should be 0
