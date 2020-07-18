# acms-google-calendar
a-blog cms Ver.2.8.0より拡張アプリ「Google Calendar」を利用できます。 この拡張アプリは「[Google Calendar](https://www.google.com/calendar/about/)」と連携し、お問い合わせフォームなどで送信された内容を任意のGoogle Calendarに登録します。

## ダウンロード
[Google Calendar for a-blog cms](https://github.com/appleple/acms-google-calendar/raw/master/build/GoogleCalendar.zip)

## 準備
次のステップで a-blog cms と [Google Calendar](https://www.google.com/calnedar/about/) を連携します。

1. ClientID JSON の取得
2. ClientID JSON を CMS側に登録

### 1. ClientID JSON の取得
[Google API Console](https://console.developers.google.com/)にアクセスし、認証情報を作成します。
下の画像の画面で OAuthクライアントID を選択し、OAuthクライアントIDを作成します。
<img src="./images/select_key_type.png" />

OAuth クライアントID 作成時に設定しなければならない項目は、アプリケーションの種類、名前、承認済みのリダイレクトURLです。
ここでは、下の画像のようにアプリケーションの種類を「ウェブアプリケーション」、承認済みのリダイレクトURLは「ドメイン名/bid/（現在使用しているブログのBID）/admin/app_google_calendar_callback/」と設定します。名前は任意のもので構いません。
<img src="./images/setting_oauth_json.png" />

作成が完了したら、画像の赤丸で囲まれた場所をクリックします。ここで、JSONファイルがダウンロードされますので、このファイルを ablog cms が動いているサーバにアップロードします(ブラウザからアクセスできないドキュメントルートより上の階層にアップロードすることが望ましいです)。

### 2. ClientID JSON を CMS側に登録
a-blog cmsにおいて拡張アプリがHOOK処理を書くことを許可します。
config.server.php の設定を変更します。
```php
define('HOOK_ENABLE', 1);
```

extension/plugins に[Google Calendar for a-blog cms](https://github.com/appleple/acms-google-calendar/raw/master/build/GoogleCalendar.zip)をアップロードし、管理画面 > 拡張アプリより、 Google Calendar をインストールします。インストール完了後、管理画面 > Google Calnedar より Google Calendar の管理画面に移動します(下の図)。ここで、Client ID Key Location に先ほどアップロードしたJSONファイルが存在する場所を表す絶対パスを入力し、「認証」をクリックします。
<img src="./images/acms_setting_oauth.png" />
