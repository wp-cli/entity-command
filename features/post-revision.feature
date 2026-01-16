Feature: Manage WordPress post revisions

  Background:
    Given a WP install

  # Creating a published post doesn't create an initial revision,
  # so we update it twice here and restore the middle version.
  # See https://github.com/wp-cli/entity-command/issues/564.
  Scenario: Restore a post revision
    When I run `wp post create --post_title='Original Post' --post_content='Original content' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_content='Updated content'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post list --post_type=revision --post_parent={POST_ID} --format=ids`
    Then STDOUT should not be empty
    And save STDOUT as {REVISION_ID}

    When I run `wp post update {POST_ID} --post_content='Another one'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      Another one
      """

    When I run `wp post revision restore {REVISION_ID}`
    Then STDOUT should contain:
      """
      Success: Restored revision
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      Updated content
      """

  Scenario: Restore invalid revision should fail
    When I try `wp post revision restore 99999`
    Then STDERR should contain:
      """
      Error: Invalid revision ID
      """
    And the return code should be 1

  Scenario: Show diff between two revisions
    When I run `wp post create --post_title='Test Post' --post_content='First version' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_content='Second version'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post update {POST_ID} --post_title='New Title' --post_content='Third version'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post list --post_type=revision --post_parent={POST_ID} --fields=ID --format=ids --orderby=ID --order=ASC`
    Then STDOUT should not be empty
    And save STDOUT as {REVISION_IDS}

    When I run `echo "{REVISION_IDS}" | awk '{print $1}'`
    Then save STDOUT as {REVISION_ID_1}

    When I run `echo "{REVISION_IDS}" | awk '{print $2}'`
    Then save STDOUT as {REVISION_ID_2}

    When I run `wp post revision diff {REVISION_ID_1} {REVISION_ID_2}`
    Then STDOUT should contain:
      """
      - Second version
      + Third version
      """
    And STDOUT should contain:
      """
      --- Test Post
      """
    And STDOUT should contain:
      """
      +++ New Title
      """

  Scenario: Show diff between revision and current post
    When I run `wp post create --post_title='Diff Test' --post_content='Original text' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_content='Modified text'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post list --post_type=revision --post_parent={POST_ID} --fields=ID --format=ids --orderby=ID --order=ASC`
    Then STDOUT should not be empty
    And save STDOUT as {REVISION_ID}

    When I run `wp post revision diff {REVISION_ID}`
    Then STDOUT should contain:
      """
      Success: No difference found.
      """

  Scenario: Diff with invalid revision should fail
    When I try `wp post revision diff 99999`
    Then STDERR should contain:
      """
      Error: Invalid 'from' ID
      """
    And the return code should be 1

  Scenario: Diff between two invalid revisions should fail
    When I try `wp post revision diff 99998 99999`
    Then STDERR should contain:
      """
      Error: Invalid 'from' ID
      """
    And the return code should be 1

  Scenario: Diff with specific field
    When I run `wp post create --post_title='Field Test' --post_content='Some content' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_title='Modified Field Test'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post list --post_type=revision --post_parent={POST_ID} --fields=ID --format=ids --orderby=ID --order=ASC`
    Then STDOUT should not be empty
    And save STDOUT as {REVISION_ID}

    When I run `wp post revision diff {REVISION_ID} --field=post_title`
    Then the return code should be 0
