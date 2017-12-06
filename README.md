wp-cli/entity-command
=====================

Manage WordPress core entities.

[![Build Status](https://travis-ci.org/wp-cli/entity-command.svg?branch=master)](https://travis-ci.org/wp-cli/entity-command)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp comment

Creates, updates, deletes, and moderates comments.

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



### wp comment approve

Approve a comment.

~~~
wp comment approve <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to approve.

**EXAMPLES**

    # Approve comment.
    $ wp comment approve 1337
    Success: Approved comment 1337.



### wp comment count

Count comments, on whole blog or on a given post.

~~~
wp comment count [<post-id>]
~~~

**OPTIONS**

	[<post-id>]
		The ID of the post to count comments in.

**EXAMPLES**

    # Count comments on whole blog.
    $ wp comment count
    approved:        33
    spam:            3
    trash:           1
    post-trashed:    0
    all:             34
    moderated:       1
    total_comments:  37

    # Count comments in a post.
    $ wp comment count 42
    approved:        19
    spam:            0
    trash:           0
    post-trashed:    0
    all:             19
    moderated:       0
    total_comments:  19



### wp comment create

Create a new comment.

~~~
wp comment create [--<field>=<value>] [--porcelain]
~~~

**OPTIONS**

	[--<field>=<value>]
		Associative args for the new comment. See wp_insert_comment().

	[--porcelain]
		Output just the new comment id.

**EXAMPLES**

    # Create comment.
    $ wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
    Success: Created comment 932.



### wp comment delete

Delete a comment.

~~~
wp comment delete <id>... [--force]
~~~

**OPTIONS**

	<id>...
		One or more IDs of comments to delete.

	[--force]
		Skip the trash bin.

**EXAMPLES**

    # Delete comment.
    $ wp comment delete 1337 --force
    Success: Deleted comment 1337.

    # Delete multiple comments.
    $ wp comment delete 1337 2341 --force
    Success: Deleted comment 1337.
    Success: Deleted comment 2341.



### wp comment exists

Verify whether a comment exists.

~~~
wp comment exists <id>
~~~

Displays a success message if the comment does exist.

**OPTIONS**

	<id>
		The ID of the comment to check.

**EXAMPLES**

    # Check whether comment exists.
    $ wp comment exists 1337
    Success: Comment with ID 1337 exists.



### wp comment generate

Generate some number of new dummy comments.

~~~
wp comment generate [--count=<number>] [--post_id=<post-id>] [--format=<format>]
~~~

Creates a specified number of new comments with dummy data.

**OPTIONS**

	[--count=<number>]
		How many comments to generate?
		---
		default: 100
		---

	[--post_id=<post-id>]
		Assign comments to a specific post.

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate comments for the given post.
    $ wp comment generate --format=ids --count=3 --post_id=123
    138 139 140

    # Add meta to every generated comment.
    $ wp comment generate --format=ids --count=3 | xargs -d ' ' -I % wp comment meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### wp comment get

Get data of a single comment.

~~~
wp comment get <id> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The comment to get.

	[--field=<field>]
		Instead of returning the whole comment, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get comment.
    $ wp comment get 21 --field=content
    Thanks for all the comments, everyone!



### wp comment list

Get a list of comments.

~~~
wp comment list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--<field>=<value>]
		One or more args to pass to WP_Comment_Query.

	[--field=<field>]
		Prints the value of a single field for each comment.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - ids
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each comment:

* comment_ID
* comment_post_ID
* comment_date
* comment_approved
* comment_author
* comment_author_email

These fields are optionally available:

* comment_author_url
* comment_author_IP
* comment_date_gmt
* comment_content
* comment_karma
* comment_agent
* comment_type
* comment_parent
* user_id
* url

**EXAMPLES**

    # List comment IDs.
    $ wp comment list --field=ID
    22
    23
    24

    # List comments of a post.
    $ wp comment list --post_id=1 --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 1          | 2015-06-20 09:00:10 | Mr WordPress   |
    +------------+---------------------+----------------+

    # List approved comments.
    $ wp comment list --number=3 --status=approve --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 1          | 2015-06-20 09:00:10 | Mr WordPress   |
    | 30         | 2013-03-14 12:35:07 | John Doe       |
    | 29         | 2013-03-14 11:56:08 | Jane Doe       |
    +------------+---------------------+----------------+



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





### wp comment meta add

Add a meta field.

~~~
wp comment meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp comment meta delete

Delete a meta field.

~~~
wp comment meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp comment meta get

Get meta field value.

~~~
wp comment meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Accepted values: table, json. Default: table



### wp comment meta list

List all metadata associated with an object.

~~~
wp comment meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Accepted values: table, csv, json, count. Default: table

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---



### wp comment meta patch

Update a nested value for a meta field.

~~~
wp comment meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp comment meta pluck

Get a nested value from a meta field.

~~~
wp comment meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp comment meta update

Update a meta field.

~~~
wp comment meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp comment recount

Recalculate the comment_count value for one or more posts.

~~~
wp comment recount <id>...
~~~

**OPTIONS**

	<id>...
		IDs for one or more posts to update.

**EXAMPLES**

    # Recount comment for the post.
    $ wp comment recount 123
    Updated post 123 comment count to 67.



### wp comment spam

Mark a comment as spam.

~~~
wp comment spam <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to mark as spam.

**EXAMPLES**

    # Spam comment.
    $ wp comment spam 1337
    Success: Marked as spam comment 1337.



### wp comment status

Get status of a comment.

~~~
wp comment status <id>
~~~

**OPTIONS**

	<id>
		The ID of the comment to check.

**EXAMPLES**

    # Get status of comment.
    $ wp comment status 1337
    approved



### wp comment trash

Trash a comment.

~~~
wp comment trash <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to trash.

**EXAMPLES**

    # Trash comment.
    $ wp comment trash 1337
    Success: Trashed comment 1337.



### wp comment unapprove

Unapprove a comment.

~~~
wp comment unapprove <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to unapprove.

**EXAMPLES**

    # Unapprove comment.
    $ wp comment unapprove 1337
    Success: Unapproved comment 1337.



### wp comment unspam

Unmark a comment as spam.

~~~
wp comment unspam <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to unmark as spam.

**EXAMPLES**

    # Unspam comment.
    $ wp comment unspam 1337
    Success: Unspammed comment 1337.



### wp comment untrash

Untrash a comment.

~~~
wp comment untrash <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to untrash.

**EXAMPLES**

    # Untrash comment.
    $ wp comment untrash 1337
    Success: Untrashed comment 1337.



### wp comment update

Update one or more comments.

~~~
wp comment update <id>... --<field>=<value>
~~~

**OPTIONS**

	<id>...
		One or more IDs of comments to update.

	--<field>=<value>
		One or more fields to update. See wp_update_comment().

**EXAMPLES**

    # Update comment.
    $ wp comment update 123 --comment_author='That Guy'
    Success: Updated comment 123.



### wp menu

Lists, creates, assigns, and deletes the active theme's navigation menus.

~~~
wp menu
~~~

See the [Navigation Menus](https://developer.wordpress.org/themes/functionality/navigation-menus/) reference in the Theme Handbook.

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



### wp menu create

Create a new menu.

~~~
wp menu create <menu-name> [--porcelain]
~~~

**OPTIONS**

	<menu-name>
		A descriptive name for the menu.

	[--porcelain]
		Output just the new menu id.

**EXAMPLES**

    $ wp menu create "My Menu"
    Success: Created menu 200.



### wp menu delete

Delete one or more menus.

~~~
wp menu delete <menu>...
~~~

**OPTIONS**

	<menu>...
		The name, slug, or term ID for the menu(s).

**EXAMPLES**

    $ wp menu delete "My Menu"
    Success: 1 menu deleted.



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





### wp menu item add-custom

Add a custom menu item.

~~~
wp menu item add-custom <menu> <title> <link> [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<title>
		Title for the link.

	<link>
		Target URL for the link.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ wp menu item add-custom sidebar-menu Apple http://apple.com
    Success: Menu item added.



### wp menu item add-post

Add a post as a menu item.

~~~
wp menu item add-post <menu> <post-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<post-id>
		Post ID to add to the menu.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ wp menu item add-post sidebar-menu 33 --title="Custom Test Post"
    Success: Menu item added.



### wp menu item add-term

Add a taxonomy term as a menu item.

~~~
wp menu item add-term <menu> <taxonomy> <term-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<taxonomy>
		Taxonomy of the term to be added.

	<term-id>
		Term ID of the term to be added.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ wp menu item add-term sidebar-menu post_tag 24
    Success: Menu item added.



### wp menu item delete

Delete one or more items from a menu.

~~~
wp menu item delete <db-id>...
~~~

**OPTIONS**

	<db-id>...
		Database ID for the menu item(s).

**EXAMPLES**

    $ wp menu item delete 45
    Success: 1 menu item deleted.



### wp menu item list

Get a list of items associated with a menu.

~~~
wp menu item list <menu> [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - ids
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each menu item:

* db_id
* type
* title
* link
* position

These fields are optionally available:

* menu_item_parent
* object_id
* object
* type
* type_label
* target
* attr_title
* description
* classes
* xfn

**EXAMPLES**

    $ wp menu item list main-menu
    +-------+-----------+-------------+---------------------------------+----------+
    | db_id | type      | title       | link                            | position |
    +-------+-----------+-------------+---------------------------------+----------+
    | 5     | custom    | Home        | http://example.com              | 1        |
    | 6     | post_type | Sample Page | http://example.com/sample-page/ | 2        |
    +-------+-----------+-------------+---------------------------------+----------+



### wp menu item update

Update a menu item.

~~~
wp menu item update <db-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>]
~~~

**OPTIONS**

	<db-id>
		Database ID for the menu item.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

**EXAMPLES**

    $ wp menu item update 45 --title=WordPress --link='http://wordpress.org' --target=_blank --position=2
    Success: Menu item updated.



### wp menu list

Get a list of menus.

~~~
wp menu list [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - ids
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each menu:

* term_id
* name
* slug
* count

These fields are optionally available:

* term_group
* term_taxonomy_id
* taxonomy
* description
* parent
* locations

**EXAMPLES**

    $ wp menu list
    +---------+----------+----------+-----------+-------+
    | term_id | name     | slug     | locations | count |
    +---------+----------+----------+-----------+-------+
    | 200     | My Menu  | my-menu  |           | 0     |
    | 177     | Top Menu | top-menu | primary   | 7     |
    +---------+----------+----------+-----------+-------+



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





### wp menu location assign

Assign a location to a menu.

~~~
wp menu location assign <menu> <location>
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<location>
		Location's slug.

**EXAMPLES**

    $ wp menu location assign primary-menu primary
    Success: Assigned location primary to menu primary-menu.



### wp menu location list

List locations for the current theme.

~~~
wp menu location list [--format=<format>]
~~~

**OPTIONS**

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each location:

* name
* description

**EXAMPLES**

    $ wp menu location list
    +----------+-------------------+
    | location | description       |
    +----------+-------------------+
    | primary  | Primary Menu      |
    | social   | Social Links Menu |
    +----------+-------------------+



### wp menu location remove

Remove a location from a menu.

~~~
wp menu location remove <menu> <location>
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<location>
		Location's slug.

**EXAMPLES**

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



### wp network meta add

Add a meta field.

~~~
wp network meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp network meta delete

Delete a meta field.

~~~
wp network meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp network meta get

Get meta field value.

~~~
wp network meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Accepted values: table, json. Default: table



### wp network meta list

List all metadata associated with an object.

~~~
wp network meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Accepted values: table, csv, json, count. Default: table

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---



### wp network meta patch

Update a nested value for a meta field.

~~~
wp network meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp network meta pluck

Get a nested value from a meta field.

~~~
wp network meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp network meta update

Update a meta field.

~~~
wp network meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp option

Retrieves and sets site options, including plugin and WordPress settings.

~~~
wp option
~~~

See the [Plugin Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) and the [Theme Options](https://developer.wordpress.org/themes/customize-api/) for more information on adding customized options.

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

    # Get blog description.
    $ wp option get blogdescription
    A random blog description

    # Get blog name
    $ wp option get blogname
    A random blog name

    # Get admin email.
    $ wp option get admin_email
    someone@example.com

    # Get option in JSON format.
    $ wp option get active_plugins --format=json
    {"0":"dynamically-dynamic-sidebar\/dynamically-dynamic-sidebar.php","1":"monster-widget\/monster-widget.php","2":"show-current-template\/show-current-template.php","3":"theme-check\/theme-check.php","5":"wordpress-importer\/wordpress-importer.php"}



### wp option list

List options and their values.

~~~
wp option list [--search=<pattern>] [--exclude=<pattern>] [--autoload=<value>] [--transients] [--field=<field>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
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

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: option_id
		options:
		 - option_id
		 - option_name
		 - option_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
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



### wp option patch

Update a nested value in an option.

~~~
wp option patch <action> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<key>
		The option name.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp option pluck

Get a nested value from an option.

~~~
wp option pluck <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<key>
		The option name.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml
		---



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

    # Update site blog name.
    $ wp option update blogname "Random blog name"
    Success: Updated 'blogname' option.

    # Update site blog description.
    $ wp option update blogdescription "Some random blog description"
    Success: Updated 'blogdescription' option.

    # Update admin email address.
    $ wp option update admin_email someone@example.com
    Success: Updated 'admin_email' option.

    # Set the default role.
    $ wp option update default_role author
    Success: Updated 'default_role' option.

    # Set the timezone string.
    $ wp option update timezone_string "America/New_York"
    Success: Updated 'timezone_string' option.



### wp post

Manages posts, content, and meta.

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





### wp post meta add

Add a meta field.

~~~
wp post meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp post meta delete

Delete a meta field.

~~~
wp post meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp post meta get

Get meta field value.

~~~
wp post meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Accepted values: table, json. Default: table



### wp post meta list

List all metadata associated with an object.

~~~
wp post meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Accepted values: table, csv, json, count. Default: table

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---



### wp post meta patch

Update a nested value for a meta field.

~~~
wp post meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp post meta pluck

Get a nested value from a meta field.

~~~
wp post meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp post meta update

Update a meta field.

~~~
wp post meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



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

Retrieves details on the site's registered post types.

~~~
wp post-type
~~~

Get information on WordPress' built-in and the site's [custom post types](https://developer.wordpress.org/plugins/post-types/).

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

Performs site-wide operations on a multisite install.

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



### wp site empty

Empty a site of its content (posts, comments, terms, and meta).

~~~
wp site empty [--uploads] [--yes]
~~~

Truncates posts, comments, and terms tables to empty a site of its
content. Doesn't affect site configuration (options) or users.

If running a persistent object cache, make sure to flush the cache
after emptying the site, as the cache values will be invalid otherwise.

To also empty custom database tables, you'll need to hook into command
execution:

```
WP_CLI::add_hook( 'after_invoke:site empty', function(){
    global $wpdb;
    foreach( array( 'p2p', 'p2pmeta' ) as $table ) {
        $table = $wpdb->$table;
        $wpdb->query( "TRUNCATE $table" );
    }
});
```

**OPTIONS**

	[--uploads]
		Also delete *all* files in the site's uploads directory.

	[--yes]
		Proceed to empty the site without a confirmation prompt.

**EXAMPLES**

    $ wp site empty
    Are you sure you want to empty the site at http://www.example.com of all posts, comments, and terms? [y/n] y
    Success: The site at 'http://www.example.com' was emptied.



### wp taxonomy

Retrieves information about registered taxonomies.

~~~
wp taxonomy
~~~

See references for [built-in taxonomies](https://developer.wordpress.org/themes/basics/categories-tags-custom-taxonomies/) and [custom taxonomies](https://developer.wordpress.org/plugins/taxonomies/working-with-custom-taxonomies/).

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



### wp taxonomy get

Get details about a registered taxonomy.

~~~
wp taxonomy get <taxonomy> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy slug.

	[--field=<field>]
		Instead of returning the whole taxonomy, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get details of `category` taxonomy.
    $ wp taxonomy get category --fields=name,label,object_type
    +-------------+------------+
    | Field       | Value      |
    +-------------+------------+
    | name        | category   |
    | label       | Categories |
    | object_type | ["post"]   |
    +-------------+------------+

    # Get capabilities of 'post_tag' taxonomy.
    $ wp taxonomy get post_tag --field=cap
    {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}



### wp taxonomy list

List registered taxonomies.

~~~
wp taxonomy list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--<field>=<value>]
		Filter by one or more fields (see get_taxonomies() first parameter for a list of available fields).

	[--field=<field>]
		Prints the value of a single field for each taxonomy.

	[--fields=<fields>]
		Limit the output to specific taxonomy fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* name
* label
* description
* public
* hierarchical

There are no optionally available fields.

**EXAMPLES**

    # List all taxonomies.
    $ wp taxonomy list --format=csv
    name,label,description,object_type,show_tagcloud,hierarchical,public
    category,Categories,,post,1,1,1
    post_tag,Tags,,post,1,,1
    nav_menu,"Navigation Menus",,nav_menu_item,,,
    link_category,"Link Categories",,link,1,,
    post_format,Format,,post,,,1

    # List all taxonomies with 'post' object type.
    $ wp taxonomy list --object_type=post --fields=name,public
    +-------------+--------+
    | name        | public |
    +-------------+--------+
    | category    | 1      |
    | post_tag    | 1      |
    | post_format | 1      |
    +-------------+--------+



### wp term

Manages taxonomy terms and term meta, with create, delete, and list commands.

~~~
wp term
~~~

See reference for [taxonomies and their terms](https://codex.wordpress.org/Taxonomies).

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



### wp term create

Create a new term.

~~~
wp term create <taxonomy> <term> [--slug=<slug>] [--description=<description>] [--parent=<term-id>] [--porcelain]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy for the new term.

	<term>
		A name for the new term.

	[--slug=<slug>]
		A unique slug for the new term. Defaults to sanitized version of name.

	[--description=<description>]
		A description for the new term.

	[--parent=<term-id>]
		A parent for the new term.

	[--porcelain]
		Output just the new term id.

**EXAMPLES**

    # Create a new category "Apple" with a description.
    $ wp term create category Apple --description="A type of fruit"
    Success: Created category 199.



### wp term delete

Delete an existing term.

~~~
wp term delete <taxonomy> <term>... [--by=<field>]
~~~

Errors if the term doesn't exist, or there was a problem in deleting it.

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to delete.

	<term>...
		One or more IDs or slugs of terms to delete.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

**EXAMPLES**

    # Delete post category by id
    $ wp term delete category 15
    Deleted category 15.
    Success: Deleted 1 of 1 terms.

    # Delete post category by slug
    $ wp term delete category apple --by=slug
    Deleted category 15.
    Success: Deleted 1 of 1 terms.

    # Delete all post tags
    $ wp term list post_tag --field=term_id | xargs wp term delete post_tag
    Deleted post_tag 159.
    Deleted post_tag 160.
    Deleted post_tag 161.
    Success: Deleted 3 of 3 terms.



### wp term generate

Generate some terms.

~~~
wp term generate <taxonomy> [--count=<number>] [--max_depth=<number>] [--format=<format>]
~~~

Creates a specified number of new terms with dummy data.

**OPTIONS**

	<taxonomy>
		The taxonomy for the generated terms.

	[--count=<number>]
		How many terms to generate?
		---
		default: 100
		---

	[--max_depth=<number>]
		Generate child terms down to a certain depth.
		---
		default: 1
		---

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate post categories.
    $ wp term generate category --count=10
    Generating terms  100% [=========] 0:02 / 0:02

    # Add meta to every generated term.
    $ wp term generate category --format=ids --count=3 | xargs -d ' ' -I % wp term meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### wp term get

Get details about a term.

~~~
wp term get <taxonomy> <term> [--by=<field>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to get

	<term>
		ID or slug of the term to get

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

	[--field=<field>]
		Instead of returning the whole term, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get details about a category with id 199.
    $ wp term get category 199 --format=json
    {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}

    # Get details about a category with slug apple.
    $ wp term get category apple --by=slug --format=json
    {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}



### wp term list

List terms in a taxonomy.

~~~
wp term list <taxonomy>... [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>...
		List terms of one or more taxonomies

	[--<field>=<value>]
		Filter by one or more fields (see get_terms() $args parameter for a list of fields).

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* term_taxonomy_id
* name
* slug
* description
* parent
* count

These fields are optionally available:

* url

**EXAMPLES**

    # List post categories
    $ wp term list category --format=csv
    term_id,term_taxonomy_id,name,slug,description,parent,count
    2,2,aciform,aciform,,0,1
    3,3,antiquarianism,antiquarianism,,0,1
    4,4,arrangement,arrangement,,0,1
    5,5,asmodeus,asmodeus,,0,1

    # List post tags
    $ wp term list post_tag --fields=name,slug
    +-----------+-------------+
    | name      | slug        |
    +-----------+-------------+
    | 8BIT      | 8bit        |
    | alignment | alignment-2 |
    | Articles  | articles    |
    | aside     | aside       |
    +-----------+-------------+



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





### wp term meta add

Add a meta field.

~~~
wp term meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp term meta delete

Delete a meta field.

~~~
wp term meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp term meta get

Get meta field value.

~~~
wp term meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Accepted values: table, json. Default: table



### wp term meta list

List all metadata associated with an object.

~~~
wp term meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Accepted values: table, csv, json, count. Default: table

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---



### wp term meta patch

Update a nested value for a meta field.

~~~
wp term meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp term meta pluck

Get a nested value from a meta field.

~~~
wp term meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp term meta update

Update a meta field.

~~~
wp term meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp term recount

Recalculate number of posts assigned to each term.

~~~
wp term recount <taxonomy>...
~~~

In instances where manual updates are made to the terms assigned to
posts in the database, the number of posts associated with a term
can become out-of-sync with the actual number of posts.

This command runs wp_update_term_count() on the taxonomy's terms
to bring the count back to the correct value.

**OPTIONS**

	<taxonomy>...
		One or more taxonomies to recalculate.

**EXAMPLES**

    # Recount posts assigned to each categories and tags
    $ wp term recount category post_tag
    Success: Updated category term count.
    Success: Updated post_tag term count.

    # Recount all listed taxonomies
    $ wp taxonomy list --field=name | xargs wp term recount
    Success: Updated category term count.
    Success: Updated post_tag term count.
    Success: Updated nav_menu term count.
    Success: Updated link_category term count.
    Success: Updated post_format term count.



### wp term update

Update an existing term.

~~~
wp term update <taxonomy> <term> [--by=<field>] [--name=<name>] [--slug=<slug>] [--description=<description>] [--parent=<term-id>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to update.

	<term>
		ID or slug for the term to update.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

	[--name=<name>]
		A new name for the term.

	[--slug=<slug>]
		A new slug for the term.

	[--description=<description>]
		A new description for the term.

	[--parent=<term-id>]
		A new parent for the term.

**EXAMPLES**

    # Change category with id 15 to use the name "Apple"
    $ wp term update category 15 --name=Apple
    Success: Term updated.

    # Change category with slug apple to use the name "Apple"
    $ wp term update category apple --by=slug --name=Apple
    Success: Term updated.



### wp user

Manages users, along with their roles, capabilities, and meta.

~~~
wp user
~~~

See references for [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities) and [WP User class](https://codex.wordpress.org/Class_Reference/WP_User).

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





### wp user meta add

Add a meta field.

~~~
wp user meta add <user> <key> <value> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to add metadata for.

	<key>
		The metadata key.

	<value>
		The new metadata value.

	[--format=<format>]
		The serialization format for the value. Default is plaintext.

**EXAMPLES**

    # Add user meta
    $ wp user meta add 123 bio "Mary is an WordPress developer."
    Success: Added custom field.



### wp user meta delete

Delete a meta field.

~~~
wp user meta delete <user> <key> [<value>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to delete metadata from.

	<key>
		The metadata key.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

**EXAMPLES**

    # Delete user meta
    $ wp user meta delete 123 bio
    Success: Deleted custom field.



### wp user meta get

Get meta field value.

~~~
wp user meta get <user> <key> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get metadata for.

	<key>
		The metadata key.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get user meta
    $ wp user meta get 123 bio
    Mary is an WordPress developer.



### wp user meta list

List all metadata associated with a user.

~~~
wp user meta list <user> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get metadata for.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

**EXAMPLES**

    # List user meta
    $ wp user meta list 123 --keys=nickname,description,wp_capabilities
    +---------+-----------------+--------------------------------+
    | user_id | meta_key        | meta_value                     |
    +---------+-----------------+--------------------------------+
    | 123     | nickname        | supervisor                     |
    | 123     | description     | Mary is a WordPress developer. |
    | 123     | wp_capabilities | {"administrator":true}         |
    +---------+-----------------+--------------------------------+



### wp user meta patch

Update a nested value for a meta field.

~~~
wp user meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp user meta pluck

Get a nested value from a meta field.

~~~
wp user meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp user meta update

Update a meta field.

~~~
wp user meta update <user> <key> <value> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to update metadata for.

	<key>
		The metadata key.

	<value>
		The new metadata value.

	[--format=<format>]
		The serialization format for the value. Default is plaintext.

**EXAMPLES**

    # Update user meta
    $ wp user meta update 123 bio "Mary is an awesome WordPress developer."
    Success: Updated custom field 'bio'.



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

Github issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
