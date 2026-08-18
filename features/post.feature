Feature: Manage WordPress posts

  Background:
    Given a WP install

  Scenario: Creating/updating/deleting posts
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post create --post_title='Test post' --post_type="test" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CUSTOM_POST_ID}

    When I run `wp post exists {CUSTOM_POST_ID}`
    Then STDOUT should be:
      """
      Success: Post with ID {CUSTOM_POST_ID} exists.
      """
    And the return code should be 0

    When I try `wp post exists 1000`
    Then STDOUT should be empty
    And the return code should be 1

    When I run `wp post update {POST_ID} --post_title='Updated post'`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post delete {POST_ID}`
    Then STDOUT should be:
      """
      Success: Trashed post {POST_ID}.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Deleted post {POST_ID}.
      """

    When I run `wp post delete {CUSTOM_POST_ID}`
    Then STDOUT should be:
      """
      Success: Trashed post {CUSTOM_POST_ID}.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Deleted post {CUSTOM_POST_ID}.
      """

  Scenario: Force-deleting a custom post type post skips trash
    When I run `wp post create --post_title='Test CPT post' --post_type='book' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {BOOK_POST_ID}

    When I run `wp post delete {BOOK_POST_ID} --force`
    Then STDOUT should be:
      """
      Success: Deleted post {BOOK_POST_ID}.
      """

    When I try the previous command again
    Then the return code should be 1

  Scenario: Deleting already trashed custom post type posts
    When I run `wp post create --post_title='Test CPT post' --post_type='book' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {BOOK_POST_ID}

    When I run `wp post update {BOOK_POST_ID} --post_status='trash'`
    Then STDOUT should be:
      """
      Success: Updated post {BOOK_POST_ID}.
      """

    When I run `wp post delete {BOOK_POST_ID}`
    Then STDOUT should be:
      """
      Success: Deleted post {BOOK_POST_ID}.
      """

  Scenario: Updating an invalid post should exit with an error
    Given a WP install

    When I try `wp post update 22 --post_title=Foo`
    Then the return code should be 1
    And STDERR should contain:
      """
      Warning: Invalid post ID.
      """

  Scenario: Setting post categories
    When I run `wp term create category "First Category" --porcelain`
    Then save STDOUT as {TERM_ID}

    When I run `wp term create category "Second Category" --porcelain`
    Then save STDOUT as {SECOND_TERM_ID}

    When I run `wp post create --post_title="Test category" --post_category="First Category" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      {TERM_ID}
      """

    When I run `wp post update {POST_ID} --post_category={SECOND_TERM_ID}`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      {SECOND_TERM_ID}
      """

    When I run `wp post update {POST_ID} --post_category='Uncategorized,{TERM_ID},Second Category'`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    And save STDOUT as {MULTI_CATEGORIES_STDOUT}
    Then STDOUT should contain:
      """
      {TERM_ID}
      """
    And STDOUT should contain:
      """
      {SECOND_TERM_ID}
      """
    And STDOUT should contain:
      """
      1
      """

    # Blank categories with non-blank ignored.
    When I run `wp post update {POST_ID} --post_category='Uncategorized, ,{TERM_ID},Second Category,'`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      {MULTI_CATEGORIES_STDOUT}
      """

    # Zero category same as default Uncategorized (1) category.
    When I try `wp post update {POST_ID} --post_category=0`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      1
      """

    # Blank category/categories same as default Uncategorized (1) category.
    When I try `wp post update {POST_ID} --post_category=,`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      1
      """

    # Null category same as no categories.
    When I try `wp post update {POST_ID} --post_category=' '`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be empty

    # Non-existent category.
    When I try `wp post update {POST_ID} --post_category=test`
    Then STDERR should be:
      """
      Error: No such post category 'test'.
      """

    When I try `wp post create --post_title="Non-existent Category" --post_category={SECOND_TERM_ID},Test --porcelain`
    Then STDERR should be:
      """
      Error: No such post category 'Test'.
      """

    # Error on first non-existent category found.
    When I try `wp post create --post_title="More than one non-existent Category" --post_category={SECOND_TERM_ID},Test,Bad --porcelain`
    Then STDERR should be:
      """
      Error: No such post category 'Test'.
      """

  Scenario: Creating/getting/editing posts
    Given a content.html file:
      """
      This is some content.

      <script>
      alert('This should not be stripped.');
      </script>
      """
    And a create-post.sh file:
      """
      cat content.html | wp post create --post_title="Test post" --post_excerpt="A multiline
      excerpt" --porcelain -
      """

    When I run `bash create-post.sh`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post get --field=excerpt {POST_ID}`
    Then STDOUT should be:
      """
      A multiline
      excerpt
      """

    When I run `wp post get --field=content {POST_ID} | diff -Bu content.html -`
    Then STDOUT should be empty

    When I run `wp post get --format=table {POST_ID}`
    Then STDOUT should be a table containing rows:
      | Field      | Value     |
      | ID         | {POST_ID} |
      | post_title | Test post |
      | post_name  |           |
      | post_type  | post      |

    When I run `wp post get {POST_ID} --format=csv --fields=post_title,type | wc -l | tr -d ' '`
    Then STDOUT should be:
      """
      3
      """

    When I run `wp post get --format=json {POST_ID}`
    Then STDOUT should be JSON containing:
      """
      {
        "ID": {POST_ID},
        "post_title": "Test post"
      }
      """

    When I try `EDITOR="ex -i NONE -c q!" wp post edit {POST_ID}`
    Then STDERR should contain:
      """
      No change made to post content.
      """
    And the return code should be 0

    When I run `EDITOR="ex -i NONE -c %s/content/bunkum -c wq" wp post edit {POST_ID}`
    Then STDERR should be empty
    And STDOUT should contain:
      """
      Updated post {POST_ID}.
      """

    When I run `wp post get --field=content {POST_ID}`
    Then STDOUT should contain:
      """
      This is some bunkum.
      """

    When I run `wp post list --post__in=1,{POST_ID} --post_type=any --orderby=post__in --field=url`
    Then STDOUT should be:
      """
      https://example.com/?p=1
      https://example.com/?p={POST_ID}
      """

    When I run `wp post get 1 --field=url`
    Then STDOUT should be:
      """
      https://example.com/?p=1
      """

  Scenario: Update a post from file or STDIN
    Given a content.html file:
      """
      Oh glorious CLI
      """
    And a content-2.html file:
      """
      Let it be the weekend
      """

    When I run `wp post create --post_title="Testing update via STDIN" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `cat content.html | wp post update {POST_ID} -`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}
      """

    When I run `wp post get --field=post_content {POST_ID}`
    Then STDOUT should be:
      """
      Oh glorious CLI
      """

    When I run `wp post create --post_title="Testing update via STDIN. Again!" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID_TWO}

    When I run `wp post update {POST_ID} {POST_ID_TWO} content-2.html`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID_TWO}
      """

    When I run `wp post get --field=post_content {POST_ID_TWO}`
    Then STDOUT should be:
      """
      Let it be the weekend
      """

    When I try `wp post update {POST_ID} invalid-file.html`
    Then STDERR should be:
      """
      Error: Unable to read content from 'invalid-file.html'.
      """
    And the return code should be 1

  Scenario: Creating/listing posts
    When I run `wp post create --post_title='Publish post' --post_content='Publish post content' --post_status='publish' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post create --post_title='Draft post' --post_content='Draft post content' --post_status='draft' --porcelain`
    Then STDOUT should be a number

    When I run `wp post list --post_type='post' --fields=post_title,post_name,post_status --format=csv`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Publish post | publish-post | publish      |
      | Draft post   |              | draft        |

    When I run `wp post list --post_type='post' --fields=title,name,status --format=csv`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Publish post | publish-post | publish      |
      | Draft post   |              | draft        |

    When I run `wp post list --post_type='post' --fields="title, name, status" --format=csv`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Publish post | publish-post | publish      |
      | Draft post   |              | draft        |

    When I run `wp post list --post__in={POST_ID} --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_type='page' --field=title`
    Then STDOUT should contain:
      """
      Sample Page
      """

    When I run `wp post list --post_type=any --fields=post_title,post_name,post_status --format=csv --orderby=post_title --order=ASC`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Draft post   |              | draft        |
      | Hello world! | hello-world  | publish      |
      | Publish post | publish-post | publish      |
      | Sample Page  | sample-page  | publish      |

  Scenario: List posts with date query
    When I run `wp post create --post_title='old post' --post_date='2023-01-24T09:52:00.000Z'`
    And I run `wp post create --post_title='new post' --post_date='2025-01-24T09:52:00.000Z'`
    And I run `wp post list --field=post_title --date_query='{"before":{"year":"2024"}}'`
    Then STDOUT should contain:
      """
      old post
      """
    And STDOUT should not contain:
      """
      new post
      """

  Scenario: List posts with tax query
    When I run `wp term create category "First Category" --porcelain`
    And I run `wp term create category "Second Category" --porcelain`
    And I run `wp post create --post_title='post-1' --post_category="First Category"`
    And I run `wp post create --post_title='post-2' --post_category="Second Category"`
    And I run `wp post create --post_title='new post' --post_date='2025-01-24T09:52:00.000Z'`
    And I run `wp post list --field=post_title --tax_query='[{"taxonomy":"category","field":"slug","terms":"first-category"}]'`
    Then STDOUT should contain:
      """
      post-1
      """
    And STDOUT should not contain:
      """
      post-2
      """

  Scenario: Creating/updating posts with taxonomies
    When I run `wp term create category "First Category" --porcelain`
    And save STDOUT as {CAT_1}
    And I run `wp term create category "Second Category" --porcelain`
    And save STDOUT as {CAT_2}
    And I run `wp term create post_tag "Term One" --porcelain`
    And I run `wp term create post_tag "Term Two" --porcelain`
    And I run `wp post create --post_title='Test Post' --post_content='Test post content' --tax_input='{"category":[{CAT_1},{CAT_2}],"post_tag":["term-one", "term-two"]}' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term list {POST_ID} category post_tag --format=table --fields=name,taxonomy`
    Then STDOUT should be a table containing rows:
      | name            | taxonomy |
      | First Category  | category |
      | Second Category | category |
      | Term One        | post_tag |
      | Term Two        | post_tag |
    When I run `wp post update {POST_ID} --tax_input='{"category":[{CAT_1}],"post_tag":["term-one"]}'`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category post_tag --format=table --fields=name,taxonomy`
    Then STDOUT should be a table containing rows:
      | name           | taxonomy |
      | First Category | category |
      | Term One       | post_tag |

  Scenario: Update categories on a post
    When I run `wp term create category "Test Category" --porcelain`
    Then save STDOUT as {TERM_ID}

    When I run `wp post update 1 --post_category={TERM_ID}`
    And I run `wp post term list 1 category --format=json --fields=name`
    Then STDOUT should be:
      """
      [{"name":"Test Category"}]
      """

  Scenario: Make sure WordPress receives the slashed data it expects
    When I run `wp post create --post_title='My\Post' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --field=title`
    Then STDOUT should be:
      """
      My\Post
      """

    When I run `wp post update {POST_ID} --post_content="var isEmailValid = /^\S+@\S+.\S+$/.test(email);"`
    Then STDOUT should not be empty

    When I run `wp post get {POST_ID} --field=content`
    Then STDOUT should be:
      """
      var isEmailValid = /^\S+@\S+.\S+$/.test(email);
      """

  @require-wp-4.4
  Scenario: Creating/updating posts with meta keys
    When I run `wp post create --post_title='Test Post' --post_content='Test post content' --meta_input='{"key1":"value1","key2":"value2"}' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post meta list {POST_ID} --format=table`
    Then STDOUT should be a table containing rows:
      | post_id   | meta_key | meta_value |
      | {POST_ID} | key1     | value1     |
      | {POST_ID} | key2     | value2     |

    When I run `wp post update {POST_ID} --meta_input='{"key2":"value2b","key3":"value3"}'`
    And I run `wp post meta list {POST_ID} --format=table`
    Then STDOUT should be a table containing rows:
      | post_id   | meta_key | meta_value |
      | {POST_ID} | key1     | value1     |
      | {POST_ID} | key2     | value2b    |
      | {POST_ID} | key3     | value3     |

    When I run `wp post list --field=post_title --meta_query='[{"key":"key2","value":"value2b"}]'`
    Then STDOUT should contain:
      """
      Test Post
      """

  Scenario: Publishing a post and setting a date fails if the edit_date flag is not passed.
    Given a WP install

    When I run `wp post create --post_title='test' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_date='2005-01-24T09:52:00.000Z' --post_status='publish'`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp post get {POST_ID} --field=post_date`
    Then STDOUT should not contain:
      """
      2005-01-24 09:52:00
      """

  Scenario: Publishing a post and setting a date succeeds if the edit_date flag is passed.
    Given a WP install

    When I run `wp post create --post_title='test' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_date='2005-01-24T09:52:00.000Z' --post_status='publish' --edit_date=1`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp post get {POST_ID} --field=post_date`
    Then STDOUT should contain:
      """
      2005-01-24 09:52:00
      """

  @require-wp-5.0
  Scenario: Get block_version field for post with blocks
    Given a block-post.html file:
      """
      <!-- wp:paragraph --><p>Hello block world</p><!-- /wp:paragraph -->
      """
    When I run `wp post create block-post.html --post_title='Block Post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --field=block_version`
    Then STDOUT should be:
      """
      1
      """

  @require-wp-5.0
  Scenario: Get block_version field for post without blocks
    Given a classic-post.html file:
      """
      <p>Just plain HTML</p>
      """
    When I run `wp post create classic-post.html --post_title='Classic Post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --field=block_version`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.0
  Scenario: Get block_version field included in default output
    Given a heading-post.html file:
      """
      <!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->
      """
    When I run `wp post create heading-post.html --post_title='Test Post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --format=json`
    Then STDOUT should be JSON containing:
      """
      {"block_version":1}
      """

  @require-wp-4.4
  Scenario: Filtering by the wp_posts column names
    When I run `wp post create --post_title='Alpha' --post_status=publish --porcelain`
    Then STDOUT should be a number

    # 'title', 'name' and 'author' are WP_Query's names for these filters. The
    # columns they filter on are spelled differently, and passing the column
    # name used to reach WP_Query as an argument it does not know: it was
    # dropped, and every post came back. They are aliases now.
    When I run `wp post list --title='Hello world!' --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_title='Hello world!' --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --name=alpha --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_name=alpha --format=count`
    Then STDOUT should be:
      """
      1
      """

    # Only the bundled post matches: a post created by WP-CLI has author 0,
    # because WP-CLI runs as no user unless told otherwise.
    When I run `wp post list --author=1 --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_author=1 --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --p=1 --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --ID=1 --format=count`
    Then STDOUT should be:
      """
      1
      """

  Scenario: Filtering drafts by slug needs a user
    When I run `wp post create --post_title='Beta' --post_name=beta --post_status=draft --porcelain`
    Then STDOUT should be a number

    # '--name' makes this a single-post query, and WP_Query hands a draft from
    # one of those only to a user who can edit it. WP-CLI is no user by default.
    When I run `wp post list --name=beta --field=ID`
    Then STDOUT should be empty

    # The global '--user' argument is what makes it reachable.
    When I run `wp post list --name=beta --user=1 --field=ID`
    Then STDOUT should not be empty

  Scenario: Trashed posts need their status named
    When I run `wp post create --post_title='Doomed' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {DOOMED_ID}

    When I run `wp post delete {DOOMED_ID}`
    Then STDOUT should contain:
      """
      Success: Trashed post
      """

    # This command defaults post_status to 'any', and WP_Query reads 'any' as
    # every status registered without 'exclude_from_search' - which leaves out
    # 'trash' and 'auto-draft'.
    When I run `wp post list --field=ID`
    Then STDOUT should not contain:
      """
      {DOOMED_ID}
      """

    When I run `wp post list --post_status=trash --field=ID`
    Then STDOUT should contain:
      """
      {DOOMED_ID}
      """

  Scenario: Filtering by category and tag
    When I run `wp term create category 'Alpha Cat' --porcelain`
    Then save STDOUT as {CAT_ID}

    When I run `wp term create post_tag 'Alpha Tag' --slug=alpha-tag --porcelain`
    Then save STDOUT as {TAG_ID}

    When I run `wp post create --post_title='Tagged' --post_status=publish --post_category={CAT_ID} --tags_input=alpha-tag --porcelain`
    Then STDOUT should be a number

    When I run `wp post list --cat={CAT_ID} --format=count`
    Then STDOUT should be:
      """
      1
      """

    # A negative ID excludes that category rather than selecting it.
    When I run `wp post list --cat=-{CAT_ID} --field=post_title`
    Then STDOUT should not contain:
      """
      Tagged
      """

    When I run `wp post list --tag=alpha-tag --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --category_name=alpha-cat --format=count`
    Then STDOUT should be:
      """
      1
      """

  Scenario: Filtering by meta, time of day and order
    When I run `wp post create --post_title='Timed' --post_status=publish --post_date='2020-03-04 05:06:07' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TIMED_ID}

    When I run `wp post meta add {TIMED_ID} color blue`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Locked' --post_status=publish --post_password=secret --porcelain`
    Then STDOUT should be a number

    # Time of day, alongside the year/month/day filters already documented.
    # Two units at once exercises the combined path; all three of hour, minute
    # and second together is left out on purpose, because that path compares a
    # DATE_FORMAT() string and the SQLite integration plugin does not emulate
    # it the way MySQL does, so it matches nothing there.
    When I run `wp post list --hour=5 --minute=6 --field=post_title`
    Then STDOUT should be:
      """
      Timed
      """

    When I run `wp post list --second=7 --field=post_title`
    Then STDOUT should be:
      """
      Timed
      """

    # A meta key on its own, then narrowed by its value.
    When I run `wp post list --meta_key=color --field=post_title`
    Then STDOUT should be:
      """
      Timed
      """

    When I run `wp post list --meta_key=color --meta_value=red --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp post list --orderby=title --order=ASC --field=post_title`
    Then STDOUT should be:
      """
      Hello world!
      Locked
      Timed
      """

    When I run `wp post list --orderby=title --order=DESC --field=post_title`
    Then STDOUT should be:
      """
      Timed
      Locked
      Hello world!
      """

  @require-wp-4.4
  Scenario: Filtering by lists and JSON queries
    When I run `wp post create --post_title='Zebra Title' --post_content='nothing' --post_status=publish --porcelain`
    Then save STDOUT as {TITLED}

    When I run `wp post create --post_title='Plain' --post_name=plain --post_content='zebra in body' --post_status=publish --porcelain`
    Then save STDOUT as {BODIED}

    When I run `wp post meta add {TITLED} rank 5`
    Then STDOUT should not be empty

    # Arguments carrying '__' are split on commas before they reach WP_Query,
    # which is what makes them usable from the command line at all.
    When I run `wp post list --post__in={TITLED},{BODIED} --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp post list --post__not_in={TITLED},{BODIED} --format=count`
    Then STDOUT should be:
      """
      1
      """

    # Unlike --name, this reaches a draft, because it is not a single-post query.
    When I run `wp post list --post_name__in=plain --field=post_title`
    Then STDOUT should be:
      """
      Plain
      """

    When I run `wp post list --s=zebra --format=count`
    Then STDOUT should be:
      """
      2
      """

    # The nested queries are given as JSON, which this command decodes.
    When I run `wp post list --meta_query='[{"key":"rank","value":"5"}]' --field=post_title`
    Then STDOUT should be:
      """
      Zebra Title
      """

    When I run `wp post list --meta_key=rank --meta_value=4 --meta_compare=">" --field=post_title`
    Then STDOUT should be:
      """
      Zebra Title
      """

    # --offset only means anything once --posts_per_page is bounded.
    When I run `wp post list --posts_per_page=10 --offset=1 --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp post list --posts_per_page=1 --nopaging=1 --format=count`
    Then STDOUT should be:
      """
      3
      """

  # 'search_columns' is a WP_Query argument as of WordPress 6.2. Before that it
  # is not recognised, so '--s' searches every column and the narrowing here
  # would not happen.
  @require-wp-6.2
  Scenario: Narrowing a search to particular columns
    When I run `wp post create --post_title='Zebra Title' --post_content='nothing' --post_status=publish --porcelain`
    Then STDOUT should be a number

    When I run `wp post create --post_title='Plain' --post_content='zebra in body' --post_status=publish --porcelain`
    Then STDOUT should be a number

    When I run `wp post list --s=zebra --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp post list --s=zebra --search_columns=post_title --field=post_title`
    Then STDOUT should be:
      """
      Zebra Title
      """

    When I run `wp post list --s=zebra --search_columns=post_content --field=post_title`
    Then STDOUT should be:
      """
      Plain
      """

    When I run `wp post list --s=zebra --search_columns=post_title,post_content --format=count`
    Then STDOUT should be:
      """
      2
      """
