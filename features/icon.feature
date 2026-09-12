Feature: Manage WordPress SVG icons

  Background:
    Given a WP install

  @require-wp-7.1
  Scenario: Listing icons
    When I run `wp icon list --collection=core --format=count`
    Then STDOUT should be a number

    When I run `wp icon list --search=arrow-down --field=name`
    Then STDOUT should contain:
      """
      core/arrow-down
      """

    When I run `wp icon list --format=ids`
    Then STDOUT should contain:
      """
      core/plus
      """

  @require-wp-7.1
  Scenario: Listing icons matches names and labels
    When I run `wp icon list --search="Arrow Down" --fields=name,label,collection`
    Then STDOUT should be a table containing rows:
      | name            | label      | collection |
      | core/arrow-down | Arrow Down | core       |

  @require-wp-7.1
  Scenario: Listing icons of a non-existent collection
    When I try `wp icon list --collection=nonexistent-collection`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-7.1
  Scenario: Getting an icon
    When I run `wp icon get core/plus --fields=name,label,collection`
    Then STDOUT should be a table containing rows:
      | Field      | Value     |
      | name       | core/plus |
      | label      | Plus      |
      | collection | core      |

    When I run `wp icon get core/plus --field=content`
    Then STDOUT should contain:
      """
      <svg
      """

    When I run `wp icon get core/plus --field=file_path`
    Then STDOUT should contain:
      """
      wp-includes/images/icon-library/plus.svg
      """

  @require-wp-7.1
  Scenario: Getting a non-existent icon
    When I try `wp icon get core/nonexistent-icon`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-7.1
  Scenario: Checking whether an icon is registered
    When I try `wp icon is-registered core/nonexistent-icon`
    Then the return code should be 1

    When I run `wp icon is-registered core/plus`
    Then the return code should be 0

  @require-wp-7.1
  Scenario: Rendering an icon
    When I run `wp icon render core/plus`
    Then STDOUT should contain:
      """
      width="24"
      """
    And STDOUT should contain:
      """
      height="24"
      """
    And STDOUT should contain:
      """
      aria-hidden="true"
      """

  @require-wp-7.1
  Scenario: Rendering an icon with a size, class and label
    When I run `wp icon render core/plus --size=48 --class=my-icon --label="Add item"`
    Then STDOUT should contain:
      """
      width="48"
      """
    And STDOUT should contain:
      """
      class="my-icon"
      """
    And STDOUT should contain:
      """
      role="img"
      """
    And STDOUT should contain:
      """
      aria-label="Add item"
      """
    And STDOUT should not contain:
      """
      aria-hidden
      """

  @require-wp-7.1
  Scenario: Rendering an icon at its intrinsic size
    When I run `wp icon render core/plus --size=none`
    Then STDOUT should contain:
      """
      <svg
      """
    And STDOUT should not contain:
      """
      width=
      """

  @require-wp-7.1
  Scenario: Rendering an icon with an invalid size
    When I try `wp icon render core/plus --size=huge`
    Then the return code should be 1
    And STDERR should contain:
      """
      The --size argument must be a positive integer or "none".
      """

  @require-wp-7.1
  Scenario: Rendering a non-existent icon
    When I try `wp icon render core/nonexistent-icon`
    Then the return code should be 1
    And STDERR should contain:
      """
      doesn't exist
      """

  @require-wp-7.1
  Scenario: Managing icons registered by a plugin
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

    When I run `wp icon list --collection=test-icons --fields=name,label,collection`
    Then STDOUT should be a table containing rows:
      | name              | label  | collection |
      | test-icons/square | Square | test-icons |

    When I run `wp icon get test-icons/square --field=content`
    Then STDOUT should contain:
      """
      M4 4h16v16H4z
      """

    When I run `wp icon get test-icons/square --field=file_path`
    Then STDOUT should not contain:
      """
      .svg
      """

    When I run `wp icon render test-icons/square --size=16`
    Then STDOUT should contain:
      """
      width="16"
      """

  @less-than-wp-7.1
  Scenario: Icon commands fail on WordPress < 7.1
    When I try `wp icon list`
    Then the return code should be 1
    And STDERR should contain:
      """
      Requires WordPress 7.1 or greater
      """
