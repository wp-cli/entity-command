Feature: Manage user privacy requests

  @require-wp-4.9.6
  Scenario: Create and list privacy requests
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {REQUEST_ID}

    When I run `wp user privacy-request list --format=csv --fields=ID,user_email,action_name,status`
    Then STDOUT should contain:
      """
      {REQUEST_ID},admin@example.com,export_personal_data,request-pending
      """

    When I run `wp user privacy-request list --format=ids`
    Then STDOUT should contain:
      """
      {REQUEST_ID}
      """

    When I run `wp user privacy-request list --format=count`
    Then STDOUT should be:
      """
      1
      """

  @require-wp-4.9.6
  Scenario: Create requests with confirmed status
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --status=confirmed --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {REQUEST_ID}

    When I run `wp user privacy-request list --format=csv --fields=ID,status`
    Then STDOUT should contain:
      """
      {REQUEST_ID},request-confirmed
      """

  @require-wp-4.9.6
  Scenario: Filter privacy request list by action type
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --porcelain`
    Then save STDOUT as {EXPORT_ID}

    When I run `wp user privacy-request create admin@example.com remove_personal_data --porcelain`
    Then save STDOUT as {ERASE_ID}

    When I run `wp user privacy-request list --action-type=export_personal_data --format=ids`
    Then STDOUT should contain:
      """
      {EXPORT_ID}
      """
    And STDOUT should not contain:
      """
      {ERASE_ID}
      """

    When I run `wp user privacy-request list --action-type=remove_personal_data --format=ids`
    Then STDOUT should contain:
      """
      {ERASE_ID}
      """
    And STDOUT should not contain:
      """
      {EXPORT_ID}
      """

  @require-wp-4.9.6
  Scenario: Filter privacy request list by status
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --status=confirmed --porcelain`
    Then save STDOUT as {CONFIRMED_ID}

    When I run `wp user privacy-request create admin@example.com remove_personal_data --porcelain`
    Then save STDOUT as {PENDING_ID}

    When I run `wp user privacy-request list --status=request-confirmed --format=ids`
    Then STDOUT should contain:
      """
      {CONFIRMED_ID}
      """
    And STDOUT should not contain:
      """
      {PENDING_ID}
      """

    When I run `wp user privacy-request list --status=request-pending --format=ids`
    Then STDOUT should contain:
      """
      {PENDING_ID}
      """
    And STDOUT should not contain:
      """
      {CONFIRMED_ID}
      """

  @require-wp-4.9.6
  Scenario: Delete privacy requests
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --porcelain`
    Then save STDOUT as {REQUEST_ID}

    When I run `wp user privacy-request delete {REQUEST_ID}`
    Then STDOUT should contain:
      """
      Success: Deleted 1 of 1 privacy requests.
      """

    When I run `wp user privacy-request list --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I try `wp user privacy-request delete 9999`
    Then STDERR should contain:
      """
      Warning: Could not find privacy request with ID 9999.
      """

  @require-wp-4.9.6
  Scenario: Complete privacy requests
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --status=confirmed --porcelain`
    Then save STDOUT as {REQUEST_ID}

    When I run `wp user privacy-request complete {REQUEST_ID}`
    Then STDOUT should contain:
      """
      Success: Completed 1 of 1 privacy requests.
      """

    When I run `wp user privacy-request list --status=request-completed --format=ids`
    Then STDOUT should contain:
      """
      {REQUEST_ID}
      """

    When I try `wp user privacy-request complete 9999`
    Then STDERR should contain:
      """
      Warning: Could not find privacy request with ID 9999.
      """

  @require-wp-4.9.6
  Scenario: Erase personal data for a request
    Given a WP install

    When I run `wp user privacy-request create admin@example.com remove_personal_data --status=confirmed --porcelain`
    Then save STDOUT as {REQUEST_ID}

    When I run `wp user privacy-request erase {REQUEST_ID}`
    Then STDOUT should contain:
      """
      Success: Erased personal data for request {REQUEST_ID}.
      """

    When I run `wp user privacy-request list --status=request-completed --format=ids`
    Then STDOUT should contain:
      """
      {REQUEST_ID}
      """

  @require-wp-4.9.6
  Scenario: Erase command fails for non-erasure requests
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --status=confirmed --porcelain`
    Then save STDOUT as {REQUEST_ID}

    When I try `wp user privacy-request erase {REQUEST_ID}`
    Then STDERR should contain:
      """
      Error: Request {REQUEST_ID} is not a 'remove_personal_data' request.
      """

  @require-wp-4.9.6
  Scenario: Export personal data for a request
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data --status=confirmed --porcelain`
    Then save STDOUT as {REQUEST_ID}

    When I run `wp user privacy-request export {REQUEST_ID}`
    Then STDOUT should contain:
      """
      Success: Exported personal data to:
      """
    And STDOUT should contain:
      """
      .zip
      """

    When I run `wp user privacy-request list --status=request-completed --format=ids`
    Then STDOUT should contain:
      """
      {REQUEST_ID}
      """

  @require-wp-4.9.6
  Scenario: Export command fails for non-export requests
    Given a WP install

    When I run `wp user privacy-request create admin@example.com remove_personal_data --status=confirmed --porcelain`
    Then save STDOUT as {REQUEST_ID}

    When I try `wp user privacy-request export {REQUEST_ID}`
    Then STDERR should contain:
      """
      Error: Request {REQUEST_ID} is not an 'export_personal_data' request.
      """

  @require-wp-4.9.6
  Scenario: Create request without porcelain flag
    Given a WP install

    When I run `wp user privacy-request create admin@example.com export_personal_data`
    Then STDOUT should contain:
      """
      Created privacy request
      """

  @require-wp-4.9.6
  Scenario: Create request with invalid email address
    Given a WP install

    When I try `wp user privacy-request create not-an-email export_personal_data`
    Then STDERR should contain:
      """
      Error:
      """

  @require-wp-4.9.6
  Scenario: Create request with invalid action type
    Given a WP install

    When I try `wp user privacy-request create admin@example.com invalid_action`
    Then STDERR should contain:
      """
      Error: Invalid value specified for positional arg.
      """

  @require-wp-4.9.6
  Scenario: Create request with invalid status
    Given a WP install

    When I try `wp user privacy-request create admin@example.com export_personal_data --status=invalid`
    Then STDERR should contain:
      """
      Error: Parameter errors:
       Invalid value specified for 'status' (The initial status of the request.)
      """
