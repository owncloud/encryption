# encryption
 :lock_with_ink_pen: server side encryption of files
 
 [![Build Status](https://travis-ci.org/owncloud/encryption.svg?branch=master)](https://travis-ci.org/owncloud/encryption)

In order to use this encryption module you need to enable server-side
encryption in the admin settings. Once enabled this module will encrypt
all your files transparently. The encryption is based on AES 256 keys.
The module won't touch existing files, only new files will be encrypted
after server-side encryption was enabled. It is also not possible to
disable the encryption again and switch back to a unencrypted system.
Please read the documentation to know all implications before you decide
to enable server-side encryption.
