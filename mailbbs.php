<?php
/* �ʃ��[��BBS �\���X�N���v�g
 *  ?mode=admin �ō폜���[�h
 * config.php�Őݒ肵�Ă�������
 */
require_once("config.php");
require_once("htmltemplate.inc");

//PHP4.1.0�ȉ��̏ꍇ
if(phpversion()<"4.1.0"){
    $_GET	= $HTTP_GET_VARS;
    $_POST	= $HTTP_POST_VARS;
    $_SERVER	= $HTTP_SERVER_VARS;
}
$env = "pc";
// �U�蕪��
$ua = explode("/", $_SERVER['HTTP_USER_AGENT']);
if (strstr($ua[0], "DoCoMo")) {
  $env = "i"; // DoCoMo
} elseif (preg_match("#^UP.Browser|KDDI#i", $ua[0])) {
  $env = "i"; // au
} elseif (preg_match("#^J-PHONE|Vodafone|SoftBank|MOT#i", $ua[0])) {
  $env = "i"; // Vodafone
}

// �����̃X�N���v�g��
$arg['self'] = basename($_SERVER['SCRIPT_NAME']);
// ���O�ǂݍ���
$lines = file($log);

// �ҏW����
if ($_POST['act'] == "edit") {
  $find = false;
  for ($i=0; $i<count($lines); $i++) {
    list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$i]);
    if ($_POST['id'] == $id) {
      if ($_POST['pass'] == $delpass || $_POST['pass'] == $from) {
        $find = true;
        if (!checkdate($_POST['m'], $_POST['d'], $_POST['y'])) die("���t�𐳂������͂��Ă�������");
        $ptime = mktime($_POST['h'], $_POST['i'], 0, $_POST['m'], $_POST['d'], $_POST['y']);
        $subject = $_POST['subject'];
        $body = $_POST['body'];
        if (get_magic_quotes_gpc()) {
          $subject = stripslashes($subject);
          $body = stripslashes($body);
        }
        $subject = str_replace(">","&gt;",$subject);
        $subject = str_replace("<","&lt;",$subject);
        $body = str_replace(">","&gt;",$body);
        $body = str_replace("<","&lt;",$body);
        $body = str_replace("\r\n", "\r",$body);
        $body = str_replace("\r", "\n",$body);
        $body = preg_replace("/\n{2,}/", "\n\n", $body);
        $body = str_replace("\n", "<br>", $body);

        if ($_POST['tmpdel'] == 1) {
          if (file_exists($tmpdir.$att)) {
            @unlink($tmpdir.$att);
            $filename = substr($att,0,strrpos($att,"."));
            @unlink($thumb_dir.$filename.".jpg");
          }
          $att = "";
        }
        // ���O�̃t�H�[�}�b�g�ɐ��`
        $lines[$i] = "$id<>$ptime<>$subject<>$from<>$body<>$att<>\n";
        break;
      }
      else {
        die("�p�X���[�h���Ⴂ�܂�");
      }
    }
  }
  if ($find) {
    $fp = fopen($log, "w");
    flock($fp, LOCK_EX);
    fputs($fp, implode('', $lines));
    fclose($fp);
    echo "<br>�ҏW���������܂���";
  }else {
    die("<br>�������݂Ɏ��s���܂���");
  }
}
// �폜����
if ($_POST['del']) {
  $find = false;
  for ($i=0; $i<count($lines); $i++) {
    list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$i]);
    if ($_POST['del'][$id] == "on") {
      if($_POST['pass'] == $delpass || $_POST['pass'] == $from) {
        $lines[$i] = "";
        $find = true;
        if ($att !="" && file_exists($tmpdir.$att)) {
          @unlink($tmpdir.$att);
          $filename = substr($att,0,strrpos($att,"."));
          @unlink($thumb_dir.$filename.".jpg");
        }
      }
    }
  }
  if ($find) {
    $fp = fopen($log, "w");
    flock($fp, LOCK_EX);
    fputs($fp, implode('', $lines));
    fclose($fp);
  }
  else {
    $arg['err'] = "���A�h����v���܂���I�I<br>���Ƀ`�F�b�N�����A���e���̃��A�h����͂��ĉ�����<br>";
  }
  $lines = file($log);
  $_GET['mode'] = "admin";
}
// �Ǘ����[�h
if ($_GET['mode'] == "admin") {
  $env = "admin";
}
// �ҏW���[�h
if ($_GET['mode'] == "edit") {
  $find = false;
  for ($i=0; $i<count($lines); $i++) {
    list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$i]);
    if ($_GET['id'] == $id) {
      $find = true;
      break;
    }
  }
  if (!$find) die("�ΏۋL����������܂���ł���");

  $subject = htmlspecialchars($subject);
  $body = str_replace("<br>", "\n", $body);
  $self = $arg['self'];
  if ($att!="") $tmp = $tmpdir.$att;
  $arg = compact('id','ptime','subject','from','body','att','tmp','self');

  $dt = getdate($ptime);
  for ($i=1970;$i<=date("Y")+1;$i++) {
    $sel = ($i == $dt['year']) ? " selected" : "";
    $arg['year'][] = array('num' => $i, 'sel' => $sel);
  }
  for ($i=1;$i<=12;$i++) {
    $sel = ($i == $dt['mon']) ? " selected" : "";
    $arg['mon'][] = array('num' => $i, 'sel' => $sel);
  }
  for ($i=1;$i<=31;$i++) {
    $sel = ($i == $dt['mday']) ? " selected" : "";
    $arg['day'][] = array('num' => $i, 'sel' => $sel);
  }
  for ($i=0;$i<=59;$i++) {
    $hsel = ($i == $dt['hours']) ? " selected" : "";
    $arg['hour'][] = array('num' => $i, 'sel' => $hsel);
    $msel = ($i == $dt['minutes']) ? " selected" : "";
    $arg['min'][] = array('num' => $i, 'sel' => $msel);
  }

  HtmlTemplate::t_include("mailbbs_edit.html",$arg);
  exit;
}
$st = (!$_GET['page']) ? 0 : $_GET['page'];
$pname = "page_def_".$env;
$page_def = $$pname;
// ���[�v
for ($i=$st; $i<$st+$page_def; $i++) {
  if ($lines[$i] == "") break;
  $imgsrc = $body = $subject = $row = "";
  list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$i]);

  $row['id'] = $id;
  $row['date'] = date($format, $ptime);
  $row['subject'] = $subject;
  $row['from'] = $from;
  $row['size'] = intval(@filesize($tmpdir.$att) / 1024);

  // �{��E-Mail�������N
  // mb�n�֐����g����ꍇ
  if (function_exists("mb_eregi_replace")) {
    mb_regex_encoding("SJIS");
    $body = mb_eregi_replace("([-a-z0-9_.]+@[-a-z0-9_.]+)", "<a href='mailto:\\1'>\\1</a>", $body);
  } else {
    $body = eregi_replace("([-a-z0-9_.]+@[-a-z0-9_.]+)", "<a href='mailto:\\1'>\\1</a>", $body);
  }
  // URL�����N
  $body = ereg_replace("(https?|ftp)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)","<a href=\"\\1\\2\" target=_top>\\1\\2</a>",$body);
  $row['body'] = $body;
  $row['filename'] = $att;
  $row['url'] = $tmpdir.rawurlencode($att);
  $row['tail'] = strtoupper(substr($att,strrpos($att,".")+1,strlen($att)-strrpos($att,".")));
  $row['sam_url'] = $row['url'];
  $row['sam_size'] = $row['size'];

  // �摜�����鎞
  if (eregi("\.(gif|jpe?g|png|bmp)$",$att)) {
    $fname = substr($att,0,strrpos($att,"."));
    // �T���l�C�������鎞
    if (file_exists($thumb_dir.$fname.".jpg")) {
      $row['sam'] = true;
      $row['sam_url'] = $thumb_dir.rawurlencode($fname).".jpg";
      $row['sam_size'] = intval(@filesize($thumb_dir.$fname.".jpg") / 1024);
    // �ʏ�摜
    }else{
      $row['img'] = true;
    }
  }
  elseif (eregi("\.amc$", $att)) {
    $row['noimg'] = true;
    $byte = @filesize($tmpdir.$att);
    $row['amc'] = <<<AMC
<br>
<object data="$tmpdir$att" type="application/x-mpeg" copyright="yes" standby="�_�E�����[�h">
<param name="disposition" value="devdl1q" valuetype="data" />
<param name="size" value="$byte" valuetype="data" />
<param name="title" value="$att" valuetype="data" />
</object>
AMC;
  }//����ȊO�̓Y�t
  elseif (trim($att)!="") {
    $row['noimg'] = true;
  }
  $arg['main'][] = $row;
}
$prev = $st - $page_def;
$next = $st + $page_def;
if ($_GET['mode'] == "admin") $mode = "&mode=admin";
//�y�[�W
if ($_GET['page']) {
  $arg['prev'] = $arg['self'].'?page='.$prev.$mode;
}
if ($next < count($lines)) {
  $arg['next'] = $arg['self'].'?page='.$next.$mode;
}
//print_r($arg);
HtmlTemplate::t_include("mailbbs_".$env.".html",$arg);
?>
