<?php
// 受信メールサーバーの設定
// POP3サーバー
$host = "localhost";
// ユーザーID
$user = "メールuser";
// パスワード
$pass = "メールpass";

// 画像保存ﾃﾞｨﾚｸﾄﾘ（パーミッション777等に変更）
$tmpdir  = "./data/";
// ログファイルの場所（パーミッション666等に変更）
$log     = "./mail.cgi";
// 更新後のジャンプ先（表示スクリプトURL）
$jump    = "http://php.s3.to/php/mailbbs.php";

//表示設定
// 1ページの表示数（通常）
$page_def_pc = 7;
// 1ページの表示数（携帯）
$page_def_i = 7;
// 1ページの表示数（管理）
$page_def_admin = 15;
// 削除パス
$delpass  = "127834";
// 日付のフォーマット http://jp2.php.net/date
$format   = "y/m/d G:i";

// ログ保存件数
$maxline = 100;
// 最大添付量（バイト・1ファイルにつき）※超えるものは保存しない
$maxbyte = 102400; //100KB
// 最大本文文字数（半角で
$maxtext = 1000;
// 対応MIMEサブタイプ（正規表現）Content-Type: image/jpegの後ろの部分。octet-streamは危険かも
$subtype = "gif|jpe?g|png|bmp|pmd|mld|mid|smd|smaf|mpeg|kjx|3gpp";

// 投稿非許可アドレス（ログに記録しない）
$deny_from = array('163.com','bigfoot.com','boss.com');
// 投稿非許可件名（ログに記録しない）
$deny_subj = array('未承諾','広告','ocument','equest','essage','elivery');
// 投稿非許可本文（ログに記録しない）
$deny_text = array('スーパーコピー','http://php.s3.to','http://se-buo');
// 保存しないファイル(正規表現)
$viri = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";

// 本文から削除する文字列
$word[] = "会員登録は無料  充実した出品アイテムなら MSN オークション";
$word[] = "http://auction.msn.co.jp/";
$word[] = "Do You Yahoo!?";
$word[] = "Yahoo! BB is Broadband by Yahoo!";
$word[] = "http://bb.yahoo.co.jp/";
$word[] = "友達と24時間ホットライン「MSN メッセンジャー」、今すぐダウンロード！";
$word[] = "http://messenger.msn.co.jp";

// 添付メールのみ記録する？Yes=1 No=0（本文のみはログに載せない）
$imgonly = 0;
// 件名がないときの題名
$nosubject = "匿名さん";
// 次の秒数以内の同一送信者からの連続投稿禁止（0で連続可）
$wtime = 30;
// 元ファイル名で保存する？Yes=1 No=0（0の場合 時間.拡張子）
$original = 1;

/*-- サムネイル--*/
//これ以上の大きい画像はjpg,pngのサムネイル作成
$W = 140;
$H = 140;
//サムネイル保存ディレクトリ（パーミッション777等に変更）
$thumb_dir = "./data/s/";

/* テンプレート解説
{$self}　　　　　表示スクリプト名（mailbbs.php）
[loop main]　　　記事繰り返しの開始
 ＊以下[loop main]内
 [if main/sam]～[/if]      サムネイル画像がある場合
 [if main/img]～[if]　     画像がある場合
 [if main/noimg]～[/if]    画像以外の添付がある場合
 [if main/filename]～[/if] 添付がある場合
 {$main/id}　　　　記事番号
 {$main/subject}　 件名
 {$main/date}　　　日付
 {$main/url}　　　 画像URL（ディレクトリ＋ファイル名）
 {$main/size}      画像サイズ
 {$main/body}      本文
 {$main/filename}  添付ファイル名
 {$main/tail}      添付拡張子
 {$main/sam_url}　 サムネイル画像URL（ディレクトリ＋ファイル名）
 {$main/sam_size}  サムネイル画像サイズ（KB)
 　＊サムネイルが無い場合は{$main/url}{$main/size}が使用されます
 {$main/amc}　　　 amc用objectタグ
[/loop]           記事の終わり

[if prev]～[/if]  前ページがある場合
{$prev}　前ページへのURL（mailbbs.php?page=x）
[if next]～[/if]　次ページがある場合
{$prev}　次ページへのURL（mailbbs.php?page=x）
*/
?>