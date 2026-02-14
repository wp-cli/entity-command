Feature: Create a new site on a WP multisite

  Scenario: Respect defined `$base` in wp-config
    Given an empty directory
    And WP files
    And a database
    And a extra-config file:
      """
      define( 'WP_ALLOW_MULTISITE', true );
      define( 'MULTISITE', true );
      define( 'SUBDOMAIN_INSTALL', false );
      $base = '/dev/';
      define( 'DOMAIN_CURRENT_SITE', 'localhost' );
      define( 'PATH_CURRENT_SITE', '/dev/' );
      define( 'SITE_ID_CURRENT_SITE', 1 );
      define( 'BLOG_ID_CURRENT_SITE', 1 );
      """

    When I run `wp config create {CORE_CONFIG_SETTINGS} --skip-check --extra-php < extra-config`
    Then STDOUT should be:
      """
      Success: Generated 'wp-config.php' file.
      """

    # Old versions of WP can generate wpdb database errors if the WP tables don't exist, so STDERR may or may not be empty
    When I try `wp core multisite-install --url=localhost/dev/ --title=Test --admin_user=admin --admin_email=admin@example.org`
    Then STDOUT should contain:
      """
      Success: Network installed. Don't forget to set up rewrite rules
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                   |
      | 1       | http://localhost/dev/ |

    When I run `wp site create --slug=newsite`
    Then STDOUT should be:
      """
      Success: Site 2 created: http://localhost/dev/newsite/
      """

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                           |
      | 1       | http://localhost/dev/         |
      | 2       | http://localhost/dev/newsite/ |

  Scenario: Create new site with custom `$super_admins` global
    Given an empty directory
    And WP files
    And a database
    And a extra-config file:
      """
      define( 'WP_ALLOW_MULTISITE', true );
      define( 'MULTISITE', true );
      define( 'SUBDOMAIN_INSTALL', false );
      define( 'DOMAIN_CURRENT_SITE', 'localhost' );
      define( 'PATH_CURRENT_SITE', '/' );
      define('SITE_ID_CURRENT_SITE', 1);
      define('BLOG_ID_CURRENT_SITE', 1);

      $super_admins = array( 1 => 'admin' );
      """
    When I run `wp core config {CORE_CONFIG_SETTINGS} --skip-check --extra-php < extra-config`
    Then STDOUT should be:
      """
      Success: Generated 'wp-config.php' file.
      """

    # Old versions of WP can generate wpdb database errors if the WP tables don't exist, so STDERR may or may not be empty
    When I try `wp core multisite-install --url=localhost --title=Test --admin_user=admin --admin_email=admin@example.org`
    Then STDOUT should contain:
      """
      Success: Network installed. Don't forget to set up rewrite rules
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                   |
      | 1       | http://localhost/ |

    When I run `wp site create --slug=newsite`
    Then STDOUT should be:
      """
      Success: Site 2 created: http://localhost/newsite/
      """

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                           |
      | 1       | http://localhost/         |
      | 2       | http://localhost/newsite/ |

  Scenario: Create site with custom URL in subdomain multisite
    Given a WP multisite subdomain install

    When I run `wp site create --url=http://custom.example.com`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http://custom.example.com/
      """

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                        |
      | 1       | https://example.com/       |
      | 2       | http://custom.example.com/ |

    When I run `wp --url=custom.example.com option get home`
    Then STDOUT should be:
      """
      http://custom.example.com
      """

  Scenario: Create site with custom URL in subdirectory multisite
    Given a WP multisite subdirectory install

    When I run `wp site create --url=http://example.com/custom/path/`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http://example.com/custom/path/
      """

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                            |
      | 1       | https://example.com/           |
      | 2       | http://example.com/custom/path/ |

    When I run `wp --url=example.com/custom/path option get home`
    Then STDOUT should be:
      """
      http://example.com/custom/path
      """

  Scenario: Create site with custom URL and explicit slug
    Given a WP multisite subdomain install

    When I run `wp site create --url=http://custom.example.com --slug=myslug`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http://custom.example.com/
      """

  Scenario: Error when neither slug nor url is provided
    Given a WP multisite install

    When I try `wp site create --title="Test Site"`
    Then STDERR should be:
      """
      Error: Either --slug or --url must be provided.
      """
    And the return code should be 1

  Scenario: Error when invalid URL format is provided
    Given a WP multisite install

    When I try `wp site create --url=not-a-valid-url`
    Then STDERR should contain:
      """
      Error: Invalid URL format
      """
    And the return code should be 1

  Scenario: Preserve existing slug behavior
    Given a WP multisite subdomain install

    When I run `wp site create --slug=testsite`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http://testsite.example.com/
      """

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                          |
      | 1       | https://example.com/         |
      | 2       | http://testsite.example.com/ |
