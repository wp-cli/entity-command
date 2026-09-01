Feature: Manage WordPress icon collections

  Background:
    Given a WP install

  @require-wp-7.1
  Scenario: Listing icon collections
    When I run `wp icon collection list --fields=slug,label,description`
    Then STDOUT should be a table containing rows:
      | slug | label     | description              |
      | core | WordPress | Default icon collection. |

  @require-wp-7.1
  Scenario: Listing icon collections in other formats
    When I run `wp icon collection list --format=ids`
    Then STDOUT should contain:
      """
      core
      """

    When I run `wp icon collection list --fields=slug,label --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"slug":"core","label":"WordPress"}]
      """

    When I run `wp icon collection list --format=count`
    Then STDOUT should be a number

  @require-wp-7.1
  Scenario: Getting an icon collection
    When I run `wp icon collection get core --fields=slug,label,description`
    Then STDOUT should be a table containing rows:
      | Field       | Value                    |
      | slug        | core                     |
      | label       | WordPress                |
      | description | Default icon collection. |

    When I run `wp icon collection get core --field=label`
    Then STDOUT should be:
      """
      WordPress
      """

  @require-wp-7.1
  Scenario: Counting the icons of a collection
    When I run `wp icon collection get core --field=count`
    Then STDOUT should be a number

  @require-wp-7.1
  Scenario: Getting a non-existent icon collection
    When I try `wp icon collection get nonexistent-collection`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-7.1
  Scenario: Checking whether an icon collection is registered
    When I try `wp icon collection is-registered nonexistent-collection`
    Then the return code should be 1

    When I run `wp icon collection is-registered core`
    Then the return code should be 0

  @require-wp-7.1
  Scenario: Listing a collection registered by a plugin
    Given a wp-content/mu-plugins/test-icons.php file:
      """
      <?php
      // Plugin Name: Test Icons
      add_action(
          'init',
          function () {
              wp_register_icon_collection(
                  'test-icons',
                  array(
                      'label'       => 'Test Icons',
                      'description' => 'A collection for testing.',
                  )
              );

              wp_register_icon(
                  'test-icons/square',
                  array(
                      'label'   => 'Square',
                      'content' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4h16v16H4z" /></svg>',
                  )
              );
          }
      );
      """

    When I run `wp icon collection list --fields=slug,label,description,count`
    Then STDOUT should be a table containing rows:
      | slug       | label      | description              | count |
      | test-icons | Test Icons | A collection for testing. | 1     |

    When I run `wp icon collection is-registered test-icons`
    Then the return code should be 0

  @less-than-wp-7.1
  Scenario: Icon collection commands fail on WordPress < 7.1
    When I try `wp icon collection list`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 7.1 or greater
      """
