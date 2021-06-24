# encryption
 :lock_with_ink_pen: server side encryption of files
 
 [![Build Status](https://drone.owncloud.com/api/badges/owncloud/encryption/status.svg)](https://drone.owncloud.com/owncloud/encryption)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_encryption&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_encryption)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_encryption&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_encryption)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_encryption&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_encryption)

In order to use this encryption module you need to enable server-side
encryption in the admin settings. Once enabled this module will encrypt
all your files transparently. The encryption is based on AES 256 keys.
The module won't touch existing files, only new files will be encrypted
after server-side encryption was enabled. It is also not possible to
disable the encryption again and switch back to a unencrypted system.
Please read the documentation to know all implications before you decide
to enable server-side encryption.

## The following occ commands are not documented in the official documentation but added here for completness

The values bellow mostly represent internal configuration state and should not be set by the user directly. They are controlled by respective encryption-commands. Change only if you know what you are doing or are debugging.

`config:app:set encryption masterKeyId --value ??`

`config:app:set encryption recoveryKeyId --value ??`

The ID of the respective key. Background: Instead of giving a path to a keyfile (which might be error-prone) an explicit key-id which is part of the key is given. This is also done to accomodate for Keystorages which might not be file-based.

`config:app:set encryption useMasterKey --value 1/0`

Is masterkey encryption enabled?

`config:app:set encryption crypto.engine --value 'internal | hsm'`

Normal ownCloud encryption vs storing decryption-keys in a HSM

`config:app:set encryption recoveryAdminEnabled --value 1/0`

With user-key encryption where only the specific user (not even the admin) can decrypt his data with his password there is an additional option to provide a recovery-key to the admin so he can also decrypt the data if the user leaves the company for example.

`config:app:set encryption userSpecificKey --value 1/0`

Set to 1 if userKey encryption is enabled
