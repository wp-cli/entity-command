Feature: Manage WordPress posts revision

  Background:
    Given a WP install

  @list
  Scenario: Posts revision list
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {FIRST_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated again'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {SECOND_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated again and again'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {THIRD_REVISON_ID}

    When I run `wp post revision list {POST_ID} --latest --fields='ID,post_title,post_parent'`
    Then STDOUT should be a table containing rows:
      | ID                  | post_title                        | post_parent |
      | {THIRD_REVISON_ID}  | Test post updated again and again | {POST_ID}   |
      | {SECOND_REVISON_ID} | Test post updated again           | {POST_ID}   |
      | {FIRST_REVISON_ID}  | Test post updated                 | {POST_ID}   |

    When I run `wp post revision list {POST_ID} --earliest --fields='ID,post_title,post_parent'`
    Then STDOUT should be a table containing rows:
      | ID                  | post_title                       | post_parent |
      | {FIRST_REVISON_ID}  | Test post updated                | {POST_ID}   |
      | {SECOND_REVISON_ID} | Test post updated again          | {POST_ID}   |
      | {THIRD_REVISON_ID} | Test post updated again and again | {POST_ID} |

  @get
  Scenario: Posts revision get
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {EARLIEST_REVISION_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated again'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {LATEST_REVISION_ID}

    When I run `wp post revision get {LATEST_REVISION_ID} --field=post_title`
    Then STDOUT should be:
      """
      Test post updated again
      """

    When I run `wp post revision get {EARLIEST_REVISION_ID} --field=post_title`
    Then STDOUT should be:
      """
      Test post updated
      """

    When I run `wp post revision get {EARLIEST_REVISION_ID} --fields=ID,post_title`
    Then STDOUT should be a table containing rows:
      | Field      | Value                  |
      | ID         | {EARLIEST_REVISION_ID} |
      | post_title | Test post updated      |

    When I run `wp post revision get {LATEST_REVISION_ID} --fields=ID,post_title,post_parent`
    Then STDOUT should be a table containing rows:
      | Field       | Value                   |
      | ID          | {LATEST_REVISION_ID}    |
      | post_title  | Test post updated again |
      | post_parent | {POST_ID}               |

  @delete
  Scenario: Posts revision delete
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {FIRST_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated again'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {SECOND_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated again and again'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {THIRD_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated 4th time'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {FOURTH_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Test post updated 5th time'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {FIFTH_REVISON_ID}

    When I try `wp post revision delete {POST_ID}`
    Then the return code should be 0
    And STDERR should be:
      """
      Warning: {POST_ID} This would not be revision ID. Please provide valid revision ID.
      """

    When I run `wp post revision delete {SECOND_REVISON_ID}`
    Then STDOUT should be:
      """
      Success: Deleted revision {SECOND_REVISON_ID}.
      """

    When I run `wp post revision delete $(wp post revision list {POST_ID} --earliest=2 --format=ids)`
    Then STDOUT should be:
      """
      Success: Deleted revision {FIRST_REVISON_ID}.
      Success: Deleted revision {THIRD_REVISON_ID}.
      """

    When I run `wp post revision delete`
    Then STDOUT should be:
      """
      Success: Deleted revision {FOURTH_REVISON_ID}.
      Success: Deleted revision {FIFTH_REVISON_ID}.
      """

  @prune
  Scenario: Posts revision prune
    When I run `wp post delete $(wp post list --format=ids)`
    And I run `wp post delete $(wp post list --post_type=page --format=ids)`
    And I try `wp post revision prune`
    Then STDERR should be:
      """
      Error: No posts found.
      """

    When I run `wp post create --post_title='Test post for PAGE post type' --post_type='page' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PAGE_ID}

    When I try `wp post revision prune {PAGE_ID}`
    Then the return code should be 0
    And STDERR should be:
      """
      Warning: No revision found for post #{PAGE_ID}.
      """

    When I run `wp post update {PAGE_ID} --post_title='Test post updated'`
    And I run `wp post revision list {PAGE_ID} --format=ids --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {PAGE_FIRST_REVISON_ID}

    When I run `wp post update {PAGE_ID} --post_title='Test post updated again'`
    And I run `wp post revision list {PAGE_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {PAGE_SECOND_REVISON_ID}

    When I run `wp post update {PAGE_ID} --post_title='Test post updated again and again'`
    And I run `wp post revision list {PAGE_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {PAGE_THIRD_REVISON_ID}

    When I run `wp post update {PAGE_ID} --post_title='Test post updated 4th time'`
    And I run `wp post revision list {PAGE_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {PAGE_FOURTH_REVISON_ID}

    When I run `wp post update {PAGE_ID} --post_title='Test post updated 5th time'`
    And I run `wp post revision list {PAGE_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {PAGE_FIFTH_REVISON_ID}

    When I run `wp post create --post_title='Test post for POST post type' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_title='Updated POST post type post'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {POST_POST_TYPE_FIRST_REVISON_ID}

    When I run `wp post update {POST_ID} --post_title='Updated POST post type post again'`
    And I run `wp post revision list {POST_ID} --field=ID --latest=1`
    Then STDOUT should be a number
    And save STDOUT as {POST_POST_TYPE_SECOND_REVISON_ID}

    When I run `wp post revision prune --post_type='page' --earliest=2`
    Then STDOUT should be:
      """
      Deleting revision for post #{PAGE_ID}.
      Success: Deleted revision {PAGE_FIRST_REVISON_ID}.
      Success: Deleted revision {PAGE_SECOND_REVISON_ID}.
      """

    When I run `wp post revision list {POST_ID} --fields=ID`
    Then STDOUT should be a table containing rows:
      | ID                                 |
      | {POST_POST_TYPE_FIRST_REVISON_ID}  |
      | {POST_POST_TYPE_SECOND_REVISON_ID} |

    When I run `wp post revision prune --post_type='page' --latest=1`
    Then STDOUT should be:
      """
      Deleting revision for post #{PAGE_ID}.
      Success: Deleted revision {PAGE_FIFTH_REVISON_ID}.
      """

    When I run `wp post revision prune --post_type='page'`
    Then STDOUT should be:
      """
      Deleting revision for post #{PAGE_ID}.
      Success: Deleted revision {PAGE_FOURTH_REVISON_ID}.
      Success: Deleted revision {PAGE_THIRD_REVISON_ID}.
      """

    When I run `wp post revision prune`
    Then STDOUT should be:
      """
      Deleting revision for post #{POST_ID}.
      Success: Deleted revision {POST_POST_TYPE_SECOND_REVISON_ID}.
      Success: Deleted revision {POST_POST_TYPE_FIRST_REVISON_ID}.
      """
