# acms-google-calendar
a-blog cms Ver.2.8.0より拡張アプリ「Google Calendar」を利用できるようになります。 この拡張アプリは「[Google Calendar](https://www.google.com/calendar/about/)」と連携し、お問い合わせフォームなどで送信された内容を任意のGoogle Calendarに登録します。

## ダウンロード
[Google Calendar for a-blog cms](https://github.com/appleple/acms-google-calendar/raw/master/build/GoogleCalendar.zip)

## 準備
次の2つのステップで a-blog cms と [Google Calendar](https://www.google.com/calnedar/about/) を連携します。

1. ClientID JSON の取得
2. ClientID JSON を CMS側に登録

### 1. ClientID JSON の取得
### 2. ClientID JSON を CMS側に登録
a-blog cmsにおいて拡張アプリがHOOK処理を書くことを許可します。
```php
define('HOOK_ENABLE', 1);
```
管理画面 > 拡張アプリより、 Google Calendar をインストールします。インストール完了後は、管理画面 > Google Calnedar より Google Calendar の管理画面に移動します。
