@api
Feature: encrypt files with legacy and default(new) encoding

  Background:
    Given user "Alice" has been created with default attributes and without skeleton files

  Scenario: encrypted file with default encoding
    Given user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"

  Scenario: encrypted file with legacy encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"

  Scenario: Download encrypted files with different encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some new data" to "/textfile2.txt"
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"
    And the content of file "/textfile2.txt" for user "Alice" should be "some new data"

  Scenario: overwrite file with legacy encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some new data" to "/textfile1.txt"
    Then the content of file "/textfile1.txt" for user "Alice" should be "some new data"

  Scenario: overwrite file with default encoding by legacy encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some new data" to "/textfile1.txt"
    Then the content of file "/textfile1.txt" for user "Alice" should be "some new data"

  Scenario: share file with legacy encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Brian" has been created with default attributes and without skeleton files
    And user "Alice" has shared file "textfile1.txt" with user "Brian"
    Then the content of file "/textfile1.txt" for user "Brian" should be "some data"

  Scenario: share file with default encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Brian" has been created with default attributes and without skeleton files
    And user "Alice" has shared file "textfile1.txt" with user "Brian"
    Then the content of file "/textfile1.txt" for user "Brian" should be "some data"

  Scenario: Restore a file with legacy encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Alice" has deleted file "/textfile1.txt"
    When user "Alice" restores the folder with original path "/textfile1.txt" using the trashbin API
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"

  Scenario: Restore a file with default encoding
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Alice" has deleted file "/textfile1.txt"
    When user "Alice" restores the folder with original path "/textfile1.txt" using the trashbin API
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"

  Scenario: Restore a file with legacy encoding after encoding is changed
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Alice" has deleted file "/textfile1.txt"
    When the administrator adds system config key "encryption.use_legacy_encoding" with value "false" and type boolean using the occ command
    And user "Alice" restores the folder with original path "/textfile1.txt" using the trashbin API
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"

  Scenario: Restore a file with default encoding after encoding is changed
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Alice" has deleted file "/textfile1.txt"
    When the administrator adds system config key "encryption.use_legacy_encoding" with value "true" and type boolean using the occ command
    And user "Alice" restores the folder with original path "/textfile1.txt" using the trashbin API
    Then the content of file "/textfile1.txt" for user "Alice" should be "some data"

  Scenario: User overwrites a shared file with legacy encoding after encoding is switched
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Brian" has been created with default attributes and without skeleton files
    And user "Alice" has shared file "textfile1.txt" with user "Brian"
    And the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    Then the content of file "/textfile1.txt" for user "Brian" should be "some data"
    When user "Brian" uploads file with content "some new data" to "/textfile1.txt" using the WebDAV API
    Then the content of file "/textfile1.txt" for user "Alice" should be "some new data"
    And the content of file "/textfile1.txt" for user "Brian" should be "some new data"

  Scenario: User overwrites a shared file with default encoding after encoding is switched
    Given the administrator has added system config key "encryption.use_legacy_encoding" with value "true" and type "boolean"
    And user "Alice" has uploaded file with content "some data" to "/textfile1.txt"
    And user "Brian" has been created with default attributes and without skeleton files
    And user "Alice" has shared file "textfile1.txt" with user "Brian"
    And the administrator has added system config key "encryption.use_legacy_encoding" with value "false" and type "boolean"
    Then the content of file "/textfile1.txt" for user "Brian" should be "some data"
    When user "Brian" uploads file with content "some new data" to "/textfile1.txt" using the WebDAV API
    Then the content of file "/textfile1.txt" for user "Alice" should be "some new data"
    And the content of file "/textfile1.txt" for user "Brian" should be "some new data"