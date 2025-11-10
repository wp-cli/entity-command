Feature: Manage WordPress ability categories

  Background:
    Given a WP install

  @less-than-wp-6.9
  Scenario: Ability category commands require WordPress 6.9+
    When I try `wp ability category list`
    Then STDERR should contain:
      """
      Error: Requires WordPress 6.9 or greater.
      """
    And the return code should be 1

  @require-wp-6.9
  Scenario: List ability categories
    Given a wp-content/mu-plugins/test-ability-categories.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
          return;
        }
        
        wp_register_ability_category( 'test_category_1', array(
          'description' => 'First test category',
        ) );
        
        wp_register_ability_category( 'test_category_2', array(
          'description' => 'Second test category',
        ) );
      } );
      """

    When I run `wp ability category list --format=count`
    Then STDOUT should contain:
      """
      2
      """

    When I run `wp ability category list --fields=name,description --format=csv`
    Then STDOUT should contain:
      """
      test_category_1,"First test category"
      """
    And STDOUT should contain:
      """
      test_category_2,"Second test category"
      """

  @require-wp-6.9
  Scenario: Get a specific ability category
    Given a wp-content/mu-plugins/test-ability-categories.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
          return;
        }
        
        wp_register_ability_category( 'content_ops', array(
          'description' => 'Content operations category',
        ) );
      } );
      """

    When I try `wp ability category get invalid_category`
    Then STDERR should contain:
      """
      Error: Ability category invalid_category doesn't exist.
      """
    And the return code should be 1

    When I run `wp ability category get content_ops --field=description`
    Then STDOUT should be:
      """
      Content operations category
      """

    When I run `wp ability category get content_ops --field=name`
    Then STDOUT should be:
      """
      content_ops
      """

  @require-wp-6.9
  Scenario: Check if an ability category exists
    Given a wp-content/mu-plugins/test-ability-categories.php file:
      """
      <?php
      add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
          return;
        }
        
        wp_register_ability_category( 'existing_category', array(
          'description' => 'This category exists',
        ) );
      } );
      """

    When I try `wp ability category exists existing_category`
    Then the return code should be 0

    When I try `wp ability category exists non_existent_category`
    Then the return code should be 1
