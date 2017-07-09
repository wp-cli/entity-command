Feature: Manage post custom fields

  Scenario: Postmeta CRUD
    Given a WP install

    When I run `wp post-meta add 1 foo 'bar'`
    Then STDOUT should not be empty

    When I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      bar
      """

    When I try `wp post meta get 999999 foo`
    Then STDERR should be:
      """
      Error: Could not find the post with ID 999999.
      """

    When I run `wp post-meta set 1 foo '[ "1", "2" ]' --format=json`
    Then STDOUT should not be empty

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Value passed for custom field 'foo' is unchanged.
      """

    When I run `wp post-meta get 1 foo --format=json`
    Then STDOUT should be:
      """
      ["1","2"]
      """

    When I run `echo 'via STDIN' | wp post-meta set 1 foo`
    And I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      via STDIN
      """

    When I run `wp post-meta delete 1 foo`
    Then STDOUT should not be empty

    When I try `wp post-meta get 1 foo`
    Then the return code should be 1

  Scenario: List post meta
    Given a WP install

    When I run `wp post meta add 1 apple banana`
    And I run `wp post meta add 1 apple banana`
    Then STDOUT should not be empty

    When I run `wp post meta set 1 banana '["apple", "apple"]' --format=json`
    Then STDOUT should not be empty

    When I run `wp post meta list 1`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |
      | 1       | banana   | ["apple","apple"]  |

  Scenario: Delete all post meta
    Given a WP install

    When I run `wp post meta add 1 apple banana`
    And I run `wp post meta add 1 _foo banana`
    Then STDOUT should not be empty

    When I run `wp post meta list 1 --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I try `wp post meta delete 1`
    Then STDERR should be:
      """
      Error: Please specify a meta key, or use the --all flag.
      """

    When I run `wp post meta delete 1 --all`
    Then STDOUT should contain:
      """
      Deleted 'apple' custom field.
      Deleted '_foo' custom field.
      Success: Deleted all custom fields.
      """

    When I run `wp post meta list 1 --format=count`
    Then STDOUT should be:
      """
      0
      """

  Scenario: List post meta with a null value
    Given a WP install
    And a setup.php file:
      """
      <?php
      update_post_meta( 1, 'foo', NULL );
      """
    And I run `wp eval-file setup.php`

    When I run `wp post meta list 1`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | foo      |                    |

  Scenario: Make sure WordPress receives the slashed data it expects in meta fields
    Given a WP install

    When I run `wp post-meta add 1 foo 'My\Meta'`
    Then STDOUT should not be empty

    When I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      My\Meta
      """

    When I run `wp post-meta update 1 foo 'My\New\Meta'`
    Then STDOUT should be:
      """
      Success: Updated custom field 'foo'.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Value passed for custom field 'foo' is unchanged.
      """

    When I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      My\New\Meta
      """

  @pluck
  Scenario: Multi-dimensional values can be plucked.
    Given a WP install
    And an input.json file:
      """
      {
        "foo": "bar"
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta pluck 1 meta-key foo`
    Then STDOUT should be:
      """
      bar
      """

  @pluck @pluck-deep
  Scenario: Multi-dimensional values can be plucked at any depth.
    Given a WP install
    And an input.json file:
      """
      {
        "foo": {
          "bar": {
            "baz": "some value"
          }
        },
        "foo.com": {
          "visitors": 999
        }
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta pluck 1 meta-key foo bar baz`
    Then STDOUT should be:
      """
      some value
      """

    When I run `wp post meta pluck 1 meta-key foo.com visitors`
    Then STDOUT should be:
      """
      999
      """

  @pluck @pluck-fail
  Scenario: The command fails when attempting to pluck a non-existent nested value.
    Given a WP install
    And I run `wp post meta set 1 meta-key '{ "key": "value" }' --format=json`

    When I run `wp post meta pluck 1 meta-key key`
    Then STDOUT should be:
      """
      value
      """

    When I try `wp post meta pluck 1 meta-key foo`
    Then STDOUT should be empty
    And the return code should be 1

  @patch
  Scenario: Multi-dimensional values can be patched.
    Given a WP install
    And an input.json file:
      """
      {
        "foo": "bar"
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta patch 1 meta-key foo baz`
    Then STDOUT should be:
      """
      Success: Updated custom field 'meta-key'.
      """

    When I run `wp post meta get 1 meta-key`
    Then STDOUT should be:
      """
      array (
        'foo' => 'baz',
      )
      """