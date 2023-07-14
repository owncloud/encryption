# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [1.6.1] - 2023-06-27

### Changed

- [#395](https://github.com/owncloud/encryption/issues/395) - Always return an int from Symfony Command execute method
- Minimum core version 10.11, minimum php version 7.4
- Dependencies updated.
- Strings updated.


## [1.6.0] - 2023-03-29

### Changed

- [#389](https://github.com/owncloud/encryption/issues/389) - feat: drop setup of user based encryption
- This version of the encryption app requires core 10.12.0 or later.


## [1.5.3] - 2022-08-01

- Handle the versions in the trashbin for the checksum verify command [#361](https://github.com/owncloud/encryption/issues/361)

## [1.5.2] - 2022-05-25

### Added
- Add increment option to fix-encrypted-version command [#279](https://github.com/owncloud/encryption/issues/279)

## [1.5.1] - 2021-05-28

### Fixed

- Use legacy-encoding setting for HSM also [#269](https://github.com/owncloud/encryption/issues/269)
- `fix-encrypted-version` command restores value to original if no fix is found  [#275](https://github.com/owncloud/encryption/issues/275)
- Determine encryption format correctly when using HSM  [#261](https://github.com/owncloud/encryption/pull/261)

## [1.5.0] - 2021-03-11

### Added

- Add path option to FixEncryptedVersion command [#218](https://github.com/owncloud/encryption/pull/218)
- Make encryption repair for file and folder [#4276](https://github.com/owncloud/enterprise/issues/4276)

### Changed

- Use PHP's built-in hash_hkdf function [#215](https://github.com/owncloud/encryption/pull/215)
- Unnecessary file size overhead (binary instead of base64) [#210](https://github.com/owncloud/encryption/issues/210)
- Code needs updating due to core icewind/streams 0.7.2 [#198](https://github.com/owncloud/encryption/issues/198)

### Fixed

- Prevent command encryption:fix-encrypted-version from printing file binary data [#226](https://github.com/owncloud/encryption/pull/226)

## 1.4.0 - 2019-09-02

### Added

- `encryption:fixencryptedversion` command to address issues related to encrypted versions  [#115](https://github.com/owncloud/encryption/pull/115)

### Changed

- Improved wording for several user/administrator interactions [#21](https://github.com/owncloud/encryption/pull/21) [#117](https://github.com/owncloud/encryption/pull/117)

### Fixed

- Issues with recreating masterkeys when HSM is used [#128](https://github.com/owncloud/encryption/pull/128)


[Unreleased]: https://github.com/owncloud/encryption/compare/v1.6.1...HEAD
[1.6.1]: https://github.com/owncloud/encryption/compare/v1.6.0...v1.6.1
[1.6.0]: https://github.com/owncloud/encryption/compare/v1.5.3...v1.6.0
[1.5.3]: https://github.com/owncloud/encryption/compare/v1.5.2...v1.5.3
[1.5.2]: https://github.com/owncloud/encryption/compare/v1.5.1...v1.5.2
[1.5.1]: https://github.com/owncloud/encryption/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/owncloud/encryption/compare/v1.4.0...v1.5.0
