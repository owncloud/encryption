# ownCloud Encryption

<!-- OSPO-managed README | Generated: 2026-04-16 | v2 -->

[![License](https://img.shields.io/badge/License-AGPL--3.0-blue.svg)](LICENSE) [![ownCloud OSPO](https://img.shields.io/badge/OSPO-ownCloud-blue)](https://kiteworks.com/opensource) [![Docker Hub](https://img.shields.io/docker/pulls/owncloud)](https://hub.docker.com/r/owncloud/server)

An ownCloud Classic (OC10) app that provides transparent server-side encryption of files using AES-256 keys. Once enabled in the admin settings, all newly uploaded files are encrypted at rest. The module supports master key encryption, recovery keys, and optional HSM (Hardware Security Module) integration for key storage. Per-user key encryption is deprecated and no longer recommended — see the [server release notes](https://doc.owncloud.com/docs/next/server_release_notes.html#deprecation-note-for-user-key-storage-encryption).

## Getting Started

Enable server-side encryption via the ownCloud admin settings or the command line:

```bash
sudo -u www-data php occ encryption:enable
sudo -u www-data php occ encryption:select-encryption-type masterkey
sudo -u www-data php occ encryption:encrypt-all
```

**Important:** Read the documentation thoroughly before enabling. To reverse encryption, run `occ encryption:decrypt-all` (decrypts all files and disables encryption), followed by `occ encryption:disable`. Requires OpenSSL 1.1.x: OpenSSL 3.x retired several legacy ciphers this module depends on, which can break access to already-encrypted files. If this happens, re-enable the legacy ciphers in your OpenSSL 3.x config -- see the "Providers" section of the OpenSSL 3.0 wiki for the workaround.

## Documentation

- [ownCloud Encryption Documentation](https://doc.owncloud.com/server/latest/admin_manual/configuration/files/encryption/)
- [ownCloud Server Admin Manual](https://doc.owncloud.com/server/latest/admin_manual/)

## Part of ownCloud Classic (OC10)

This app extends [ownCloud Server](https://github.com/owncloud/core) with server-side file encryption. It is shipped as part of the [ownCloud Server Docker image](https://hub.docker.com/r/owncloud/server).

## Community & Support

**[Star](https://github.com/owncloud/encryption)** this repo and **Watch** for release notifications!

- [ownCloud Website](https://owncloud.com)
- [Community Discussions](https://github.com/orgs/owncloud/discussions)
- [Matrix Chat](https://app.element.io/#/room/#owncloud:matrix.org)
- [Documentation](https://doc.owncloud.com)
- [Enterprise Support](https://owncloud.com/contact-us/)
- [OSPO Home](https://kiteworks.com/opensource)

## Contributing

We welcome contributions! Please read the [Contributing Guidelines](CONTRIBUTING.md)
and our [Code of Conduct](CODE_OF_CONDUCT.md) before getting started.

### Workflow

- **Rebase Early, Rebase Often!** We use a rebase workflow. Always rebase on the target branch before submitting a PR.
- **Dependabot**: Automated dependency updates are managed via Dependabot. Review and merge dependency PRs promptly.
- **Signed Commits**: All commits **must** be PGP/GPG signed. See [GitHub's signing guide](https://docs.github.com/en/authentication/managing-commit-signature-verification).
- **DCO Sign-off**: Every commit must carry a `Signed-off-by` line:
  ```
  git commit -s -S -m "your commit message"
  ```
- **GitHub Actions Policy**: Workflows may only use actions that are (a) owned by `owncloud`, (b) created by GitHub (`actions/*`), or (c) verified in the GitHub Marketplace.

## Translations

Help translate this project on Transifex:
**<https://explore.transifex.com/owncloud-org/owncloud/>**

Please submit translations via Transifex -- do not open pull requests for translation changes.

## Security

**Do not open a public GitHub issue for security vulnerabilities.**

Report vulnerabilities at **<https://security.owncloud.com>** -- see [SECURITY.md](SECURITY.md).

Bug bounty: [YesWeHack ownCloud Program](https://yeswehack.com/programs/owncloud-bug-bounty-program)

## License

This project is licensed under the [AGPL-3.0](LICENSE).

## About the ownCloud OSPO

The [Kiteworks Open Source Program Office](https://kiteworks.com/opensource), operating under
the [ownCloud](https://owncloud.com) brand, launched on May 5, 2026, to steward the open source
ecosystem around ownCloud's products. The OSPO ensures transparent governance, license compliance,
community health, and sustainable collaboration between the open source community and
[Kiteworks](https://www.kiteworks.com), which acquired ownCloud in 2023.

- **OSPO Home**: <https://kiteworks.com/opensource>
- **GitHub**: <https://github.com/owncloud>
- **ownCloud**: <https://owncloud.com>

For questions about the OSPO or licensing, contact ospo@kiteworks.com.

### License Migration to Apache 2.0

The OSPO is driving a strategic relicensing of ownCloud repositories toward the
[Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0), following
the [Apache Software Foundation's third-party license policy](https://www.apache.org/legal/resolved.html).

Individual repositories will migrate as their audit is completed. The LICENSE file
in each repo reflects its **current** license status (not the target).

**Current license: AGPL-3.0** (Category X per Apache policy -- cannot be included in Apache-2.0 works).

Migration prerequisites for this repository:

- **CLA/DCO coverage**: All past contributors must have signed agreements permitting relicensing
- **Copyleft dependency audit**: All AGPL/GPL dependencies must be replaced or isolated
- **KDE heritage review**: Any code with KDE-era copyrights requires legal analysis
- **Complete relicensing**: AGPL-3.0 is a strong copyleft license; migration requires full relicensing of all files, not just a header change
