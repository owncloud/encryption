@webUI @skipOnEncryptionType:masterkey
Feature: encrypt files using user specific keys
  As an admin
  I want to be able to encrypt user files using user specific keys
  So that users can use specific keys for encrypting their files

  Background:
    Given user "brand-new-user" has been created
    And the app "encryption" has been enabled
    And encryption has been enabled

  Scenario: encrypt using user keys based encryption
    When the administrator sets the encryption type to "user-keys" using the occ command
    And the administrator encrypts all data using the occ command
    Then the file "textfile0.txt" of user "brand-new-user" should be encrypted

  Scenario: file gets encrypted if the encryption is enabled and administrator has not encrypted all files but the user has logged in
    When the administrator sets the encryption type to "user-keys" using the occ command
    And user "brand-new-user" has logged in using the webUI
    Then the file "textfile0.txt" of user "brand-new-user" should be encrypted

  Scenario: decrypt user keys based encryption
    Given the administrator has set the encryption type to "user-keys"
    And the administrator has encrypted all the data
    And the administrator has browsed to admin encryption settings page
    And the administrator has enabled recovery key and set the recovery key to "recoverypass"
    And the administrator has logged out of the webUI
    And user "brand-new-user" has logged in using the webUI
    And the user has browsed to personal encryption settings page
    And the user has enabled password recovery
    When the administrator decrypts user keys based encryption with recovery key "recoverypass" using the occ command
    Then the file "textfile0.txt" of user "brand-new-user" should not be encrypted