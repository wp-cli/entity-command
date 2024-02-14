Feature: Manage sites in a multisite installation

  Scenario: Create a site
    Given a WP multisite install

    When I try `wp site create --slug=first --network_id=1000`
    Then STDERR should contain:
      """
      Network with id 1000 does not exist.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Create a subdomain site
    Given a WP multisite subdomain install

    When I run `wp site create --slug=first`
    Then STDOUT should not be empty

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | https://example.com/       |
      | 2       | http://first.example.com/ |

    When I run `wp site list --format=ids`
    Then STDOUT should be:
      """
      1 2
      """

    When I run `wp site list --site_id=2 --format=ids`
    Then STDOUT should be empty

    When I run `wp --url=first.example.com option get home`
    Then STDOUT should be:
      """
      http://first.example.com
      """

  Scenario: Delete a site by id
    Given a WP multisite subdirectory install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}
    And I run `wp site list --site__in={SITE_ID} --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SCHEME}

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                           |
      | 1       | https://example.com/          |
      | 2       | {SCHEME}://example.com/first/ |

    When I run `wp site list --field=url`
    Then STDOUT should be:
      """
      https://example.com/
      {SCHEME}://example.com/first/
      """

    When I try `wp site delete 1`
    Then STDERR should be:
      """
      Error: You cannot delete the root site.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp site delete {SITE_ID} --yes`
    Then STDOUT should be:
      """
      Success: The site at '{SCHEME}://example.com/first/' was deleted.
      """

    When I try the previous command again
    Then the return code should be 1

  Scenario: Filter site list
    Given a WP multisite install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}
    And I run `wp site list --site__in={SITE_ID} --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SCHEME}

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                           |
      | 1       | https://example.com/          |
      | 2       | {SCHEME}://example.com/first/ |

    When I run `wp site list --field=url --blog_id=2`
    Then STDOUT should be:
      """
      {SCHEME}://example.com/first/
      """

  Scenario: Filter site list by user
    Given a WP multisite install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}
    And I run `wp site list --blog_id={SITE_ID} --field=url`
    And save STDOUT as {SITE_URL}
    And I run `wp user create newuser newuser@example.com --porcelain --url={SITE_URL}`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}
    And I run `wp user get {USER_ID} --field=user_login`
    And save STDOUT as {USER_LOGIN}

    When I run `wp site list --field=url --site_user={USER_LOGIN}`
    Then STDOUT should be:
      """
      {SITE_URL}
      """

    When I try `wp site list --site_user=invalid_user`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Invalid user ID, email or login: 'invalid_user'
      """

    When I run `wp user remove-role {USER_LOGIN} --url={SITE_URL}`
    Then STDOUT should contain:
      """
      Success: Removed
      """

    When I run `wp site list --field=url --site_user={USER_LOGIN}`
    Then STDOUT should be empty


  Scenario: Delete a site by slug
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/first/
      """

    When I run `wp site delete --slug=first --yes`
    Then STDOUT should contain:
      """
      ://example.com/first/' was deleted.
      """

    When I try the previous command again
    Then the return code should be 1

    When I run `wp site create --slug=42`
    Then STDOUT should contain:
      """
      Success: Site 3 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/42/
      """

    When I run `wp site delete --slug=42 --yes`
    Then STDOUT should contain:
      """
      ://example.com/42/' was deleted.
      """

    When I try the previous command again
    Then STDERR should contain:
      """
      Error: Could not find site with slug '42'.
      """
    And the return code should be 1

  Scenario: Archive a site by a numeric slug
    Given a WP multisite install

    When I run `wp site create --slug=42`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/42/
      """

    When I run `wp site archive --slug=42`
    Then STDOUT should contain:
      """
      Success: Site 2 archived.
      """

    When I try `wp site archive --slug=43`
    Then STDERR should contain:
      """
      Error: Could not find site with slug '43'.
      """
    And the return code should be 1

  Scenario: Get site info
    Given a WP multisite install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}
    And I run `wp site list --site__in={SITE_ID} --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SCHEME}

    When I run `wp site url {SITE_ID}`
    Then STDOUT should be:
      """
      {SCHEME}://example.com/first/
      """

    When I run `wp site create --slug=second --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SECOND_ID}
    And I run `wp site list --site__in={SECOND_ID} --field=url | sed -e's,^\(.*\)://.*,\1,g'`
    And save STDOUT as {SECOND_SCHEME}

    When I run `wp site url {SECOND_ID} {SITE_ID}`
    Then STDOUT should be:
      """
      {SECOND_SCHEME}://example.com/second/
      {SCHEME}://example.com/first/
      """

  Scenario: Not providing a site ID or slug when running an update blog status command should throw an error
    Given a WP multisite install

    When I try `wp site private`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more IDs of sites, or pass the slug for a single site using --slug.
      """
    And STDOUT should be empty

  Scenario: Site IDs or a slug can be provided, but not both.
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`

    When I try `wp site private 1 --slug=first`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more IDs of sites, or pass the slug for a single site using --slug.
      """

  Scenario: Errors for an invalid slug
    Given a WP multisite install

    When I try `wp site private --slug=first`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Could not find site with slug 'first'.
      """

  Scenario: Archive/unarchive a site
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site archive {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} archived.
      """

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id      | archived |
      | {FIRST_SITE} | 1        |

    When I try `wp site archive {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already archived.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} archived.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id      | archived |
      | {FIRST_SITE} | 1        |

    When I run `wp site unarchive {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} unarchived.
      """

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id      | archived |
      | {FIRST_SITE} | 0        |

    When I try `wp site archive 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Activate/deactivate a site
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site deactivate {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} deactivated.
      """

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id      | deleted |
      | {FIRST_SITE} | 1       |

    When I try `wp site deactivate {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already deactivated.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} deactivated.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id      | deleted |
      | {FIRST_SITE} | 1       |

    When I run `wp site activate {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} activated.
      """

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id      | deleted |
      | {FIRST_SITE} | 0       |

    When I try `wp site deactivate 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Mark/remove a site from spam
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site spam {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as spam.
      """

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id      | spam |
      | {FIRST_SITE} | 1    |

    When I try `wp site spam {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already marked as spam.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} marked as spam.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id      | spam |
      | {FIRST_SITE} | 1    |

    When I run `wp site unspam {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} removed from spam.
      """

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id      | spam |
      | {FIRST_SITE} | 0    |

    When I try `wp site spam 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Mark/remove a site as mature
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site mature {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as mature.
      """

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id      | mature |
      | {FIRST_SITE} | 1    |

    When I try `wp site mature {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already marked as mature.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} marked as mature.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id      | mature |
      | {FIRST_SITE} | 1    |

    When I run `wp site unmature {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as unmature.
      """

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id      | mature |
      | {FIRST_SITE} | 0    |

    When I try `wp site unmature 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Set/Unset a site as public
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site private {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as private.
      """

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id      | public |
      | {FIRST_SITE} | 0    |

    When I try `wp site private {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already marked as private.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} marked as private.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id      | public |
      | {FIRST_SITE} | 0    |

    When I run `wp site public {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as public.
      """

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id      | public |
      | {FIRST_SITE} | 1    |

    When I try `wp site private 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Permit CLI operations against archived and suspended sites
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}

    When I run `wp site archive {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} archived.
      """

    When I run `wp --url=example.com/first option get home`
    Then STDOUT should contain:
      """
      ://example.com/first
      """

  Scenario: Create site with title containing slash
    Given a WP multisite install
    And I run `wp site create --slug=mysite --title="My\Site"`
    Then STDOUT should not be empty

    When I run `wp option get blogname --url=example.com/mysite`
    Then STDOUT should be:
      """
      My\Site
      """

  Scenario: Activate/deactivate a site by slug
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/first/
      """

    When I run `wp site deactivate --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 deactivated.
      """

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id | deleted |
      | 2       | 1       |

    When I try `wp site deactivate --slug=first`
    Then STDERR should be:
      """
      Warning: Site 2 already deactivated.
      """

    When I run `wp site activate --slug=first`
    Then STDOUT should be:
      """
      Success: Site 2 activated.
      """

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id | deleted |
      | 2       | 0       |

  Scenario: Archive/unarchive a site by slug
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/first/
      """

    When I run `wp site archive --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 archived.
      """

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id | archived |
      | 2       | 1        |

    When I try `wp site archive --slug=first`
    Then STDERR should be:
      """
      Warning: Site 2 already archived.
      """

    When I run `wp site unarchive --slug=first`
    Then STDOUT should be:
      """
      Success: Site 2 unarchived.
      """

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id | archived |
      | 2       | 0        |

  Scenario: Mark/remove a site by slug from spam
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/first/
      """

    When I run `wp site spam --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 marked as spam.
      """

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id | spam |
      | 2       | 1    |

    When I try `wp site spam --slug=first`
    Then STDERR should be:
      """
      Warning: Site 2 already marked as spam.
      """

    When I run `wp site unspam --slug=first`
    Then STDOUT should be:
      """
      Success: Site 2 removed from spam.
      """

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id | spam |
      | 2       | 0    |

  Scenario: Mark/remove a site by slug as mature
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/first/
      """

    When I run `wp site mature --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 marked as mature.
      """

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id | mature |
      | 2       | 1      |

    When I try `wp site mature --slug=first`
    Then STDERR should be:
      """
      Warning: Site 2 already marked as mature.
      """

    When I run `wp site unmature --slug=first`
    Then STDOUT should be:
      """
      Success: Site 2 marked as unmature.
      """

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id | mature |
      | 2       | 0      |

  Scenario: Set/Unset a site by slug as public
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 created: http
      """
    And STDOUT should contain:
      """
      ://example.com/first/
      """

    When I run `wp site private --slug=first`
    Then STDOUT should contain:
      """
      Success: Site 2 marked as private.
      """

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id | public |
      | 2       | 0      |

    When I try `wp site private --slug=first`
    Then STDERR should be:
      """
      Warning: Site 2 already marked as private.
      """

    When I run `wp site public --slug=first`
    Then STDOUT should be:
      """
      Success: Site 2 marked as public.
      """

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id | public |
      | 2       | 1      |
