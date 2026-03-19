Feature: Manage WordPress notes

  Background:
    Given a WP install

  @require-wp-6.9
  Scenario: Create and list notes
    When I run `wp comment create --comment_post_ID=1 --comment_content='This is a note about the block' --comment_author='Editor' --comment_type='note' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTE_ID}

    When I run `wp comment get {NOTE_ID} --field=comment_type`
    Then STDOUT should be:
      """
      note
      """

    When I run `wp comment list --type=note --post_id=1 --format=ids`
    Then STDOUT should be:
      """
      {NOTE_ID}
      """

    When I run `wp comment list --type=note --post_id=1 --fields=comment_ID,comment_type,comment_content`
    Then STDOUT should be a table containing rows:
      | comment_ID | comment_type | comment_content                |
      | {NOTE_ID}  | note         | This is a note about the block |

  @require-wp-6.9
  Scenario: Notes are not shown by default in comment list
    When I run `wp comment create --comment_post_ID=1 --comment_content='Regular comment' --comment_author='User' --porcelain`
    Then save STDOUT as {COMMENT_ID}

    When I run `wp comment create --comment_post_ID=1 --comment_content='This is a note' --comment_author='Editor' --comment_type='note' --porcelain`
    Then save STDOUT as {NOTE_ID}

    When I run `wp comment list --post_id=1 --format=ids`
    Then STDOUT should contain:
      """
      {COMMENT_ID}
      """
    And STDOUT should not contain:
      """
      {NOTE_ID}
      """

    When I run `wp comment list --type=note --post_id=1 --format=ids`
    Then STDOUT should be:
      """
      {NOTE_ID}
      """

  @require-wp-6.9
  Scenario: Reply to a note
    When I run `wp comment create --comment_post_ID=1 --comment_content='Initial note' --comment_author='Editor1' --comment_type='note' --porcelain`
    Then save STDOUT as {PARENT_NOTE_ID}

    When I run `wp comment create --comment_post_ID=1 --comment_content='Reply to note' --comment_author='Editor2' --comment_type='note' --comment_parent={PARENT_NOTE_ID} --porcelain`
    Then save STDOUT as {REPLY_NOTE_ID}

    When I run `wp comment get {REPLY_NOTE_ID} --field=comment_parent`
    Then STDOUT should be:
      """
      {PARENT_NOTE_ID}
      """

    When I run `wp comment list --type=note --post_id=1 --format=count`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-6.9
  Scenario: Resolve a note
    When I run `wp comment create --comment_post_ID=1 --comment_content='Note to be resolved' --comment_author='Editor' --comment_type='note' --porcelain`
    Then save STDOUT as {NOTE_ID}

    When I run `wp comment create --comment_post_ID=1 --comment_content='Resolving' --comment_author='Editor' --comment_type='note' --comment_parent={NOTE_ID} --porcelain`
    Then save STDOUT as {RESOLVE_NOTE_ID}

    When I run `wp comment meta add {RESOLVE_NOTE_ID} _wp_note_status resolved`
    Then STDOUT should contain:
      """
      Success: Added custom field.
      """

    When I run `wp comment meta get {RESOLVE_NOTE_ID} _wp_note_status`
    Then STDOUT should be:
      """
      resolved
      """

  @require-wp-6.9
  Scenario: Reopen a resolved note
    When I run `wp comment create --comment_post_ID=1 --comment_content='Note to resolve and reopen' --comment_author='Editor' --comment_type='note' --porcelain`
    Then save STDOUT as {NOTE_ID}

    When I run `wp comment create --comment_post_ID=1 --comment_content='Resolving' --comment_author='Editor' --comment_type='note' --comment_parent={NOTE_ID} --porcelain`
    Then save STDOUT as {RESOLVE_NOTE_ID}

    When I run `wp comment meta add {RESOLVE_NOTE_ID} _wp_note_status resolved`
    Then STDOUT should contain:
      """
      Success: Added custom field.
      """

    When I run `wp comment create --comment_post_ID=1 --comment_content='Reopening' --comment_author='Editor' --comment_type='note' --comment_parent={NOTE_ID} --porcelain`
    Then save STDOUT as {REOPEN_NOTE_ID}

    When I run `wp comment meta add {REOPEN_NOTE_ID} _wp_note_status reopen`
    Then STDOUT should contain:
      """
      Success: Added custom field.
      """

    When I run `wp comment meta get {REOPEN_NOTE_ID} _wp_note_status`
    Then STDOUT should be:
      """
      reopen
      """

  @require-wp-6.9
  Scenario: List notes with comment meta
    When I run `wp comment create --comment_post_ID=1 --comment_content='First note' --comment_author='Editor' --comment_type='note' --porcelain`
    Then save STDOUT as {NOTE1_ID}

    When I run `wp comment create --comment_post_ID=1 --comment_content='Resolved note' --comment_author='Editor' --comment_type='note' --comment_parent={NOTE1_ID} --porcelain`
    Then save STDOUT as {NOTE2_ID}

    When I run `wp comment meta add {NOTE2_ID} _wp_note_status resolved`
    Then STDOUT should contain:
      """
      Success: Added custom field.
      """

    When I run `wp comment meta list {NOTE2_ID} --keys=_wp_note_status`
    Then STDOUT should be a table containing rows:
      | comment_id | meta_key         | meta_value |
      | {NOTE2_ID} | _wp_note_status  | resolved   |

  @require-wp-6.9
  Scenario: Get notes for multiple posts
    When I run `wp post create --post_title='Post 2' --porcelain`
    Then save STDOUT as {POST2_ID}

    When I run `wp comment create --comment_post_ID=1 --comment_content='Note on post 1' --comment_author='Editor' --comment_type='note' --porcelain`
    Then save STDOUT as {NOTE1_ID}

    When I run `wp comment create --comment_post_ID={POST2_ID} --comment_content='Note on post 2' --comment_author='Editor' --comment_type='note' --porcelain`
    Then save STDOUT as {NOTE2_ID}

    When I run `wp comment list --type=note --post_id=1 --format=ids`
    Then STDOUT should be:
      """
      {NOTE1_ID}
      """

    When I run `wp comment list --type=note --post_id={POST2_ID} --format=ids`
    Then STDOUT should be:
      """
      {NOTE2_ID}
      """
