<?php
/****
  �ʃ��[��BBS by ToR 2002/09/25
                     http://php.s3.to
�@�T���l�C���Ł@��PHP��GD�I�v�V�������K�v�ł�
  config.php�ɂă��[���T�[�o�̐ݒ�����Ă��������B

  ���[�����e�^�̌f���ł��B�Y�t�摜�ɑΉ����Ă܂��B
  ��p�̃��[���A�h���X��p�ӂ������������ł��B

  mailbbs.php �\���p
  pop.php     ��M�p
  thumb.php   �T���l�C���쐬�p
  htmltemplete.inc   HTML�e���v���[�g�p
  mail.cgi           ���O�t�@�C��
  mailbbs_pc.html    PC�\���e���v���[�g
  mailbbs_i.html     �g�ѕ\���e���v���[�g
  mailbbs_admin.html �Ǘ��\���e���v���[�g
  config.php   �ݒ�p
  riyou.html   ���e���@����y�[�W�i�e���ҏW���Ă�������)
*/
//10/29 v1.6 �T�C�Y�I�[�o�[�����󃍃O�L�^���Ă�
//12/17 v1.7 PlayOnline Mailer�̓Y�t�ɑΉ��i�t�@�C�����擾
//03/01/06 v1.8 ez.php�X�V�B�L���폜�@�\�ǉ��B
//03/01/14 v1.81 2��������M�̎��O�̃t�@�C�������c���$attach = "";�ǉ�
//03/01/18 v1.9 �Y�t���[���̂݋L�^����ݒ�ǉ�
//03/01/25 v2.0 �o�E���_���ɐ��K�\�������������Ă������Ȃ��悤�ɂ���
//03/02/05 v2.1 �T�[�o�ڑ�����ύX
//03/02/07 v2.1 �T���l�C�����̃t�@�C�����C��
//03/02/13 v2.2 ���t����荞�ݎ����ł͖����A�w�b�_�ɂ�����t�ɕύX
//03/07/17 v2.3 ��o�͂ɕύX
//03/07/24 v2.4 �X�V��header�ŃW�����v�A�{����������
//03/09/25 v2.51 2�s�ɓn�錏���A�t�@�C�����̎擾�B�A�����e����
//04/01/06 v2.6 �擪��mb_�ǉ��i4.3.4�΍�j
//04/03/04 v2.9 ���O�������ݔ��ʏC��
//04/04/11 v3.0 �Ă�Ղ����[�g�Ή�
//04/08/02 v3.12 mb_convert_encoding��auto��JIS,SJIS, �����t�@�C����
//06/08/02 v3.2 ������M�Ńt�@�C�����d���o�O�Aau�g�є��ʃo�O
//07/11/28 v3.4 Yahoo�AGmail�̓��t�AFrom�擾�o�O�A�g���q���擾
//08/10/17 v3.5 Gmail���o�C���p�C��
//09/10/12 v3.6 Subject�����s�C���B�{���֎~���[�h�ǉ�
//14/10/21 v4.0 �{���̖`�������̉��s�폜
/*-----------------*/
require_once("config.php");
require_once("thumb.php");

// mb_�֐����g���Ȃ��ꍇ��http://www.spencernetwork.org/�ɂĊ����R�[�h�ϊ�(�ȈՔ�)����肷�鎖
if (file_exists("jcode-LE.php")) require_once("jcode-LE.php");
/*-----------------*/
//���������΍�
if (function_exists("mb_internal_encoding")) {
  mb_internal_encoding("SJIS");
  mb_language("Japanese");
}
$sock = fsockopen($host, 110, $err, $errno, 10) or die("���ް�ɐڑ��ł��܂���");
$buf = fgets($sock, 512);
if(substr($buf, 0, 3) != '+OK') die($buf);
$buf = _sendcmd("USER $user");
$buf = _sendcmd("PASS $pass");
$data = _sendcmd("STAT");//STAT -�����ƃT�C�Y�擾 +OK 8 1234
sscanf($data, '+OK %d %d', $num, $size);
if ($num == "0") {
  $buf = _sendcmd("QUIT"); //�o�C�o�C
  fclose($sock);
  header("Location: $jump");
  exit;
}
// ������
for($i=1;$i<=$num;$i++) {
  $line = _sendcmd("RETR $i");//RETR n -n�Ԗڂ̃��b�Z�[�W�擾�i�w�b�_�܁j
  while (!preg_match("/^\.\r\n/",$line)) {//EOF��.�܂œǂ�
    $line = fgets($sock,512);
    $dat[$i].= $line;
  }
  $data = _sendcmd("DELE $i");//DELE n n�Ԗڂ̃��b�Z�[�W�폜
}
$buf = _sendcmd("QUIT"); //�o�C�o�C
fclose($sock);

$lines = array();
$lines = @file($log);
$write2 = false;

for($j=1;$j<=$num;$j++) {
  $write = true;
  $subject = $from = $text = $atta = $part = $attach = $filename = "";
  list($head, $body) = mime_split($dat[$j]);
  // ���t�̑�����
  preg_match("/\nDate:[ \t]*([^\r\n]+)/i", $head, $datereg);
  $now = strtotime($datereg[1]);
  if ($now == -1) $now = time();
  $head = preg_replace("/\r\n? /", "", $head);
  // �T�u�W�F�N�g�̒��o
  if (preg_match("/\nSubject:[ \t]*(.+)/i", $head, $subhead)) {
    $subreg = preg_split("/[\r\n][A-Za-z-]+: /",$subhead[1]);
    $subject = str_replace("\n","", $subreg[0]);
    while (preg_match("/(.*)=\?iso-2022-jp\?B\?([^?]+)\?=(.*)/i",$subject,$regs)) {//MIME B�޺���
      $subject = $regs[1].base64_decode($regs[2]).$regs[3];
    }
    while (preg_match("/(.*)=\?iso-2022-jp\?Q\?([^?]+)\?=(.*)/i",$subject,$regs)) {//MIME Q�޺���
      $subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
    }
    while (preg_match("/(.*)=\?utf-8\?B\?([^?]+)\?=(.*)/i",$subject,$regs)) {//iphone?
      $subject = $regs[1].base64_decode($regs[2]).$regs[3];
    }
    $subject = convert($subject);
    $subject = htmlspecialchars($subject, ENT_QUOTES, "SJIS");
    // ���ی���
    foreach ($deny_subj as $dsubj) {
      if (stristr($subject, $dsubj)) $write = false;
    }
  }
  // ���M�҃A�h���X�̒��o
  if (preg_match("/\nFrom:[ \t]*([^\r\n]+)/i", $head, $freg)) {
    $from = addr_search($freg[1]);
  } elseif (preg_match("/\nReply-To:[ \t]*([^\r\n]+)/i", $head, $freg)) {
    $from = addr_search($freg[1]);
  } elseif (preg_match("/\nReturn-Path:[ \t]*([^\r\n]+)/i", $head, $freg)) {
    $from = addr_search($freg[1]);
  }
  // ���ۃA�h���X
  foreach ($deny_from as $dfrom) {
    if (stristr($from, $dfrom)) $write = false;
  }

  // �}���`�p�[�g�Ȃ�΃o�E���_���ɕ���
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
    $part[0] = $dat[$j];// ���ʂ̃e�L�X�g���[��
  }
  foreach ($part as $multi) {
    list($m_head, $m_body) = mime_split($multi);
    $m_body = preg_replace("/\r\n\.\r\n$/", "", $m_body);
    if (!preg_match("/\nContent-type: *([^;\n]+)/i", $m_head, $type)) continue;
    list($main, $sub) = explode("/", $type[1]);
    // �{�����f�R�[�h
    if (strtolower($main) == "text" && trim($text) == '') {
      if (preg_match("#\nContent-Transfer-Encoding:.*base64#i", $m_head)) 
        $m_body = base64_decode($m_body);
      if (preg_match("#\nContent-Transfer-Encoding:.*quoted-printable#i", $m_head)) 
        $m_body = quoted_printable_decode($m_body);
      $text = convert($m_body);
      if ($sub == "html") $text = strip_tags($text);
      // �d�b�ԍ��폜
      $text = preg_replace("/([0-9]{11})|([0-9\-]{13})/", "", $text);
      // �����폜
      $text = preg_replace("/[_]{25,}/", "", $text);
       // mac�폜
      $text = preg_replace("#\nContent-type: multipart/appledouble;\sboundary=(.*)#i","",$text);
      // �L�����폜
      if (is_array($word)) {
        $text = str_replace($word, "", $text);
      }
      // ���ۖ{��
      if (is_array($deny_text)) {
        foreach ($deny_text as $dtext) {
          if (stristr($text, $dtext)) $write = false;
        }
      }
      // �������I�[�o�[
      if (strlen($text) > $maxtext) $text = substr($text, 0, $maxtext)."...";
      $text = str_replace(">","&gt;",$text);
      $text = str_replace("<","&lt;",$text);
      $text = str_replace("\r\n", "\r",$text);
      $text = str_replace("\r", "\n",$text);
      $text = preg_replace("/\n{2,}/", "\n\n", $text);
      $text = str_replace("\n", "<br>", trim($text));
    }
    // �t�@�C�����𒊏o
    if (preg_match("/name=\"?([^;\"\n\s]+)\"?/i",$m_head, $filereg)) {
      $filename = preg_replace("/[\t\r\n]/", "", $filereg[1]);
      while (preg_match("/(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)/i",$filename,$regs)) {
        $filename = $regs[1].base64_decode($regs[2]).$regs[3];
        $filename = convert($filename);
      }
      $ext = substr($filename,strrpos($filename,".")+1,strlen($filename)-strrpos($filename,"."));
    }
    // �Y�t�f�[�^���f�R�[�h���ĕۑ�
    if (preg_match("/\nContent-Transfer-Encoding:.*base64/i", $m_head) && preg_match("/$subtype/", $sub)) {
      $tmp = base64_decode($m_body);
      if (!$ext) $ext = $sub;
      if (!$original || !$filename) $filename = $now.".".$ext;
      if (strlen($tmp) < $maxbyte && !preg_match("/$viri/", $filename) && $write) {
        $fp = fopen($tmpdir.$filename, "w");
        fputs($fp, $tmp);
        fclose($fp);
        $attach = $filename;
        //�T���l�C��
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
  // �A�����e
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

// ���O�ő�s����
if (count($lines) > $maxline) {
  for ($k=count($lines)-1; $k>=$maxline; $k--) {
    list($id,$tim,$sub,$fro,$tex,$at,) = explode("<>", $lines[$k]);
    if (file_exists($tmpdir.$at)) @unlink($tmpdir.$at);
    $lines[$k] = "";
  }
}
//���O��������
if ($write2) {
  $fp = fopen($log, "w");
  flock($fp, LOCK_EX);
  fputs($fp, implode('', $lines));
  fclose($fp);
}

/* �R�}���h�[���M�I�I */
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

/* �w�b�_�Ɩ{���𕪊����� */
function mime_split($data) {
  $part = preg_split("/\r\n\r\n/", $data, 2);
  $part[1] = preg_replace("/\r\n[\t ]+/", " ", $part[1]);

  return $part;
}
/* ���[���A�h���X�𒊏o���� */
function addr_search($addr) {
  if (preg_match("/[-!#$%&\'*+\.\/0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\.\/0-9=?A-Z^_`a-z{|}~]+/", $addr, $fromreg)) {
    return $fromreg[0];
  } else {
    return false;
  }
}
/* �����R�[�h�R���o�[�gauto��SJIS */
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
