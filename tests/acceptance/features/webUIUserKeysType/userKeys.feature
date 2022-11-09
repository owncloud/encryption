@webUI @skipOnEncryptionType:masterkey @skipOnStorage:ceph
Feature: encrypt files using user specific keys
  As an admin
  I want to be able to encrypt user files using user specific keys
  So that users can use specific keys for encrypting their files

  Background:
    Given app "encryption" has been enabled
    And these users have been created with large skeleton files but not initialized:
      | username |
      | Alice    |
    And encryption has been enabled


  Scenario: encrypt all files using user keys based encryption via the occ command
    Given these users have been initialized:
      | username |
      | Alice    |
    When the administrator sets the encryption type to "user-keys" using the occ command
    And the administrator encrypts all data using the occ command
    Then the command should have been successful
    And file "textfile0.txt" of user "Alice" should be encrypted


  Scenario: file gets encrypted if the encryption is enabled and administrator has not encrypted all files but the user has logged in
    When the administrator sets the encryption type to "user-keys" using the occ command
    And user "Alice" has logged in using the webUI
    Then file "textfile0.txt" of user "Alice" should be encrypted


  Scenario: decrypt user keys based encryption of all users
    Given these users have been created with large skeleton files but not initialized:
      | username |
      | Brian    |
    And the administrator has set the encryption type to "user-keys"
    And the administrator has browsed to the admin encryption settings page
    And the administrator has enabled recovery key and set the recovery key to "recoverypass"
    And the administrator has browsed to the personal encryption settings page
    And the administrator has enabled password recovery
    And the administrator has logged out of the webUI
    And user "Alice" has logged in using the webUI
    And the user has browsed to the personal encryption settings page
    And the user has enabled password recovery
    And the user has logged out of the webUI
    And user "Brian" has logged in using the webUI
    And the user has browsed to the personal encryption settings page
    And the user has enabled password recovery
    When the administrator decrypts user keys based encryption with recovery key "recoverypass" using the occ command
    Then file "textfile0.txt" of user "Alice" should not be encrypted
    And file "textfile0.txt" of user "Brian" should not be encrypted

  @issue-encryption-206
  Scenario Outline: Sharer shares a file where receiver already has a file with the matching name
    Given using OCS API version "<ocs_api_version>"
    And user "Brian" has been created with default attributes and large skeleton files
    And user "Alice" has shared file "textfile0.txt" with user "Brian"
    When user "Alice" gets all shares shared by him using the sharing API
    Then the OCS status code should be "<ocs_status_code>"
    And the HTTP status code should be "200"
    And file "textfile0 (2).txt" should be included in the response
    Examples:
      | ocs_api_version | ocs_status_code |
      | 1               | 100             |
      | 2               | 200             |
