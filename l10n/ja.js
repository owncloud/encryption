OC.L10N.register(
    "encryption",
    {
    "Missing recovery key password" : "復旧キーのパスワードがありません",
    "Please repeat the recovery key password" : "復旧キーのパスワードをもう一度入力してください",
    "Repeated recovery key password does not match the provided recovery key password" : "入力された復旧キーのパスワードが一致しません。",
    "Recovery key successfully enabled" : "復旧キーを有効化しました",
    "Could not enable recovery key. Please check your recovery key password!" : "復旧キーを有効にできませんでした。リカバリ用のキーのパスワードを確認してください！",
    "Recovery key successfully disabled" : "復旧キーを無効化しました",
    "Could not disable recovery key. Please check your recovery key password!" : "復旧キーを無効化できませんでした。リカバリ用のキーのパスワードを確認してください！",
    "Missing parameters" : "パラメータが不足しています",
    "Please provide the old recovery password" : "古い復旧キーのパスワードを入力してください",
    "Please provide a new recovery password" : "新しい復旧キーのパスワードを入力してください",
    "Please repeat the new recovery password" : "新しい復旧キーのパスワードをもう一度入力してください",
    "Password successfully changed." : "パスワードを変更しました。",
    "Could not change the password. Maybe the old password was not correct." : "パスワードを変更できませんでした。古いパスワードが間違っているかもしれません。",
    "Recovery Key disabled" : "復旧キーが無効になりました。",
    "Recovery Key enabled" : "復旧キーが有効になりました。",
    "Could not enable the recovery key, please try again or contact your administrator" : "復旧キーを有効化できませんでした。もう一度試してみるか、管理者に問い合わせてください。",
    "Could not update the private key password." : "秘密鍵のパスワードを更新できませんでした。",
    "The old password was not correct, please try again." : "古いパスワードが一致しませんでした。もう一度入力してください。",
    "The current log-in password was not correct, please try again." : "ログインパスワードが一致しませんでした。もう一度入力してください。",
    "Private key password successfully updated." : "秘密鍵のパスワードが更新されました。",
    "You need to migrate your encryption keys from the old encryption (ownCloud <= 8.0) to the new one. Please run 'occ encryption:migrate' or contact your administrator" : "古い暗号化方式(ownCloud 8.0以前)から新しい方式へ、暗号化キーを移行する必要があります。'occ encryption:migrate'を実行するか、管理者に問い合わせてください。",
    "Invalid private key for Encryption App. Please update your private key password in your personal settings to recover access to your encrypted files." : "暗号化アプリの無効なプライベートキーです。あなたの暗号化されたファイルへアクセスするために、個人設定から秘密鍵のパスワードを更新してください。",
    "Encryption App is enabled, but your keys are not initialized. Please log-out and log-in again." : "暗号化アプリは有効ですが、あなたの暗号化キーは初期化されていません。ログアウトした後に、再度ログインしてください。",
    "Encryption App is enabled and ready" : "暗号化アプリは有効になっており、準備が整いました",
    "Bad Signature" : "不正な署名",
    "Missing Signature" : "署名が存在しません",
    "one-time password for server-side-encryption" : "サーバーサイド暗号化のワンタイムパスワード",
    "Can not decrypt this file, probably this is a shared file. Please ask the file owner to reshare the file with you." : "このファイルを復号できません、共有ファイルの可能性があります。ファイルの所有者にお願いして、ファイルを共有しなおしてもらってください。",
    "Can not read this file, probably this is a shared file. Please ask the file owner to reshare the file with you." : "このファイルを読み取ることができません、共有ファイルの可能性があります。ファイルの所有者にお願いして、ファイルを共有しなおしてもらってください。",
    "Hey there,\n\nthe admin enabled server-side-encryption. Your files were encrypted using the password '%s'.\n\nPlease login to the web interface, go to the section 'ownCloud basic encryption module' of your personal settings and update your encryption password by entering this password into the 'old log-in password' field and your current login-password.\n\n" : "こんにちは！\n\n管理者がサーバーサイド暗号化を有効にしました。'%s'というパスワードであなたのファイルが暗号化されました。\n\nWeb画面からログインして、個人設定画面の'ownCloud 基本暗号化モジュール' セクションを開き、暗号化パスワードの更新をお願いします。 '旧ログインパスワード'部分に上記パスワードを入力し、現在のログインパスワードで更新します。\n",
    "The share will expire on %s." : "共有は %s で有効期限が切れます。",
    "Cheers!" : "それでは！",
    "Hey there,<br><br>the admin enabled server-side-encryption. Your files were encrypted using the password <strong>%s</strong>.<br><br>Please login to the web interface, go to the section \"ownCloud basic encryption module\" of your personal settings and update your encryption password by entering this password into the \"old log-in password\" field and your current login-password.<br><br>" : "こんにちは！<br><br>管理者がサーバーサイド暗号化を有効にしました。<strong>%s</strong>というパスワードであなたのファイルが暗号化されました。<br><br>Web画面からログインして、個人設定画面の\"ownCloud 基本暗号化モジュール\"のセクションを開き、暗号化パスワードの更新をお願いします。 \"旧ログインパスワード”部分に上記パスワードを入力し、現在のログインパスワードで更新します。<br><br>",
    "Default encryption module" : "既定の暗号化モジュール",
    "Encryption App is enabled but your keys are not initialized, please log-out and log-in again" : "暗号化アプリは有効ですが、あなたの暗号化キーは初期化されていません。ログアウトした後に、再度ログインしてください",
    "Encryption type: Master Key" : "暗号方式: マスターキー",
    "Encryption type: User Specific Key" : "暗号方式: ユーザーごとの鍵",
    "Please select an encryption option" : "暗号化のオプションを選択してください",
    "Master Key" : "マスターキー",
    "Permanently select this mode" : "常にこのモードを選択する",
    "Encrypt the home storage" : "メインストレージを暗号化する",
    "Enabling this option encrypts all files stored on the main storage, otherwise only files on external storage will be encrypted" : "このオプションを有効にすると、メインストレージのファイル全てが暗号化されます。無効にすると、外部ストレージのファイルだけが暗号化されます。",
    "Enable recovery key" : "復旧キーを有効にする",
    "Disable recovery key" : "復旧キーを無効にする",
    "The recovery key is an extra encryption key that is used to encrypt files. It allows recovery of a user's files if the user forgets his or her password." : "復旧キーは、ファイルの暗号化に使う特別な暗号化キーです。ユーザーがパスワードを忘れてしまった場合には、リカバリキーを使ってユーザのファイルを復元することができます。",
    "Recovery key password" : "復旧キーのパスワード",
    "Repeat recovery key password" : "復旧キーのパスワードをもう一度入力",
    "Change recovery key password:" : "復旧キーのパスワードを変更:",
    "Old recovery key password" : "古い復旧キーのパスワード",
    "New recovery key password" : "新しい復旧キーのパスワード",
    "Repeat new recovery key password" : "新しい復旧キーのパスワードをもう一度入力",
    "Change Password" : "パスワードを変更",
    "ownCloud basic encryption module" : "ownCloud 基本暗号化モジュール",
    "Your private key password no longer matches your log-in password." : "もはや秘密鍵はログインパスワードと一致しません。",
    "Set your old private key password to your current log-in password:" : "古い秘密鍵のパスワードを現在のログインパスワードに設定:",
    " If you don't remember your old password you can ask your administrator to recover your files." : "古いパスワードを覚えていない場合、管理者に尋ねてファイルを回復することができます。",
    "Old log-in password" : "古いログインパスワード",
    "Current log-in password" : "現在のログインパスワード",
    "Update Private Key Password" : "秘密鍵のパスワードを更新",
    "Enable password recovery:" : "パスワードリカバリを有効にする:",
    "Enabling this option will allow you to reobtain access to your encrypted files in case of password loss" : "このオプションを有効にすると、パスワードを紛失した場合も、暗号化されたファイルに再度アクセスすることができます。",
    "Enabled" : "有効",
    "Disabled" : "無効"
},
"nplurals=1; plural=0;");
