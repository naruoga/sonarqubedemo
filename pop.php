<?php
/****
  写メールBBS by ToR 2002/09/25
                     http://php.s3.to
　サムネイル版　※PHPにGDオプションが必要です
  config.phpにてメールサーバの設定をしてください。

  メール投稿型の掲示板です。添付画像に対応してます。
  専用のメールアドレスを用意した方がいいです。

  mailbbs.php 表示用
  pop.php     受信用
  thumb.php   サムネイル作成用
  htmltemplete.inc   HTMLテンプレート用
  mail.cgi           ログファイル
  mailbbs_pc.html    PC表示テンプレート
  mailbbs_i.html     携帯表示テンプレート
  mailbbs_admin.html 管理表示テンプレート
  config.php   設定用
  riyou.html   投稿方法解説ページ（各自編集してください)
*/
//10/29 v1.6 サイズオーバー時も空ログ記録してた
//12/17 v1.7 PlayOnline Mailerの添付に対応（ファイル名取得
//03/01/06 v1.8 ez.php更新。広告削除機能追加。
//03/01/14 v1.81 2件同時受信の時前のファイル名が残る為$attach = "";追加
//03/01/18 v1.9 添付メールのみ記録する設定追加
//03/01/25 v2.0 バウンダリに正規表現文字があっても化けないようにした
//03/02/05 v2.1 サーバ接続回りを変更
//03/02/07 v2.1 サムネイル時のファイル名修正
//03/02/13 v2.2 日付を取り込み時刻では無く、ヘッダにある日付に変更
//03/07/17 v2.3 非出力に変更
//03/07/24 v2.4 更新後headerでジャンプ、本文文字制限
//03/09/25 v2.51 2行に渡る件名、ファイル名の取得。連続投稿制限
//04/01/06 v2.6 先頭にmb_追加（4.3.4対策）
//04/03/04 v2.9 ログ書き込み判別修正
//04/04/11 v3.0 てんぷっレート対応
//04/08/02 v3.12 mb_convert_encodingをauto→JIS,SJIS, 長いファイル名
//06/08/02 v3.2 同時受信でファイル名重複バグ、au携帯判別バグ
//07/11/28 v3.4 Yahoo、Gmailの日付、From取得バグ、拡張子を取得
//08/10/17 v3.5 Gmailモバイル用修正
//09/10/12 v3.6 Subject複数行修正。本文禁止ワード追加
//14/10/21 v4.0 本文の冒頭末尾の改行削除
/*-----------------*/
require_once("config.php");
require_once("thumb.php");

// mb_関数が使えない場合はhttp://www.spencernetwork.org/にて漢字コード変換(簡易版)を入手する事
if (file_exists("jcode-LE.php")) require_once("jcode-LE.php");
/*-----------------*/
//文字化け対策
if (function_exists("mb_internal_encoding")) {
  mb_internal_encoding("SJIS");
  mb_language("Japanese");
}
$sock = fsockopen($host, 110, $err, $errno, 10) or die("ｻｰﾊﾞｰに接続できません");
$buf = fgets($sock, 512);
if(substr($buf, 0, 3) != '+OK') die($buf);
$buf = _sendcmd("USER $user");
$buf = _sendcmd("PASS $pass");
$data = _sendcmd("STAT");//STAT -件数とサイズ取得 +OK 8 1234
sscanf($data, '+OK %d %d', $num, $size);
if ($num == "0") {
  $buf = _sendcmd("QUIT"); //バイバイ
  fclose($sock);
  header("Location: $jump");
  exit;
}
// 件数分
for($i=1;$i<=$num;$i++) {
  $line = _sendcmd("RETR $i");//RETR n -n番目のメッセージ取得（ヘッダ含）
  while (!preg_match("/^\.\r\n/",$line)) {//EOFの.まで読む
    $line = fgets($sock,512);
    $dat[$i].= $line;
  }
  $data = _sendcmd("DELE $i");//DELE n n番目のメッセージ削除
}
$buf = _sendcmd("QUIT"); //バイバイ
fclose($sock);

$lines = array();
$lines = @file($log);
$write2 = false;

for($j=1;$j<=$num;$j++) {
  $write = true;
  $subject = $from = $text = $atta = $part = $attach = $filename = "";
  list($head, $body) = mime_split($dat[$j]);
  // 日付の袖しつ
  preg_match("/\nDate:[ \t]*([^\r\n]+)/i", $head, $datereg);
  $now = strtotime($datereg[1]);
  if ($now == -1) $now = time();
  $head = preg_replace("/\r\n? /", "", $head);
  // サブジェクトの抽出
  if (preg_match("/\nSubject:[ \t]*(.+)/i", $head, $subhead)) {
    $subreg = preg_split("/[\r\n][A-Za-z-]+: /",$subhead[1]);
    $subject = str_replace("\n","", $subreg[0]);
    while (preg_match("/(.*)=\?iso-2022-jp\?B\?([^?]+)\?=(.*)/i",$subject,$regs)) {//MIME Bﾃﾞｺｰﾄﾞ
      $subject = $regs[1].base64_decode($regs[2]).$regs[3];
    }
    while (preg_match("/(.*)=\?iso-2022-jp\?Q\?([^?]+)\?=(.*)/i",$subject,$regs)) {//MIME Qﾃﾞｺｰﾄﾞ
      $subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
    }
    while (preg_match("/(.*)=\?utf-8\?B\?([^?]+)\?=(.*)/i",$subject,$regs)) {//iphone?
      $subject = $regs[1].base64_decode($regs[2]).$regs[3];
    }
    $subject = convert($subject);
    $subject = htmlspecialchars($subject, ENT_QUOTES, "SJIS");
    // 拒否件名
    foreach ($deny_subj as $dsubj) {
      if (stristr($subject, $dsubj)) $write = false;
    }
  }
  // 送信者アドレスの抽出
  if (preg_match("/\nFrom:[ \t]*([^\r\n]+)/i", $head, $freg)) {
    $from = addr_search($freg[1]);
  } elseif (preg_match("/\nReply-To:[ \t]*([^\r\n]+)/i", $head, $freg)) {
    $from = addr_search($freg[1]);
  } elseif (preg_match("/\nReturn-Path:[ \t]*([^\r\n]+)/i", $head, $freg)) {
    $from = addr_search($freg[1]);
  }
  // 拒否アドレス
  foreach ($deny_from as $dfrom) {
    if (stristr($from, $dfrom)) $write = false;
  }

  // マルチパートならばバウンダリに分割
  if (preg_match("#\nContent-type:.*multipart/#i",$head)) {
    preg_match('/boundary="([^"]+)"/i', $head, $boureg);
    $body = str_replace($boureg[1], urlencode($boureg[1]), $body);
    $part = preg_split("/\r\n--".urlencode($boureg[1])."-?-?/",$body);
    /*
    if (eregi('boundary="([^"]+)"', $body, $boureg2)) {//multipart/altanative
      $body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
      $body = eregi_replace("\r\n--".urlencode($boureg[1])."-?-?\r\n","",$body);
      $part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
    }
    */
  } else {
    $part[0] = $dat[$j];// 普通のテキストメール
  }
  foreach ($part as $multi) {
    list($m_head, $m_body) = mime_split($multi);
    $m_body = preg_replace("/\r\n\.\r\n$/", "", $m_body);
    if (!preg_match("/\nContent-type: *([^;\n]+)/i", $m_head, $type)) continue;
    list($main, $sub) = explode("/", $type[1]);
    // 本文をデコード
    if (strtolower($main) == "text" && trim($text) == '') {
      if (preg_match("#\nContent-Transfer-Encoding:.*base64#i", $m_head)) 
        $m_body = base64_decode($m_body);
      if (preg_match("#\nContent-Transfer-Encoding:.*quoted-printable#i", $m_head)) 
        $m_body = quoted_printable_decode($m_body);
      $text = convert($m_body);
      if ($sub == "html") $text = strip_tags($text);
      // 電話番号削除
      $text = preg_replace("/([0-9]{11})|([0-9\-]{13})/", "", $text);
      // 下線削除
      $text = preg_replace("/[_]{25,}/", "", $text);
       // mac削除
      $text = preg_replace("#\nContent-type: multipart/appledouble;\sboundary=(.*)#i","",$text);
      // 広告等削除
      if (is_array($word)) {
        $text = str_replace($word, "", $text);
      }
      // 拒否本文
      if (is_array($deny_text)) {
        foreach ($deny_text as $dtext) {
          if (stristr($text, $dtext)) $write = false;
        }
      }
      // 文字数オーバー
      if (strlen($text) > $maxtext) $text = substr($text, 0, $maxtext)."...";
      $text = str_replace(">","&gt;",$text);
      $text = str_replace("<","&lt;",$text);
      $text = str_replace("\r\n", "\r",$text);
      $text = str_replace("\r", "\n",$text);
      $text = preg_replace("/\n{2,}/", "\n\n", $text);
      $text = str_replace("\n", "<br>", trim($text));
    }
    // ファイル名を抽出
    if (preg_match("/name=\"?([^;\"\n\s]+)\"?/i",$m_head, $filereg)) {
      $filename = preg_replace("/[\t\r\n]/", "", $filereg[1]);
      while (preg_match("/(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)/i",$filename,$regs)) {
        $filename = $regs[1].base64_decode($regs[2]).$regs[3];
        $filename = convert($filename);
      }
      $ext = substr($filename,strrpos($filename,".")+1,strlen($filename)-strrpos($filename,"."));
    }
    // 添付データをデコードして保存
    if (preg_match("/\nContent-Transfer-Encoding:.*base64/i", $m_head) && preg_match("/$subtype/", $sub)) {
      $tmp = base64_decode($m_body);
      if (!$ext) $ext = $sub;
      if (!$original || !$filename) $filename = $now.".".$ext;
      if (strlen($tmp) < $maxbyte && !preg_match("/$viri/", $filename) && $write) {
        $fp = fopen($tmpdir.$filename, "w");
        fputs($fp, $tmp);
        fclose($fp);
        $attach = $filename;
        //サムネイル
        if (preg_match("/\.jpe?g$|\.png$/i",$filename)) {
          $size = getimagesize($tmpdir.$filename);
          if ($size[0] > $W || $size[1] > $H) {
            thumb_create($tmpdir.$filename,$W,$H,$thumb_dir);
          }
        }
      } else {
        $write = false;
      }
    }
  }
  if ($imgonly && $attach=="") $write = false;
  list($old,$otime,$osubj,$ofrom,,) = explode("<>", $lines[0]);
  // 連続投稿
  if ($from == $ofrom && $now - $otime < $wtime) $write = false;
  $id = $old + 1;
  $subject = trim($subject);
  if($subject=="") $subject = $nosubject;
  $line = "$id<>$now<>$subject<>$from<>$text<>$attach<>\n";

  if ($write) {
    array_unshift($lines, $line);
    $write2 = true;
  } elseif (file_exists($tmpdir.$filename)) {
    @unlink($tmpdir.$filename);
  }
}

// ログ最大行処理
if (count($lines) > $maxline) {
  for ($k=count($lines)-1; $k>=$maxline; $k--) {
    list($id,$tim,$sub,$fro,$tex,$at,) = explode("<>", $lines[$k]);
    if (file_exists($tmpdir.$at)) @unlink($tmpdir.$at);
    $lines[$k] = "";
  }
}
//ログ書き込み
if ($write2) {
  $fp = fopen($log, "w");
  flock($fp, LOCK_EX);
  fputs($fp, implode('', $lines));
  fclose($fp);
}

/* コマンドー送信！！ */
function _sendcmd($cmd) {
  global $sock;
  fputs($sock, $cmd."\r\n");
  $buf = fgets($sock, 512);
  if(substr($buf, 0, 3) == '+OK') {
    return $buf;
  } else {
    die($buf);
  }
  return false;
}

/* ヘッダと本文を分割する */
function mime_split($data) {
  $part = preg_split("/\r\n\r\n/", $data, 2);
  $part[1] = preg_replace("/\r\n[\t ]+/", " ", $part[1]);

  return $part;
}
/* メールアドレスを抽出する */
function addr_search($addr) {
  if (preg_match("/[-!#$%&\'*+\.\/0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\.\/0-9=?A-Z^_`a-z{|}~]+/", $addr, $fromreg)) {
    return $fromreg[0];
  } else {
    return false;
  }
}
/* 文字コードコンバートauto→SJIS */
function convert($str) {
  if (function_exists('mb_convert_encoding')) {
    return mb_convert_encoding($str, "SJIS", "JIS,UTF-8,SJIS");
  } elseif (function_exists('JcodeConvert')) {
    return JcodeConvert($str, 0, 2);
  }
  return true;
}

header("Location: $jump");
//echo '<META HTTP-EQUIV="Refresh" CONTENT="0;URL='.$jump.'">';
?>
