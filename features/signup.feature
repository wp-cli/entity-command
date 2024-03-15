Feature: Manage signups in a multisite installation

	Scenario: Not applicable in single installation site
		Given a WP install

    When I try `wp signup list`
    Then STDERR should be:
			"""
			Error: This is not a multisite installation.
			"""

	Scenario: List signups
		Given a WP multisite install
		And I run `wp eval 'wpmu_signup_user( "bobuser", "bobuser@example.com" );'`
		And I run `wp eval 'wpmu_signup_user( "johnuser", "johnuser@example.com" );'`

		When I run `wp signup list --fields=signup_id,user_login,user_email,active --format=csv`
		Then STDOUT should be:
			"""
			signup_id,user_login,user_email,active
			1,bobuser,bobuser@example.com,0
			2,johnuser,johnuser@example.com,0
			"""

		When I run `wp signup list --format=count --active=1`
		Then STDOUT should be:
			"""
			0
			"""

		When I run `wp signup activate bobuser`
		Then STDOUT should contain:
			"""
			Success: Signup activated.
			"""

		When I run `wp signup list --fields=signup_id,user_login,user_email,active --format=csv --active=1`
		Then STDOUT should be:
			"""
			signup_id,user_login,user_email,active
			1,bobuser,bobuser@example.com,1
			"""

	Scenario: Get signup
		Given a WP multisite install
		And I run `wp eval 'wpmu_signup_user( "bobuser", "bobuser@example.com" );'`

		When I run `wp signup get bobuser --fields=signup_id,user_login,user_email,active --format=csv`
		Then STDOUT should be:
			"""
			signup_id,user_login,user_email,active
			1,bobuser,bobuser@example.com,0
			"""

	Scenario: Delete signup
		Given a WP multisite install

		When I run `wp eval 'wpmu_signup_user( "bobuser", "bobuser@example.com" );'`
		And I run `wp signup get bobuser --field=user_login`
		Then STDOUT should be:
			"""
			bobuser
			"""

		When I run `wp signup delete bobuser@example.com`
		Then STDOUT should be:
			"""
			Success: Signup deleted.
			"""

		When I try `wp signup get bobuser`
		Then STDERR should be:
			"""
			Error: Invalid signup ID, email, login or activation key: 'bobuser'
			"""

	Scenario: Activate signup
		Given a WP multisite install
		And I run `wp eval 'wpmu_signup_user( "bobuser", "bobuser@example.com" );'`

		And I run `wp signup get bobuser --field=active`
		Then STDOUT should be:
			"""
			0
			"""

		When I run `wp signup activate bobuser`
		Then STDOUT should contain:
			"""
			Success: Signup activated.
			"""

		When I run `wp signup get bobuser --field=active`
		Then STDOUT should be:
			"""
			1
			"""

		When I run `wp user get bobuser --field=user_email`
		Then STDOUT should be:
			"""
			bobuser@example.com
			"""

	Scenario: Activate blog signup entry
		Given a WP multisite install
		And I run `wp eval 'wpmu_signup_blog( "example.com", "/bobsite/", "My Awesome Title", "bobuser", "bobuser@example.com" );'`

		When I run `wp signup get bobuser --fields=user_login,domain,path,active --format=csv`
		Then STDOUT should be:
			"""
			user_login,domain,path,active
			bobuser,example.com,/bobsite/,0
			"""

		When I run `wp signup activate bobuser`
		Then STDOUT should contain:
			"""
			Success: Signup activated.
			"""

		When I run `wp site list --fields=domain,path`
		Then STDOUT should be a table containing rows:
			| domain      | path      |
			| example.com | /         |
			| example.com | /bobsite/ |
