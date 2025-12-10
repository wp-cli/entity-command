Feature: Manage blocks in post content

  @require-wp-5.0
  Scenario: Check if a post has blocks
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post has-blocks {POST_ID}`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains blocks.
      """

    When I run `wp post create --post_title='Classic Post' --post_content='<p>Hello classic</p>' --porcelain`
    Then save STDOUT as {CLASSIC_ID}

    When I try `wp post has-blocks {CLASSIC_ID}`
    Then STDERR should contain:
      """
      Error: Post {CLASSIC_ID} does not contain blocks.
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Check if a post has a specific block
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post has-block {POST_ID} core/paragraph`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains block 'core/paragraph'.
      """

    When I run `wp post has-block {POST_ID} core/heading`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains block 'core/heading'.
      """

    When I try `wp post has-block {POST_ID} core/image`
    Then STDERR should contain:
      """
      Error: Post {POST_ID} does not contain block 'core/image'.
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Parse blocks in a post
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph {"align":"center"} --><p>Hello</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "blockName": "core/paragraph"
      """
    And STDOUT should contain:
      """
      "align": "center"
      """

    When I run `wp post block parse {POST_ID} --format=yaml`
    Then STDOUT should contain:
      """
      blockName: core/paragraph
      """

    When I run `wp post block parse {POST_ID} --raw`
    Then STDOUT should contain:
      """
      innerHTML
      """

  @require-wp-5.0
  Scenario: List blocks in a post
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>One</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Two</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/paragraph | 2     |
      | core/heading   | 1     |

    When I run `wp post block list {POST_ID} --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"blockName":"core/paragraph","count":2}]
      """

    When I run `wp post block list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-5.0
  Scenario: List nested blocks
    Given a WP install
    When I run `wp post create --post_title='Nested Blocks' --post_content='<!-- wp:group --><!-- wp:paragraph --><p>Nested</p><!-- /wp:paragraph --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName  | count |
      | core/group | 1     |
    And STDOUT should not contain:
      """
      core/paragraph
      """

    When I run `wp post block list {POST_ID} --nested`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/group     | 1     |
      | core/paragraph | 1     |

  @require-wp-5.0
  Scenario: Render blocks to HTML
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block render {POST_ID}`
    Then STDOUT should contain:
      """
      <p>Hello World</p>
      """
    And STDOUT should contain:
      """
      <h2
      """
    And STDOUT should contain:
      """
      Title</h2>
      """

    When I run `wp post block render {POST_ID} --block=core/paragraph`
    Then STDOUT should contain:
      """
      <p>Hello World</p>
      """
    And STDOUT should not contain:
      """
      Title</h2>
      """

  @require-wp-5.0
  Scenario: Insert a block into a post
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block insert {POST_ID} core/paragraph --content="Added at end"`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      Added at end
      """

    When I run `wp post block insert {POST_ID} core/heading --content="Title" --position=start`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/paragraph | 2     |
      | core/heading   | 1     |

  @require-wp-5.0
  Scenario: Insert a block with attributes
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block insert {POST_ID} core/heading --content="Title" --attrs='{"level":3}'`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {POST_ID}.
      """

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "level": 3
      """

  @require-wp-5.0
  Scenario: Remove a block by index
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Third</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} --index=1`
    Then STDOUT should contain:
      """
      Success: Removed 1 block from post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      First
      """
    And STDOUT should contain:
      """
      Third
      """
    And STDOUT should not contain:
      """
      Second
      """

  @require-wp-5.0
  Scenario: Remove multiple blocks by indices
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Third</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} --index=0,2`
    Then STDOUT should contain:
      """
      Success: Removed 2 blocks from post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      Second
      """
    And STDOUT should not contain:
      """
      First
      """
    And STDOUT should not contain:
      """
      Third
      """

  @require-wp-5.0
  Scenario: Remove blocks by name
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Para 1</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Heading</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Para 2</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} core/paragraph`
    Then STDOUT should contain:
      """
      Success: Removed 1 block from post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/paragraph | 1     |
      | core/heading   | 1     |

  @require-wp-5.0
  Scenario: Remove all blocks of a type
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Para 1</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Heading</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Para 2</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} core/paragraph --all`
    Then STDOUT should contain:
      """
      Success: Removed 2 blocks from post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName    | count |
      | core/heading | 1     |
    And STDOUT should not contain:
      """
      core/paragraph
      """

  @require-wp-5.0
  Scenario: Replace blocks
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block replace {POST_ID} core/paragraph core/heading`
    Then STDOUT should contain:
      """
      Success: Replaced 1 block in post {POST_ID}.
      """

    When I run `wp post has-block {POST_ID} core/heading`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains block 'core/heading'.
      """

    When I try `wp post has-block {POST_ID} core/paragraph`
    Then the return code should be 1

  @require-wp-5.0
  Scenario: Replace all blocks of a type
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Para 1</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Para 2</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block replace {POST_ID} core/paragraph core/verse --all`
    Then STDOUT should contain:
      """
      Success: Replaced 2 blocks in post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName  | count |
      | core/verse | 2     |
    And STDOUT should not contain:
      """
      core/paragraph
      """

  @require-wp-5.0
  Scenario: Replace block with new attributes
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:heading {"level":2} --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block replace {POST_ID} core/heading core/heading --attrs='{"level":4}'`
    Then STDOUT should contain:
      """
      Success: Replaced 1 block in post {POST_ID}.
      """

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "level": 4
      """

  @require-wp-5.0
  Scenario: Error handling for invalid post
    Given a WP install

    When I try `wp post has-blocks 999999`
    Then STDERR should contain:
      """
      Could not find the post
      """
    And the return code should be 1

    When I try `wp post block list 999999`
    Then STDERR should contain:
      """
      Could not find the post
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Error handling for remove without block name or index
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block remove {POST_ID}`
    Then STDERR should contain:
      """
      Error: You must specify either a block name or --index.
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Porcelain output for insert
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block insert {POST_ID} core/paragraph --content="New" --porcelain`
    Then STDOUT should be:
      """
      {POST_ID}
      """

  @require-wp-5.0
  Scenario: Porcelain output for remove
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} --index=0 --porcelain`
    Then STDOUT should be:
      """
      1
      """

  @require-wp-5.0
  Scenario: Porcelain output for replace
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block replace {POST_ID} core/paragraph core/heading --porcelain`
    Then STDOUT should be:
      """
      1
      """

  @require-wp-5.0
  Scenario: Get a block by index
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph {"align":"center"} --><p>First</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block get {POST_ID} 0`
    Then STDOUT should contain:
      """
      "blockName": "core/paragraph"
      """
    And STDOUT should contain:
      """
      "align": "center"
      """

    When I run `wp post block get {POST_ID} 1`
    Then STDOUT should contain:
      """
      "blockName": "core/heading"
      """
    And STDOUT should contain:
      """
      "level": 2
      """

    When I run `wp post block get {POST_ID} 0 --format=yaml`
    Then STDOUT should contain:
      """
      blockName: core/paragraph
      """

    When I run `wp post block get {POST_ID} 0 --raw`
    Then STDOUT should contain:
      """
      innerHTML
      """

  @require-wp-5.0
  Scenario: Error on invalid block index
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block get {POST_ID} 5`
    Then STDERR should contain:
      """
      Invalid index: 5
      """
    And the return code should be 1

    When I try `wp post block get {POST_ID} -1`
    Then STDERR should contain:
      """
      Invalid index: -1
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Update block attributes
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:heading {"level":2} --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block update {POST_ID} 0 --attrs='{"level":3}'`
    Then STDOUT should contain:
      """
      Success: Updated block at index 0 in post {POST_ID}.
      """

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "level": 3
      """

  @require-wp-5.0
  Scenario: Update block content
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Old text</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block update {POST_ID} 0 --content="<p>New text</p>"`
    Then STDOUT should contain:
      """
      Success: Updated block at index 0 in post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      New text
      """
    And STDOUT should not contain:
      """
      Old text
      """

  @require-wp-5.0
  Scenario: Update block with replace-attrs flag
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:heading {"level":2,"align":"center"} --><h2 class="has-text-align-center">Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block update {POST_ID} 0 --attrs='{"level":4}' --replace-attrs`
    Then STDOUT should contain:
      """
      Success: Updated block at index 0 in post {POST_ID}.
      """

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "level": 4
      """
    And STDOUT should not contain:
      """
      "align"
      """

  @require-wp-5.0
  Scenario: Error when no attrs or content provided for update
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block update {POST_ID} 0`
    Then STDERR should contain:
      """
      You must specify either --attrs or --content.
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Porcelain output for update
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block update {POST_ID} 0 --content="<p>New</p>" --porcelain`
    Then STDOUT should be:
      """
      {POST_ID}
      """

  @require-wp-5.0
  Scenario: Move block forward
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Third</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block move {POST_ID} 0 2`
    Then STDOUT should contain:
      """
      Success: Moved block from index 0 to index 2 in post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /Second.*First/s

  @require-wp-5.0
  Scenario: Move block backward
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Third</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block move {POST_ID} 2 0`
    Then STDOUT should contain:
      """
      Success: Moved block from index 2 to index 0 in post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /Third.*First/s

  @require-wp-5.0
  Scenario: Move block same index warning
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block move {POST_ID} 0 0`
    Then STDERR should contain:
      """
      Source and destination indices are the same.
      """
    And the return code should be 0

  @require-wp-5.0
  Scenario: Error on invalid move indices
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block move {POST_ID} 5 0`
    Then STDERR should contain:
      """
      Invalid from-index: 5
      """
    And the return code should be 1

    When I try `wp post block move {POST_ID} 0 10`
    Then STDERR should contain:
      """
      Invalid to-index: 10
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Porcelain output for move
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block move {POST_ID} 0 1 --porcelain`
    Then STDOUT should be:
      """
      {POST_ID}
      """

  @require-wp-5.0
  Scenario: Export blocks to STDOUT as JSON
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block export {POST_ID}`
    Then STDOUT should contain:
      """
      "version": "1.0"
      """
    And STDOUT should contain:
      """
      "generator": "wp-cli/entity-command"
      """
    And STDOUT should contain:
      """
      "blockName": "core/paragraph"
      """

  @require-wp-5.0
  Scenario: Export blocks as YAML
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block export {POST_ID} --format=yaml`
    Then STDOUT should contain:
      """
      version:
      """
    And STDOUT should contain:
      """
      generator: wp-cli/entity-command
      """
    And STDOUT should contain:
      """
      blockName: core/paragraph
      """

  @require-wp-5.0
  Scenario: Export blocks as HTML
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block export {POST_ID} --format=html`
    Then STDOUT should contain:
      """
      <p>Hello World</p>
      """

  @require-wp-5.0
  Scenario: Export blocks to file
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block export {POST_ID} --file=blocks-export.json`
    Then STDOUT should contain:
      """
      Success: Exported 1 block to blocks-export.json
      """
    And the blocks-export.json file should contain:
      """
      "blockName": "core/paragraph"
      """

  @require-wp-5.0
  Scenario: Import blocks from file
    Given a WP install
    And a blocks-import.json file:
      """
      {
        "version": "1.0",
        "blocks": [
          {"blockName": "core/paragraph", "attrs": {}, "innerBlocks": [], "innerHTML": "<p>Imported</p>", "innerContent": ["<p>Imported</p>"]}
        ]
      }
      """
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:heading --><h2>Original</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID} --file=blocks-import.json`
    Then STDOUT should contain:
      """
      Success: Imported 1 block into post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/heading   | 1     |
      | core/paragraph | 1     |

  @require-wp-5.0
  Scenario: Import blocks at start
    Given a WP install
    And a blocks-import.json file:
      """
      {
        "blocks": [
          {"blockName": "core/paragraph", "attrs": {}, "innerBlocks": [], "innerHTML": "<p>First</p>", "innerContent": ["<p>First</p>"]}
        ]
      }
      """
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID} --file=blocks-import.json --position=start`
    Then STDOUT should contain:
      """
      Success: Imported 1 block into post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /First.*Second/s

  @require-wp-5.0
  Scenario: Import blocks with replace
    Given a WP install
    And a blocks-import.json file:
      """
      {
        "blocks": [
          {"blockName": "core/heading", "attrs": {"level": 2}, "innerBlocks": [], "innerHTML": "<h2>New Content</h2>", "innerContent": ["<h2>New Content</h2>"]}
        ]
      }
      """
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Old</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID} --file=blocks-import.json --replace`
    Then STDOUT should contain:
      """
      Success: Imported 1 block into post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName    | count |
      | core/heading | 1     |
    And STDOUT should not contain:
      """
      core/paragraph
      """

  @require-wp-5.0
  Scenario: Import error on missing file
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block import {POST_ID} --file=nonexistent.json`
    Then STDERR should contain:
      """
      File not found: nonexistent.json
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Porcelain output for import
    Given a WP install
    And a blocks-import.json file:
      """
      {
        "blocks": [
          {"blockName": "core/paragraph", "attrs": {}, "innerBlocks": [], "innerHTML": "<p>One</p>", "innerContent": ["<p>One</p>"]},
          {"blockName": "core/paragraph", "attrs": {}, "innerBlocks": [], "innerHTML": "<p>Two</p>", "innerContent": ["<p>Two</p>"]}
        ]
      }
      """
    When I run `wp post create --post_title='Block Post' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID} --file=blocks-import.json --porcelain`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-5.0
  Scenario: Count blocks across posts
    Given a WP install
    When I run `wp post create --post_title='Post 1' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Test2</p><!-- /wp:paragraph -->' --post_status=publish --porcelain`
    Then save STDOUT as {POST_1}

    When I run `wp post create --post_title='Post 2' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --post_status=publish --porcelain`
    Then save STDOUT as {POST_2}

    When I run `wp post block count {POST_1} {POST_2}`
    Then STDOUT should be a table containing rows:
      | blockName      | count | posts |
      | core/paragraph | 3     | 2     |
      | core/heading   | 1     | 1     |

  @require-wp-5.0
  Scenario: Count specific block type
    Given a WP install
    When I run `wp post create --post_title='Post 1' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Test2</p><!-- /wp:paragraph -->' --post_status=publish --porcelain`
    Then save STDOUT as {POST_1}

    When I run `wp post block count {POST_1} --block=core/paragraph --format=count`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-5.0
  Scenario: Count unique block types
    Given a WP install
    When I run `wp post create --post_title='Post 1' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --post_status=publish --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block count {POST_ID} --format=count`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-5.0
  Scenario: Clone block with default position (after)
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block clone {POST_ID} 0`
    Then STDOUT should contain:
      """
      Success: Cloned block to index 1 in post {POST_ID}.
      """

    When I run `wp post block list {POST_ID}`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/paragraph | 3     |

  @require-wp-5.0
  Scenario: Clone block to end
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block clone {POST_ID} 0 --position=end`
    Then STDOUT should contain:
      """
      Success: Cloned block to index 2 in post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /First.*Title.*First/s

  @require-wp-5.0
  Scenario: Clone block to start
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block clone {POST_ID} 1 --position=start`
    Then STDOUT should contain:
      """
      Success: Cloned block to index 0 in post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /Title.*First.*Title/s

  @require-wp-5.0
  Scenario: Porcelain output for clone
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block clone {POST_ID} 0 --porcelain`
    Then STDOUT should be:
      """
      1
      """

  @require-wp-5.0
  Scenario: Error on invalid clone index
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block clone {POST_ID} 5`
    Then STDERR should contain:
      """
      Invalid source-index: 5
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Extract attribute values
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:heading {"level":2} --><h2>Title 1</h2><!-- /wp:heading --><!-- wp:heading {"level":3} --><h3>Title 2</h3><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block extract {POST_ID} --block=core/heading --attr=level --format=ids`
    Then STDOUT should contain:
      """
      2
      """
    And STDOUT should contain:
      """
      3
      """

  @require-wp-5.0
  Scenario: Extract attribute from specific index
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:heading {"level":2} --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block extract {POST_ID} --index=0 --attr=level --format=ids`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-5.0
  Scenario: Extract content from blocks
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block extract {POST_ID} --block=core/paragraph --content --format=ids`
    Then STDOUT should contain:
      """
      Hello World
      """

  @require-wp-5.0
  Scenario: Extract error when no attr or content specified
    Given a WP install
    When I run `wp post create --post_title='Block Post' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block extract {POST_ID}`
    Then STDERR should contain:
      """
      You must specify either --attr or --content.
      """
    And the return code should be 1

  # ============================================================================
  # Phase 3: Extended Test Coverage - P0 (Critical) Tests
  # ============================================================================

  @require-wp-5.0
  Scenario: Check for nested block inside group
    Given a WP install
    When I run `wp post create --post_title='Nested' --post_content='<!-- wp:group --><!-- wp:paragraph --><p>Nested para</p><!-- /wp:paragraph --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    # Should find the nested paragraph
    When I run `wp post has-block {POST_ID} core/paragraph`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains block 'core/paragraph'.
      """

    # Should also find the container
    When I run `wp post has-block {POST_ID} core/group`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains block 'core/group'.
      """

  @require-wp-5.0
  Scenario: Partial block name does not match
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    # "core/para" should NOT match "core/paragraph"
    When I try `wp post has-block {POST_ID} core/para`
    Then STDERR should contain:
      """
      does not contain block 'core/para'
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Parse post with classic content (no blocks)
    Given a WP install
    When I run `wp post create --post_title='Classic' --post_content='<p>Just HTML, no blocks</p>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "blockName": null
      """

  @require-wp-5.0
  Scenario: Render dynamic block
    Given a WP install
    When I run `wp post create --post_title='Dynamic' --post_content='<!-- wp:archives /-->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block render {POST_ID}`
    # Dynamic blocks render at runtime - output depends on site content
    Then STDOUT should not be empty

  @require-wp-5.0
  Scenario: Insert block at specific numeric position
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Third</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block insert {POST_ID} core/paragraph --content="Second" --position=1`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /First.*Second.*Third/s

  @require-wp-5.0
  Scenario: Remove all blocks from post
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Only block</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} --index=0`
    Then STDOUT should contain:
      """
      Success: Removed 1 block from post {POST_ID}.
      """

    When I run `wp post block list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.0
  Scenario: Replace when no matches found
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block replace {POST_ID} core/image core/heading`
    Then STDERR should contain:
      """
      No blocks of type 'core/image' found
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Update with invalid attrs JSON
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block update {POST_ID} 0 --attrs='{not valid json'`
    Then STDERR should contain:
      """
      Invalid JSON
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Import invalid JSON file
    Given a WP install
    And a bad-import.json file:
      """
      {not valid json
      """
    When I run `wp post create --post_title='Test' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block import {POST_ID} --file=bad-import.json`
    Then STDERR should contain:
      """
      Invalid JSON
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Count blocks filtered by post type
    Given a WP install
    When I run `wp post create --post_title='Post' --post_type=post --post_content='<!-- wp:paragraph --><p>Post</p><!-- /wp:paragraph -->' --post_status=publish --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post create --post_title='Page' --post_type=page --post_content='<!-- wp:heading --><h2>Page</h2><!-- /wp:heading -->' --post_status=publish --porcelain`
    Then save STDOUT as {PAGE_ID}

    When I run `wp post block count --post-type=post`
    Then STDOUT should be a table containing rows:
      | blockName      | count | posts |
      | core/paragraph | 1     | 1     |

    When I run `wp post block count --post-type=page`
    Then STDOUT should be a table containing rows:
      | blockName    | count | posts |
      | core/heading | 1     | 1     |

  @require-wp-5.0
  Scenario: Count blocks filtered by post status
    Given a WP install
    When I run `wp post create --post_title='Published' --post_content='<!-- wp:paragraph --><p>Pub</p><!-- /wp:paragraph -->' --post_status=publish --porcelain`
    Then save STDOUT as {PUB_ID}

    When I run `wp post create --post_title='Draft' --post_content='<!-- wp:heading --><h2>Draft</h2><!-- /wp:heading -->' --post_status=draft --porcelain`
    Then save STDOUT as {DRAFT_ID}

    When I run `wp post block count --post-status=draft`
    Then STDOUT should be a table containing rows:
      | blockName    | count | posts |
      | core/heading | 1     | 1     |

  # ============================================================================
  # Phase 3: Extended Test Coverage - P1 (High) Tests
  # ============================================================================

  @require-wp-5.0
  Scenario: Post with mixed block and freeform content
    Given a WP install
    And a mixed-content.txt file:
      """
      <!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->

      Some freeform text

      <!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->
      """
    When I run `wp post create --post_title='Mixed' --porcelain < mixed-content.txt`
    Then save STDOUT as {POST_ID}

    When I run `wp post has-blocks {POST_ID}`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains blocks.
      """

  @require-wp-5.0
  Scenario: Empty post has no blocks
    Given a WP install
    When I run `wp post create --post_title='Empty' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post has-blocks {POST_ID}`
    Then STDERR should contain:
      """
      does not contain blocks
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Parse deeply nested blocks
    Given a WP install
    When I run `wp post create --post_title='Deep' --post_content='<!-- wp:group --><!-- wp:columns --><!-- wp:column --><!-- wp:group --><!-- wp:paragraph --><p>Deep</p><!-- /wp:paragraph --><!-- /wp:group --><!-- /wp:column --><!-- /wp:columns --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "blockName": "core/group"
      """
    And STDOUT should contain:
      """
      "blockName": "core/columns"
      """
    And STDOUT should contain:
      """
      "blockName": "core/paragraph"
      """

  @require-wp-5.0
  Scenario: List blocks on post with no blocks
    Given a WP install
    When I run `wp post create --post_title='Classic' --post_content='<p>No blocks</p>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.0
  Scenario: Render nested blocks
    Given a WP install
    When I run `wp post create --post_title='Nested' --post_content='<!-- wp:group {"className":"test-group"} --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block render {POST_ID}`
    Then STDOUT should contain:
      """
      <p>Inner</p>
      """

  @require-wp-5.0
  Scenario: Insert self-closing block
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Before</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block insert {POST_ID} core/separator`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {POST_ID}.
      """

    When I run `wp post has-block {POST_ID} core/separator`
    Then STDOUT should contain:
      """
      Success: Post {POST_ID} contains block 'core/separator'.
      """

  @require-wp-5.0
  Scenario: Insert block into empty post
    Given a WP install
    When I run `wp post create --post_title='Empty' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block insert {POST_ID} core/paragraph --content="First block"`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {POST_ID}.
      """

    When I run `wp post block list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      1
      """

  @require-wp-5.0
  Scenario: Remove with out of bounds index
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block remove {POST_ID} --index=100`
    Then STDERR should contain:
      """
      Invalid index: 100
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Remove with negative index
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block remove {POST_ID} --index=-1`
    Then STDERR should contain:
      """
      Invalid index: -1
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Remove container block removes children
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:group --><!-- wp:paragraph --><p>Nested</p><!-- /wp:paragraph --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block remove {POST_ID} --index=0`
    Then STDOUT should contain:
      """
      Success: Removed 1 block from post {POST_ID}.
      """

    When I run `wp post block list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.0
  Scenario: Replace block preserves content
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Keep this text</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block replace {POST_ID} core/paragraph core/verse`
    Then STDOUT should contain:
      """
      Success: Replaced 1 block in post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      Keep this text
      """

  @require-wp-5.0
  Scenario: Get nested block shows inner blocks
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block get {POST_ID} 0`
    Then STDOUT should contain:
      """
      "blockName": "core/group"
      """
    And STDOUT should contain:
      """
      "innerBlocks"
      """

  @require-wp-5.0
  Scenario: Update both attrs and content
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:heading {"level":2} --><h2>Old Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block update {POST_ID} 0 --attrs='{"level":3}' --content="<h3>New Title</h3>"`
    Then STDOUT should contain:
      """
      Success: Updated block at index 0 in post {POST_ID}.
      """

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should contain:
      """
      "level": 3
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      New Title
      """

  @require-wp-5.0
  Scenario: Move in single block post
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Only</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block move {POST_ID} 0 1`
    Then STDERR should contain:
      """
      Invalid to-index: 1
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Export with --raw includes innerHTML
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block export {POST_ID} --raw`
    Then STDOUT should contain:
      """
      "innerHTML"
      """
    And STDOUT should contain:
      """
      <p>Test content</p>
      """

  @require-wp-5.0
  Scenario: Export post with no blocks
    Given a WP install
    When I run `wp post create --post_title='Classic' --post_content='<p>No blocks</p>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block export {POST_ID}`
    Then STDOUT should contain:
      """
      "blocks":
      """

  @require-wp-5.0
  Scenario: Import at specific numeric position
    Given a WP install
    And a blocks-import-pos.json file:
      """
      {"blocks":[{"blockName":"core/heading","attrs":{"level":2},"innerBlocks":[],"innerHTML":"<h2>Middle</h2>","innerContent":["<h2>Middle</h2>"]}]}
      """
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Last</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID} --file=blocks-import-pos.json --position=1`
    Then STDOUT should contain:
      """
      Success: Imported 1 block into post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /First.*Middle.*Last/s

  @require-wp-5.0
  Scenario: Clone block to specific numeric position
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Third</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    # Clone first block to position 2 (between Second and Third)
    When I run `wp post block clone {POST_ID} 0 --position=2`
    Then STDOUT should contain:
      """
      Success: Cloned block to index 2 in post {POST_ID}.
      """

    When I run `wp post block render {POST_ID}`
    Then STDOUT should match /First.*Second.*First.*Third/s

  @require-wp-5.0
  Scenario: Clone nested block preserves children
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block clone {POST_ID} 0`
    Then STDOUT should contain:
      """
      Success: Cloned block to index 1 in post {POST_ID}.
      """

    When I run `wp post block list {POST_ID} --nested`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/group     | 2     |
      | core/paragraph | 2     |

  @require-wp-5.0
  Scenario: Clone block to position before source
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block clone {POST_ID} 1 --position=before`
    Then STDOUT should contain:
      """
      Success: Cloned block to index 1 in post {POST_ID}.
      """

    When I run `wp post block list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

  @require-wp-5.0
  Scenario: Extract non-existent attribute
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block extract {POST_ID} --block=core/paragraph --attr=nonexistent --format=ids`
    Then STDOUT should be empty

  @require-wp-5.0
  Scenario: Extract from non-existent block type
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block extract {POST_ID} --block=core/image --attr=id --format=ids`
    Then STDOUT should be empty

  # ============================================================================
  # Phase 3: Extended Test Coverage - P2 (Medium) Tests
  # ============================================================================

  @require-wp-5.0
  Scenario: Parse empty post content
    Given a WP install
    When I run `wp post create --post_title='Empty' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block parse {POST_ID}`
    Then STDOUT should be:
      """
      []
      """

  @require-wp-5.0
  Scenario: List blocks in CSV and YAML formats
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block list {POST_ID} --format=csv`
    Then STDOUT should contain:
      """
      blockName,count
      """
    And STDOUT should contain:
      """
      core/paragraph,1
      """

    When I run `wp post block list {POST_ID} --format=yaml`
    Then STDOUT should contain:
      """
      blockName: core/paragraph
      """

  @require-wp-5.0
  Scenario: List with --nested counts all nesting levels
    Given a WP install
    When I run `wp post create --post_title='Deep' --post_content='<!-- wp:group --><!-- wp:group --><!-- wp:paragraph --><p>Deep</p><!-- /wp:paragraph --><!-- /wp:group --><!-- /wp:group -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block list {POST_ID} --nested`
    Then STDOUT should be a table containing rows:
      | blockName      | count |
      | core/group     | 2     |
      | core/paragraph | 1     |

  @require-wp-5.0
  Scenario: Render unknown block type
    Given a WP install
    When I run `wp post create --post_title='Unknown' --post_content='<!-- wp:fake/nonexistent --><p>Content</p><!-- /wp:fake/nonexistent -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block render {POST_ID}`
    # Unknown blocks render their innerHTML as-is
    Then STDOUT should contain:
      """
      <p>Content</p>
      """

  @require-wp-5.0
  Scenario: Insert with invalid attrs JSON
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block insert {POST_ID} core/heading --attrs='{invalid json'`
    Then STDERR should contain:
      """
      Invalid JSON
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Replace with invalid attrs JSON
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block replace {POST_ID} core/paragraph core/heading --attrs='{broken'`
    Then STDERR should contain:
      """
      Invalid JSON
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Move with negative indices
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>First</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Second</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I try `wp post block move {POST_ID} -1 0`
    Then STDERR should contain:
      """
      Invalid from-index: -1
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Count blocks in various formats
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --post_status=publish --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block count {POST_ID} --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"blockName":"core/paragraph","count":1,"posts":1}]
      """

    When I run `wp post block count {POST_ID} --format=csv`
    Then STDOUT should contain:
      """
      blockName,count,posts
      """

    When I run `wp post block count {POST_ID} --format=yaml`
    Then STDOUT should contain:
      """
      blockName: core/paragraph
      """

  @require-wp-5.0
  Scenario: Import empty blocks array
    Given a WP install
    And a empty-blocks.json file:
      """
      {"version":"1.0","blocks":[]}
      """
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Existing</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID} --file=empty-blocks.json`
    Then STDOUT should contain:
      """
      Success: Imported 0 blocks into post {POST_ID}.
      """

  @require-wp-5.0
  Scenario: Extract attribute in various formats
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:heading {"level":2} --><h2>One</h2><!-- /wp:heading --><!-- wp:heading {"level":3} --><h3>Two</h3><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block extract {POST_ID} --block=core/heading --attr=level --format=json`
    Then STDOUT should be JSON containing:
      """
      [2,3]
      """

    When I run `wp post block extract {POST_ID} --block=core/heading --attr=level --format=csv`
    Then STDOUT should contain:
      """
      level
      """

  @require-wp-5.0
  Scenario: Extract with both block and index filters
    Given a WP install
    When I run `wp post create --post_title='Test' --post_content='<!-- wp:paragraph --><p>Para</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2>Title</h2><!-- /wp:heading -->' --porcelain`
    Then save STDOUT as {POST_ID}

    # --index=1 is the heading, --block filter should match
    When I run `wp post block extract {POST_ID} --index=1 --block=core/heading --attr=level --format=ids`
    Then STDOUT should be:
      """
      2
      """

  # ============================================================================
  # Phase 3: STDIN Import Test (requires wp-cli-tests update)
  # ============================================================================

  @require-wp-5.0 @broken
  Scenario: Import blocks from STDIN
    Given a WP install
    And a blocks-stdin.json file:
      """
      {"blocks":[{"blockName":"core/paragraph","attrs":{},"innerBlocks":[],"innerHTML":"<p>From STDIN</p>","innerContent":["<p>From STDIN</p>"]}]}
      """
    When I run `wp post create --post_title='STDIN Test' --post_content='' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post block import {POST_ID}` with STDIN from 'blocks-stdin.json'
    Then STDOUT should contain:
      """
      Success: Imported 1 block into post {POST_ID}.
      """

    When I run `wp post get {POST_ID} --field=post_content`
    Then STDOUT should contain:
      """
      From STDIN
      """
