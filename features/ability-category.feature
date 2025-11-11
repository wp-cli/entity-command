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
    When I run `wp ability category list --format=count`
    Then save STDOUT as {COUNT}

    Given a wp-content/mu-plugins/test-ability-categories.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
        wp_register_ability_category( 'test-category-1', array(
          'label' => 'First test category',
          'description' => 'First test category',
        ) );

        wp_register_ability_category( 'test-category-2', array(
          'label' => 'Second test category',
          'description' => 'Second test category',
        ) );
      } );
      """

    When I run `wp ability category list --format=count`
    Then STDOUT should not contain:
      """
      {COUNT}
      """

    When I run `wp ability category list --fields=slug,description --format=csv`
    Then STDOUT should contain:
      """
      test-category-1,"First test category"
      """
    And STDOUT should contain:
      """
      test-category-2,"Second test category"
      """

  @require-wp-6.9
  Scenario: Get a specific ability category
    Given a wp-content/mu-plugins/test-ability-categories.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
        wp_register_ability_category( 'content', array(
          'label' => 'Content category',
          'description' => 'Content category',
        ) );
      } );
      """

    When I try `wp ability category get invalid_category`
    Then STDERR should contain:
      """
      Error: Ability category invalid_category doesn't exist.
      """
    And the return code should be 1

    When I run `wp ability category get content --field=description`
    Then STDOUT should be:
      """
      Content category
      """

    When I run `wp ability category get content --field=slug`
    Then STDOUT should be:
      """
      content
      """

  @require-wp-6.9
  Scenario: Check if an ability category exists
    Given a wp-content/mu-plugins/test-ability-categories.php file:
      """
      <?php
      add_action( 'wp_abilities_api_categories_init', function() {
        wp_register_ability_category( 'existing-category', array(
          'label' => 'This category exists',
          'description' => 'This category exists',
        ) );
      } );
      """

    When I try `wp ability category exists existing-category`
    Then the return code should be 0

    When I try `wp ability category exists non_existent_category`
    Then the return code should be 1
