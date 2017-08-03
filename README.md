wp-cli/entity-command
=====================

Manage WordPress core entities.

[![Build Status](https://travis-ci.org/wp-cli/entity-command.svg?branch=master)](https://travis-ci.org/wp-cli/entity-command)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp comment

Manage comments.

~~~
wp comment
~~~

**EXAMPLES**

    # Create a new comment.
    $ wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
    Success: Created comment 932.

    # Update an existing comment.
    $ wp comment update 123 --comment_author='That Guy'
    Success: Updated comment 123.

    # Delete an existing comment.
    $ wp comment delete 1337 --force
    Success: Deleted comment 1337.

    # Delete all spam comments.
    $ wp comment delete $(wp comment list --status=spam --format=ids)
    Success: Deleted comment 264.
    Success: Deleted comment 262.



### wp comment meta

Manage comment custom fields.

~~~
wp comment meta
~~~

**EXAMPLES**

    # Set comment meta
    $ wp comment meta set 123 description "Mary is a WordPress developer."
    Success: Updated custom field 'description'.

    # Get comment meta
    $ wp comment meta get 123 description
    Mary is a WordPress developer.

    # Update comment meta
    $ wp comment meta update 123 description "Mary is an awesome WordPress developer."
    Success: Updated custom field 'description'.

    # Delete comment meta
    $ wp comment meta delete 123 description
    Success: Deleted custom field.





### wp menu

List, create, assign, and delete menus.

~~~
wp menu
~~~

**EXAMPLES**

    # Create a new menu
    $ wp menu create "My Menu"
    Success: Created menu 200.

    # List existing menus
    $ wp menu list
    +---------+----------+----------+-----------+-------+
    | term_id | name     | slug     | locations | count |
    +---------+----------+----------+-----------+-------+
    | 200     | My Menu  | my-menu  |           | 0     |
    | 177     | Top Menu | top-menu | primary   | 7     |
    +---------+----------+----------+-----------+-------+

    # Create a new menu link item
    $ wp menu item add-custom my-menu Apple http://apple.com --porcelain
    1922

    # Assign the 'my-menu' menu to the 'primary' location
    $ wp menu location assign my-menu primary
    Success: Assigned location to menu.



### wp menu item

List, add, and delete items associated with a menu.

~~~
wp menu item
~~~

**EXAMPLES**

    # Add an existing post to an existing menu
    $ wp menu item add-post sidebar-menu 33 --title="Custom Test Post"
    Success: Menu item added.

    # Create a new menu link item
    $ wp menu item add-custom sidebar-menu Apple http://apple.com
    Success: Menu item added.

    # Delete menu item
    $ wp menu item delete 45
    Success: 1 menu item deleted.





### wp menu location

Manage a menu's assignment to locations.

~~~
wp menu location
~~~

**EXAMPLES**

    # List available menu locations
    $ wp menu location list
    +----------+-------------------+
    | location | description       |
    +----------+-------------------+
    | primary  | Primary Menu      |
    | social   | Social Links Menu |
    +----------+-------------------+

    # Assign the 'primary-menu' menu to the 'primary' location
    $ wp menu location assign primary-menu primary
    Success: Assigned location to menu.

    # Remove the 'primary-menu' menu from the 'primary' location
    $ wp menu location remove primary-menu primary
    Success: Removed location from menu.





### wp network meta

Manage network custom fields.

~~~
wp network meta
~~~

**EXAMPLES**

    # Get a list of super-admins
    $ wp network meta get 1 site_admins
    array (
      0 => 'supervisor',
    )





### wp option

Manage options.

~~~
wp option
~~~

**EXAMPLES**

    # Get site URL.
    $ wp option get siteurl
    http://example.com

    # Add option.
    $ wp option add my_option foobar
    Success: Added 'my_option' option.

    # Update option.
    $ wp option update my_option '{"foo": "bar"}' --format=json
    Success: Updated 'my_option' option.

    # Delete option.
    $ wp option delete my_option
    Success: Deleted 'my_option' option.



### wp option add

Add a new option value.

~~~
wp option add <key> [<value>] [--format=<format>] [--autoload=<autoload>]
~~~

Errors if the option already exists.

**OPTIONS**

	<key>
		The name of the option to add.

	[<value>]
		The value of the option to add. If ommited, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

	[--autoload=<autoload>]
		Should this option be automatically loaded.
		---
		options:
		  - 'yes'
		  - 'no'
		---

**EXAMPLES**

    # Create an option by reading a JSON file.
    $ wp option add my_option --format=json < config.json
    Success: Added 'my_option' option.



### wp option delete

Delete an option.

~~~
wp option delete <key>
~~~

**OPTIONS**

	<key>
		Key for the option.

**EXAMPLES**

    # Delete an option.
    $ wp option delete my_option
    Success: Deleted 'my_option' option.



### wp option get

Get the value for an option.

~~~
wp option get <key> [--format=<format>]
~~~

**OPTIONS**

	<key>
		Key for the option.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get option.
    $ wp option get home
    http://example.com

    # Get option in JSON format.
    $ wp option get active_plugins --format=json
    {"0":"dynamically-dynamic-sidebar\/dynamically-dynamic-sidebar.php","1":"monster-widget\/monster-widget.php","2":"show-current-template\/show-current-template.php","3":"theme-check\/theme-check.php","5":"wordpress-importer\/wordpress-importer.php"}



### wp option list

List options and their values.

~~~
wp option list [--search=<pattern>] [--exclude=<pattern>] [--autoload=<value>] [--transients] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--search=<pattern>]
		Use wildcards ( * and ? ) to match option name.

	[--exclude=<pattern>]
		Pattern to exclude. Use wildcards ( * and ? ) to match option name.

	[--autoload=<value>]
		Match only autoload options when value is on, and only not-autoload option when off.

	[--transients]
		List only transients. Use `--no-transients` to ignore all transients.

	[--field=<field>]
		Prints the value of a single field.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		The serialization format for the value. total_bytes displays the total size of matching options in bytes.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - count
		  - yaml
		  - total_bytes
		---

**AVAILABLE FIELDS**

This field will be displayed by default for each matching option:

* option_name
* option_value

These fields are optionally available:

* autoload
* size_bytes

**EXAMPLES**

    # Get the total size of all autoload options.
    $ wp option list --autoload=on --format=total_bytes
    33198

    # Find biggest transients.
    $ wp option list --search="*_transient_*" --fields=option_name,size_bytes | sort -n -k 2 | tail
    option_name size_bytes
    _site_transient_timeout_theme_roots 10
    _site_transient_theme_roots 76
    _site_transient_update_themes   181
    _site_transient_update_core 808
    _site_transient_update_plugins  6645

    # List all options beginning with "i2f_".
    $ wp option list --search="i2f_*"
    +-------------+--------------+
    | option_name | option_value |
    +-------------+--------------+
    | i2f_version | 0.1.0        |
    +-------------+--------------+

    # Delete all options beginning with "theme_mods_".
    $ wp option list --search="theme_mods_*" --field=option_name | xargs -I % wp option delete %
    Success: Deleted 'theme_mods_twentysixteen' option.
    Success: Deleted 'theme_mods_twentyfifteen' option.
    Success: Deleted 'theme_mods_twentyfourteen' option.



### wp option update

Update an option value.

~~~
wp option update <key> [<value>] [--autoload=<autoload>] [--format=<format>]
~~~

**OPTIONS**

	<key>
		The name of the option to update.

	[<value>]
		The new value. If ommited, the value is read from STDIN.

	[--autoload=<autoload>]
		Requires WP 4.2. Should this option be automatically loaded.
		---
		options:
		  - 'yes'
		  - 'no'
		---

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Update an option by reading from a file.
    $ wp option update my_option < value.txt
    Success: Updated 'my_option' option.

    # Update one option on multiple sites using xargs.
    $ wp site list --field=url | xargs -n1 -I {} sh -c 'wp --url={} option update my_option my_value'
    Success: Updated 'my_option' option.
    Success: Updated 'my_option' option.



### wp post

Manage posts.

~~~
wp post
~~~

**EXAMPLES**

    # Create a new post.
    $ wp post create --post_type=post --post_title='A sample post'
    Success: Created post 123.

    # Update an existing post.
    $ wp post update 123 --post_status=draft
    Success: Updated post 123.

    # Delete an existing post.
    $ wp post delete 123
    Success: Trashed post 123.



### wp post meta

Manage post custom fields.

~~~
wp post meta
~~~

**EXAMPLES**

    # Set post meta
    $ wp post meta set 123 _wp_page_template about.php
    Success: Updated custom field '_wp_page_template'.

    # Get post meta
    $ wp post meta get 123 _wp_page_template
    about.php

    # Update post meta
    $ wp post meta update 123 _wp_page_template contact.php
    Success: Updated custom field '_wp_page_template'.

    # Delete post meta
    $ wp post meta delete 123 _wp_page_template
    Success: Deleted custom field.





### wp post term

Manage post terms.

~~~
wp post term
~~~

**EXAMPLES**

    # Set post terms
    $ wp post term set 123 test category
    Success: Set terms.





### wp post-type

Manage post types.

~~~
wp post-type
~~~

**EXAMPLES**

    # Get details about a post type
    $ wp post-type get page --fields=name,label,hierarchical --format=json
    {"name":"page","label":"Pages","hierarchical":true}

    # List post types with 'post' capability type
    $ wp post-type list --capability_type=post --fields=name,public
    +---------------+--------+
    | name          | public |
    +---------------+--------+
    | post          | 1      |
    | attachment    | 1      |
    | revision      |        |
    | nav_menu_item |        |
    +---------------+--------+



### wp site

Perform site-wide operations.

~~~
wp site
~~~

**EXAMPLES**

    # Create site
    $ wp site create --slug=example
    Success: Site 3 created: www.example.com/example/

    # Output a simple list of site URLs
    $ wp site list --field=url
    http://www.example.com/
    http://www.example.com/subdir/

    # Delete site
    $ wp site delete 123
    Are you sure you want to delete the 'http://www.example.com/example' site? [y/n] y
    Success: The site at 'http://www.example.com/example' was deleted.



### wp taxonomy

Manage taxonomies.

~~~
wp taxonomy
~~~

**EXAMPLES**

    # List all taxonomies with 'post' object type.
    $ wp taxonomy list --object_type=post --fields=name,public
    +-------------+--------+
    | name        | public |
    +-------------+--------+
    | category    | 1      |
    | post_tag    | 1      |
    | post_format | 1      |
    +-------------+--------+

    # Get capabilities of 'post_tag' taxonomy.
    $ wp taxonomy get post_tag --field=cap
    {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}



### wp term

Manage terms.

~~~
wp term
~~~

**EXAMPLES**

    # Create a new term.
    $ wp term create category Apple --description="A type of fruit"
    Success: Created category 199.

    # Get details about a term.
    $ wp term get category 199 --format=json --fields=term_id,name,slug,count
    {"term_id":199,"name":"Apple","slug":"apple","count":1}

    # Update an existing term.
    $ wp term update category 15 --name=Apple
    Success: Term updated.

    # Get the term's URL.
    $ wp term list post_tag --include=123 --field=url
    http://example.com/tag/tips-and-tricks

    # Delete post category
    $ wp term delete category 15
    Success: Deleted category 15.

    # Recount posts assigned to each categories and tags
    $ wp term recount category post_tag
    Success: Updated category term count
    Success: Updated post_tag term count



### wp term meta

Manage term custom fields.

~~~
wp term meta
~~~

**EXAMPLES**

    # Set term meta
    $ wp term meta set 123 bio "Mary is a WordPress developer."
    Success: Updated custom field 'bio'.

    # Get term meta
    $ wp term meta get 123 bio
    Mary is a WordPress developer.

    # Update term meta
    $ wp term meta update 123 bio "Mary is an awesome WordPress developer."
    Success: Updated custom field 'bio'.

    # Delete term meta
    $ wp term meta delete 123 bio
    Success: Deleted custom field.





### wp user

Manage users.

~~~
wp user
~~~

**EXAMPLES**

    # List user IDs
    $ wp user list --field=ID
    1

    # Create a new user.
    $ wp user create bob bob@example.com --role=author
    Success: Created user 3.
    Password: k9**&I4vNH(&

    # Update an existing user.
    $ wp user update 123 --display_name=Mary --user_pass=marypass
    Success: Updated user 123.

    # Delete user 123 and reassign posts to user 567
    $ wp user delete 123 --reassign=567
    Success: Removed user 123 from http://example.com



### wp user meta

Manage user custom fields.

~~~
wp user meta
~~~

**EXAMPLES**

    # Add user meta
    $ wp user meta add 123 bio "Mary is an WordPress developer."
    Success: Added custom field.

    # List user meta
    $ wp user meta list 123 --keys=nickname,description,wp_capabilities
    +---------+-----------------+--------------------------------+
    | user_id | meta_key        | meta_value                     |
    +---------+-----------------+--------------------------------+
    | 123     | nickname        | supervisor                     |
    | 123     | description     | Mary is a WordPress developer. |
    | 123     | wp_capabilities | {"administrator":true}         |
    +---------+-----------------+--------------------------------+

    # Update user meta
    $ wp user meta update 123 bio "Mary is an awesome WordPress developer."
    Success: Updated custom field 'bio'.

    # Delete user meta
    $ wp user meta delete 123 bio
    Success: Deleted custom field.





### wp user term

Manage user terms.

~~~
wp user term
~~~

**EXAMPLES**

    # Set user terms
    $ wp user term set 123 test category
    Success: Set terms.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/entity-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/entity-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/entity-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/entity-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

Github issues aren't for general support questions, but there are other venues you can try: http://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
