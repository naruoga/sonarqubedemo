<?php
session_start();

// �A�N�Z�X���ۃz�X�g �����v
$deny = array("kinja.com",".br",".sa",".pl",".it");

$host = gethostbyaddr(getenv("REMOTE_ADDR"));
foreach ($deny as $denyhost) {
  if (eregi("$denyhost$", $host)) {
    // �w��y�[�W�ɔ�΂�
    header("Location: http://php.s3.to/");exit;
  }
}

if (phpversion() >= "4.1.0") {
  extract($_GET);
  extract($_POST);
  extract($_SERVER);
  extract($_COOKIE);
}
$PHP_SELF = $SCRIPT_NAME;
/*************************************
 * Tree BBS           by ToR
 *
 * http://php.s3.to/
 *
 *************************************
 * �c���[�f���ł��B
 * �E�����ȕ\�����[�h���I�ׂ܂�
 * �E�N�b�L�[�ɂ��f�t�H���g�̕\�����[�h�ƌ������ݒ�ł��܂�
 * �E�����@�\������܂�
 * �E��d���e�E�A�����e�h�~�@�\������܂�
 * �E�֎~����**�ɕϊ����܂�
 * �E���^�O���w��ł��܂�
 * �E�V���L���ɂ�New���̕�����\���ł��܂�
 * �E�c���[�̐[���𐧌��ł��܂�
 * �E�D���ȃf�U�C���ɂ��₷���ł�
 *
 * ����
 * �E��̃��O�t�@�C����p�ӂ��ď������ݑ�����^���ĉ�����
 * �E�ߋ����O���g���ꍇ�͉ߋ����O�f�B���N�g�����������݉ɂ��āA
 * �@past.txt��p�ӂ��Ē���1�Ƃ��������ĕۑ����A�������݉ɂ��ĉ������B
 *
  * 6/17 �ߋ����O�C���A������
  * 04/2/22 ���X�������Ascript�����ǉ�
  * 04/08/14  Line943: /&lt;($tags)(.*?)&gt;/si �� /&lt;($tags)\b(.*?)&gt;/si
  * 09/06/22 XSS�Ǝ㐫�C��
 */
class tree{
  var $logarr	= array();
  var $id	= array();
  var $thread	= array();
  var $root	= array();
  var $tree_arr = array();
  var $error	= "";

  /* �ݒ肱������ */
  // �c���[�L��
  var $i_gif = "��";
  var $t_gif = "��";
  var $l_gif = "��";
  var $nogif = "�@";
  // ���O�t�@�C����
  var $logfile = "log.log";
  // ���O�ő�s
  var $max_log   = 100;
  // �ߋ����O�@�\���g���HYES=1,No=0
  var $past      = 0;
  // �ߋ����ONo�t�@�C��(�����l��1)(1.log 2.log���쐬
  var $pastno    = "past.txt";
  // �ߋ����O�ۑ��f�B���N�g��(�������݉\�ɂ���B707��
  var $pastdir   = "./dat/";
  // �R�����g�ő�s
  var $max_line  = 25;
  // ���e�t�H�[���\�����瓊�e�܂ł̐����b
  var $posttime  = 3;
  // �A�����e�����b
  var $renzoku   = 10;
  // �ŐV�H���ԓ��̓��e��New�L��
  var $expire    = 24;
  // �c���[�̐[������
  var $max_depth = 20;
  // �c���[�̃��X������
  var $max_res   = 50;
  // �c���[���̈�s�\���������i�L��+�薼+�����𔼊p�Łj
  var $cut_size  = 60;
  // ������^�O�i�{���̂݁j
  var $allow_tag = "<b><i><s><p>";
  // �֎~���镶���i**�ɕϊ��j
  var $bad_word  = "href=|url=|�o�J|����|����|�ˋ����|�̔�|����|�Z��|����|http://";
  // >���p���������̐F
  var $quote	= "#aaaacc";
  // ���e������ƊǗ��l�Ƀ��[���Œʒm����ꍇ�̃A�h���X�i�s�v�Ȃ��j
  var $adm_mail = "";
  // �Ǘ��l�ɗ��郁�[����subject�i{id},{subj}�g�p�j
  var $adm_subj = "[BBS-{id} {subj} ]";
  // �Ǘ��l�p�X���[�h�B�P�ƋL���\���̍폜�t�H�[������
  var $adm_pass = "pass1212";
  // �����\�����[�h(tree=�c���[,expn=�X���b�h,root=�^�C�g��,dump=�ꗗ
  var $mode = "root";
  // �\�������̏������
  var $page_tree = 10;
  var $page_expn = 5;
  var $page_root = 15;
  var $page_dump = 20;
  // �N�b�L�[�ۑ���
  var $cookie_n  = "treebbs_cookie";
  var $cookie_s  = "treebbs_setting";
  // �w�b�_����
  var $head = '<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
  <title>�T�|�[�g�f����</title>
  <style type=text/css>
  <!--
  a {text-decoration: none;}
  a:hover {text-decoration: underline;}
  .list {font-size: 11pt;line-height: 10.5pt;}
  .slist{font-size: 10pt;line-height: 9pt;}
  .input {border:solid 1 #77c;background-color:f0f0f0;font-size:10pt;color:#444;}
  .submit {border:solid 1 #44c;background-color:9999ff;font-size:9pt;color:#fff;}
  .brdr {background-color:#aaaacc;}
  .bga {background-color:#ddddff;}
  .bgb {background-color:#e0e0ff;}
  .bgc {background-color:#f0f0ff;}
  -->
  </style></head>
  <body bgcolor=#ffffff text=#666666 link=#4444ff alink=#4444ff vlink=#4444ff>
  <center><h2>�T�|�[�g�f����</h2><br>�����F{today} ����F{yesterday} ���v�F{total}</center>';

  /** �ϐ�����
    * {id}-�L��No {subj}-�薼 {name}-���e�� {date}-���e�� {com}-�{�� {host}-�z�X�g {time}-���etime
    * {n}-�P�ƋL���\�������N {all}-�c���[�ꗗ�\�������N {reply}-�ԐM�����N {nl}��\n�i''�ň͂������p�j
    * {new}�ŐV��$new_msg�\�� {email}�ňȉ���$if_email�����Ƀ����N {url}�ňȉ���$if_url�����Ƀ����N
    */
  // �^�C�g���\���w�b�_
  var $root_head = "<br><table align=center width=90% cellpadding=3 cellspacing=1 class=brdr><tr class=bga><td align=center>No.</td><td>�^�C�g��</a></td><td align=center>���e��</td><td align=center>���X</td><td>�ŏI�X�V</td></tr>\n";
  // �^�C�g���\���{�� {lname}�ŐV���e�� {ldate}�ŐV���e�� {lcom} �ŐV�{��
  var $root_msg = "<tr class=bgc><td align=center>{num}</td><td><a href={all}>{subj}</a></td><td align=center>{name}</td><td align=center>{res}</td><td><small>{ldate} <br>by {lname} {host}</small></td></tr>\n";
  // �^�C�g���\���t�b�^
  var $root_foot = "</table><br>\n";

  // �c���[�\���w�b�_
  var $tree_head = "<table width=90% align=center class=list><tr><td>";
  // �c���[�\���e�L��
  var $tree_oya = "<hr size=1><a href={all}>��</a>-<a href={n}>{subj}</a> [{name}] <font color=gray size=2>({date})</font><br\n>";
  // �c���[�\���q�L��
  var $tree_res = "<a href={n}>{subj}</a> [{name}] <font color=gray size=2>({date})</font> {new}<br\n>";
  // �c���[�\���t�b�^
  var $tree_foot = "</td></tr></table><hr size=1 width=90%>\n";

  // �W�J�\���e�L��
  var $expn_oya = "<a name={num}><table cellpadding=5 cellspacing=1 width=90% align=center class=brdr><tr><td class=bgb> {num}: <font size=4 color=4444aa>{subj}</font></td></tr><tr><td class=bgc><tt>Name: {name} {url} {email}<br>Date: {date}</tt><blockquote>{com}</blockquote>
<div align=right>No.{id}<a href={reply}>�ԐM����</a></div><ul>\n";
  // �W�J�\���q�L��
  var $expn_res = "<hr size=1> [{id}] {subj}<br><tt>Name: {name}  {url} {email}<br>Date: {date}</tt><blockquote>{com}</blockquote>
<div align=right><a href={reply}>�ԐM����</a></div>\n";
  // �W�J�\���e�[�u����
  var $expn_bott = "</ul></td></tr></table><br>\n";

  // �ꗗ�E�P�ƕ\��
  var $dump_msg = "<br><a name={id}><table cellpadding=3 cellspacing=1 width=90% align=center class=brdr>
<tr><td class=bgb> [<a href={n}>{id}</a>] <font size=4 color=4444aa>{subj}</font></td></tr><tr><td class=bgc><tt>Name: {name} {url} {email}<br>Date: {date}</tt><blockquote>{com}</blockquote>
<p align=right><a href={reply}>�ԐM����</a></p></td></tr></table>\n";

  //�P�ƕ\�����֘A�c���[�J�n�E�I���^�O
  var $n_tree_st = "<table width=90% align=center class=slist><tr><td><hr size=1>�֘A�c���[\n";
  var $n_tree_to = "</td></tr></table><hr size=1 width=90%>\n";
  //�c���[�ꊇ���c���[�J�n�E�I���^�O
  var $all_tree_st = "<table width=90% align=center class=slist><tr><td>No.{id}�Ɋւ���c���[";
  var $all_tree_to = "</td></tr></table>\n";

  // �t�b�^����
  var $foot = "\n<hr size=1></body></html>";

  // �V���L���L��
  var $new_msg = "<font color=dd4444 size=1>New!</font>";
  // E-Mail������Ƃ�{email}�̃����N�����i��Ȃ疼�O�Ƀ����N�j
  var $if_email = "E-MAIL";
  // URL������Ƃ�{url}�̃����N����
  var $if_url = "(HOME)";

  /***
   * �R���X�g���N�^
   *
   */
  function tree($mode, $log){
    global ${$this->cookie_s};

//    $this->mode = "tree";

    if(${$this->cookie_s}){
      list($view,$pagetree,$pageexpn,$pageroot,$pagedump)=explode(",",${$this->cookie_s});
      $this->mode = $view;
      $this->page_tree = $pagetree;
      $this->page_expn = $pageexpn;
      $this->page_root = $pageroot;
      $this->page_dump = $pagedump;
    }
    if($mode) $this->mode = $mode;
    if($log && file_exists($this->pastdir.$log.".log")) {
      $this->logfile = $this->pastdir.$log.".log";
    }
    $this->logarr = file($this->logfile);
  }
  /***
   * �Ȃт߂ɂイ�\��
   *
   */
  function show_navi(){
    global $PHP_SELF,$all,$n,$log;
    if($log) $kako = "&log=$log";

    $form = ($this->mode == "form" && !$all && !$n) ? "�V�K���e" : "<a href=$PHP_SELF?mode=form{$kako}>�V�K���e</a>";
    $tree = ($this->mode == "tree" && !$all && !$n) ? "�c���[" : "<a href=$PHP_SELF?mode=tree{$kako}>�c���[</a>";
    $expn = ($this->mode == "expn" && !$all && !$n) ? "�X���b�h" : "<a href=$PHP_SELF?mode=expn{$kako}>�X���b�h</a>";
    $root = ($this->mode == "root" && !$all && !$n) ? "�^�C�g��" : "<a href=$PHP_SELF?mode=root{$kako}>�^�C�g��</a>";
    $dump = ($this->mode == "dump" && !$all && !$n) ? "�ꗗ" : "<a href=$PHP_SELF?mode=dump{$kako}>�ꗗ</a>";
    $search = ($this->mode == "search") ? "����" : "<a href=$PHP_SELF?mode=search{$kako}>����</a>";
    if ($this->past) $past_m = "<a href=$PHP_SELF?mode=past>���O</a>�@�b�@";
    $setup = ($this->mode == "setup") ? "�ݒ�" : "<a href=$PHP_SELF?mode=setup>�ݒ�</a>";

    $html = "<br><table border=0 cellspacing=1 cellpadding=1 class=brdr align=center width=90%>
<tr><td align=center class=bga>�@{$form}�@�b�@{$tree}�@�b�@{$expn}�@�b�@{$root}�@�b�@{$dump}�@�b�@{$search}�@�b�@{$past_m}{$setup}�@</td></tr></table><br>\n";
    return $html;
  }
  /***
   * ���y�[�W�\��
   *
   */
  function show_jump($offset, $limit, $total){
    global $PHP_SELF,$log;

    if($log) $kako = "&log=$log";

    $num_page = ceil($total/$limit);
    $prev = $offset - 1;
    $next = $offset + 1;
    if($prev >= 1){
      $prev_st = "<a href='$PHP_SELF?mode=$this->mode&page=$prev$kako'>";
      $prev_to = "</a>";
    }
    if($next <= $num_page){
      $next_st = "<a href='$PHP_SELF?mode=$this->mode&page=$next$kako'>";
      $next_to ="</a>";
    }
    $html.= "<center>[ {$prev_st}�O�� {$limit} ��{$prev_to} | {$offset} /{$num_page}�y�[�W | {$next_st}���� {$limit} ��{$next_to} ]</center>\n";

    return $html;
  }
  /***
   * �w�b�h���C���\��
   *
   */
  function show_headline($offset,$limit){
    global $PHP_SELF,$log;

    if($log) $kako = "&log=$log";
    $st = ($offset-1) * $limit;
    if($st < 0) $st = 0;

    $html = "<br><table cellpadding=5 cellspacing=1 border=0 align=center class=brdr width=90%><tr><td class=bgc>";
    for($i = $st; $i < $st+$limit*5; $i++){
      $id = $this->root[$i];
      if(!$id) continue;
      $resc = 0;
      foreach($this->thread[$id] as $kidno){
        $resc += count($kidno);
      }
      list(,,,$subj,) = explode("\t", $this->id[$id]);
      $num = $i+1;
      if($i < $st+$limit) $html.= "<a href=#$num>".$num.": ".$subj."(".$resc.")</a>�@\n";
      else $html.= "<small><a href=$PHP_SELF?all=$id$kako><font color=6666ff>".$num.": ".$subj."(".count($this->thread[$id]).")</font></a></small>�@";
    }
    $html.= "</td></tr></table><br>\n";

    return $html;
  }
  /***
   * ���e�t�H�[���\��
   *
   */
  function show_form($root=0, $oya=0, $subj='', $com='', $mode='quote', $name='', $email='', $url='', $no='', $pass=''){
    global $PHP_SELF,$n,${$this->cookie_n},$log;

    $sid = session_id();
    $_SESSION['time'] = time();

    if ($mode == "quote") {
      if(get_magic_quotes_gpc()) ${$this->cookie_n} = stripslashes(${$this->cookie_n});

      if(ereg("Re\[([0-9]+)\]:", $subj, $reg)){
        $reg[1]++;
        $r_sub=ereg_replace("Re\[([0-9]+)\]:", "Re[$reg[1]]:", $subj);
      }elseif(ereg("^Re:", $subj)){ 
        $r_sub=ereg_replace("^Re:", "Re[2]:", $subj);
      }elseif($subj){
        $r_sub = "Re:$subj";
      }

      if($com){
        $i_com = ">$com";
        $i_com = str_replace("'", "\'", $i_com);
        $i_com = str_replace("&gt;", ">", $i_com);
        $i_com = str_replace("&lt;", "<", $i_com);
        $i_com = eregi_replace("<br( /)?>", "<br>", $i_com);
      }
      list($c_name,$c_email,$c_url,$c_pass) = explode(",", ${$this->cookie_n});
      $hidden = "<input type=hidden name=act value=post>";
    }
    if ($mode == "edit") {
      $r_sub = $subj;
      $r_com = $com;
      $c_name = $name;
      $c_email = $email;
      $c_url = $url;
      $c_pass = $pass;
      $hidden = "<input type=hidden name=act value=editpost><input type=hidden name=editno value=$no>";
    }
    if(isset($log)) $kako = "<input type=hidden name=kako value=kako>";
    if($this->allow_tag) $tag = "�^�O�g�p�� ".htmlspecialchars($this->allow_tag);
    $html.= '
<script language="javascript">
<!--//
function Inyou()
{
	var msg = \''.$i_com.'\';
	while(msg.match(/<br>/g) != null) {
		msg = msg.replace(/<br>/g, "\n>");
	}
	msg = msg.replace(/\\\/g, "");
	msg = msg.replace(/&amp;/g, "&");
	document.form.com.value = msg;
}
//-->
</script>';
    $html.= "
<form name=form method=post action='$PHP_SELF'>$hidden
<input type=hidden name=st value='$root'>
<input type=hidden name=re value='$oya'>
<input type=hidden name=PHPSESSID value='$sid'>
<table cellpadding=2 cellspacing=1 border=0 align=center>
<tr>
    <td height=20 colspan=2  class=bgc><a name=frm>&nbsp;";
    if($mode == "quote") $html.= ($root) ? "�ԐM�t�H�[�� <small>[<a href='#frm' onClick='Inyou()'>���p</a>]</small>" : "�V�K���e�t�H�[��";
    if($mode == "edit") $html.= "�ҏW�t�H�[��";
    $html.= "
    </td>
</tr>
<tr><td><br></td></tr>
<tr>
    <td class=bga>&nbsp;���O</td>
    <td><input type=text name=name size=30 maxlength=100 value='$c_name' class=input> *</td>
</tr>
<tr>
    <td class=bga>&nbsp;E-Mail</td>
    <td><input type=text name=email size=30 maxlength=100 value='$c_email' class=input></td>
</tr>
<tr>
    <td class=bga>&nbsp;�薼</td>
    <td><input type=text name=sub size=60 maxlength=120 value='$r_sub' class=input> *</td>
</tr>
<tr>
    <td colspan=2 width=100% align=left>
    <textarea name=com cols=65 rows=10 wrap=virtual class=input>$r_com</textarea>
    </td>
</tr>
<tr>
    <td class=bga>&nbsp;URL</td>
    <td><input type=text name=url size=60 maxlength=200 value='http://$c_url' class=input></td>
</tr>
<tr>
    <td class=bga>&nbsp;Pass</td>
    <td><input type=password name=pass size=10 maxlength=20 value='$c_pass' class=input> $tag </td>
</tr>
<tr>
    <td colspan=2>
    <input type=checkbox name=email_reply value=y> ���X���t���Ώ�L��Email�A�h���X�Ƀ��[���Œm�点��</td>
</tr>
<tr>
    <td colspan=2><br> 
    <input type=submit value='  ���e����  ' class=submit> <input type=reset value='  �N���A  ' class=submit>
 </table></form>";

    return $html;
  }
  /***
   * �����t�H�[���\��
   *
   */
  function show_search($word){
    global $PHP_SELF,$log;

    if(get_magic_quotes_gpc()) $word = stripslashes($word);
    $word = htmlspecialchars($word);
    if (is_numeric($log)) {
      $num = sprintf("%03d",$log);
      $kako = "�ߋ����O $num ����";
      $hide = "<input type=hidden name=log value='{$log}'>";
    }

    $html = "<br><table cellpadding=3 cellspacing=1 width=45% align=center class=brdr>
    <tr><td class=bgb align=center><font size=3><b>[ {$kako}�L������ ]</b></font></td></tr>
    <tr><td class=bgc align=center><br>
    <form method=post action='{$PHP_SELF}'>{$hide}
    <input type=hidden name=mode value='search'>
     <input type=text name=word size=20 value=\"{$word}\" class=input><select name=andor style='background-color:#eee;color:#444;'>
       <option value=and selected>AND</option>
       <option value=or>OR</option>
     </select>
     <input type=submit class=submit value='  ����  ' class=submit>
     </form></td></tr></table><br><br>";

    return $html;
  }
  /***
   * �ݒ�t�H�[���\��
   *
   */
  function show_setup(){
    global $PHP_SELF,${$this->cookie_s};

    list($view,) = explode(",", ${$this->cookie_s});
    $$view = " selected";
    if(!$view) $tree = " selected";

    $html = "<br><table cellpadding=3 cellspacing=1 width=60% align=center class=brdr>
    <tr><td class=bgb align=center><font size=3><b>[ ���ݒ� ]</b></font></td></tr>
    <tr><td class=bgc align=center><br>
    <form method=post action='$PHP_SELF'>
    <input type=hidden name=act value='setup_cookie'>
     <table width=90%><tr><td>�f�t�H���g�̕\�����[�h  </td><td>
     <select name=view style='background-color:#eee;color:#444;'>
       <option value=tree$tree>�c���[�\��</option>
       <option value=expn$expn>�W�J�\��</option>
       <option value=root$root>�^�C�g���\��</option>
       <option value=dump$dump>�ꗗ�\��</option>
     </select>
      </td></tr><tr><td colspan=2><hr size=1></td></tr>
      <tr><td>�c���[�\�����̂P�y�[�W�̕\����</td>
      <td><input type=text name=pagetree size=8 value=$this->page_tree></td></tr>
      <tr><td>�W�J�\�����̂P�y�[�W�̕\����</td>
      <td><input type=text name=pageexpn size=8 value=$this->page_expn></td></tr>
      <tr><td>�^�C�g���\�����̂P�y�[�W�̕\����</td>
      <td><input type=text name=pageroot size=8 value=$this->page_root></td></tr>
      <tr><td>�ꗗ�\�����̂P�y�[�W�̕\����</td>
      <td><input type=text name=pagedump size=8 value=$this->page_dump></td></tr>
      <tr><td colspan=2><hr size=1></td></tr>
      <tr><td colspan=2 align=center><input type=submit class=submit value='  �ݒ�  ' class=submit>
      </td></tr></table>
     </form></td></tr></table><br><br>";

    return $html;
  }
  /***
   * �ߋ����O�t�H�[��
   *
   */
  function show_past(){
    global $PHP_SELF;

    $html = "<br><table cellpadding=3 cellspacing=1 width=60% align=center class=brdr>
    <tr><td class=bgb align=center><font size=3><b>[ ���O�I�� ]</b></font></td></tr>
    <tr><td class=bgc align=center>�Â����ɕ���ł��܂�<br><br>";

    $no = file($this->pastno);
    for($i=1; $i<=$no[0]; $i++){
      $num = sprintf("%03d",$i);
      if(file_exists($this->pastdir.$i.".log")){
        $html.="<a href=$PHP_SELF?log=$i>�ߋ����O $num </a><br>";
      }
    }

    $html.="<a href=$PHP_SELF?>���݂̃��O</a><br><br></td></tr></table><br><br>";

    return $html;
  }
  /***
   * �폜�t�H�[��
   *
   */
  function show_del($n){
    global $PHP_SELF,${$this->cookie_n};

    list(,,,$pass) = explode(",", ${$this->cookie_n});

    $html = "<hr size=1><div align=right>
    <form method=post action='$PHP_SELF'>
    <input type=hidden name=id value='$n'>
    Pass <input type=password size=8 name=pwd value='$pass'><select name=act>
    <option value='del'>�폜<option value='edit'>�ҏW</select>
    <input type=submit value='���s'>
    </form></div>";

    return $html;
  }    
  /***
   * ���O��z���
   *
   */
  function get_arr(){
    for($i = 0; $i < count($this->logarr); $i++){
      //�L���ԍ�\t�e�L���ԍ�\t���L���ԍ�
      list($id, $oya, $root,) = explode("\t", $this->logarr[$i]);
      //$this->id : �L���ԍ����L�[�ɂ������O�f�[�^�z��
      $this->id[$id] = $this->logarr[$i];
      //$this->thread : �X���b�h�\���̔z��B�l�͋L���ԍ�
      $this->thread[$root][$oya][] = $id;
      //$this->root : ���L���݂̂̔z��B���O��
      if($oya==0)  $this->root[] = $id;
    }
    //print_r($this->thread);
  }
  /***
   * ���C��HTML��������
   *
   */
  function show_main($page){
    if(intval($page) == 0) $page = 1;//�ŏ��̓y�[�W1
    //�w�b�_����
    switch($this->mode){
      //�^�C�g�����[�h
      case 'root':
        //�y�[�W���O����
        $jump = tree::show_jump($page, $this->page_root, count($this->root));
        //�w�b�_HTML
        $html = $jump . $this->root_head;
        //1�y�[�W�\��������
        $page_def = $this->page_root;
        break;
      //�W�J���[�h
      case 'expn':
        $jump = tree::show_jump($page, $this->page_expn, count($this->root));
        //�w�b�h���C��
        $html = $jump . tree::show_headline($page, $this->page_expn);
        $page_def = $this->page_expn;
        break;
      //�c���[���[�h
      case 'tree':
        $jump = tree::show_jump($page, $this->page_tree, count($this->root));
        $html = $jump . $this->tree_head;
        $page_def = $this->page_tree;
        break;
      //�ꗗ���[�h
      case 'dump':
        $jump = tree::show_jump($page, $this->page_dump, count($this->id));
        $html = $jump;
        //�ŐV�ԍ��擾
        list($st,) = explode("\t", $this->logarr[0]);
        //�f�[�^�J�n�ʒu
        $st = ($page) ? $st - ($page-1) * $this->page_dump : $st;
        //�ŐV�L�����ɕ\��
        for($i = $st; $i > $st-$this->page_dump; $i--){
          $html.= tree::show_msg($i, $this->mode);
        }
        //�t�b�^�\���ŏI��
        $html.= "<br>" . $jump;
        return $html;
        break;
    }
    //�f�[�^�J�n�ʒu
    $st = ($page) ? ($page-1) * $page_def : 0;
    //�e�L���ԍ��Ń��[�v
    for($i = $st; $i < $st+$page_def; $i++){
      if($this->root[$i]=="") continue;
      switch($this->mode){
        //�^�C�g�����[�h
        case 'root':
          $html.= tree::show_msg($this->root[$i], 'root');
          $html = str_replace('{num}', $i+1, $html);
          break;
        //�W�J���[�h
        case 'expn':
          //�c���[�\����������
          $this->tree_arr = array();
          //�c���[����
          tree::make_tree($this->root[$i]);
          //�c���[���ɕ\��
          foreach($this->tree_arr as $val){
            $html.= tree::show_msg($val[0], 'expn');
            $html = str_replace('{num}', $i+1, $html);
          }
          //�e�[�u����
          $html.= $this->expn_bott;
          break;
        //�c���[���[�h
        case 'tree':
          $this->tree_arr = array();
          tree::make_tree($this->root[$i]);
          foreach($this->tree_arr as $val){
            $html.= tree::show_msg($val[0], 'tree', $val[1]);
          }
          break;
      }
    }
    //�t�b�^����
    switch($this->mode){
      case 'root':
        $html.= $this->root_foot;
        break;
      case 'tree':
        $html.= $this->tree_foot;
        break;
    }
    //�y�[�W���O�\��
    $html.= $jump;

    return $html;
  }
  /***
   * �c���[�̐����A���ёւ�
   *
   */
  function make_tree($id, $oya=0, $pre="", $img=""){
    //�O�c���[��L�Ȃ炻�̉��͋�
    if($img == $this->l_gif) $pre.= $this->nogif;
    //�O�c���[��T�Ȃ炻�̉���I
    if($img == $this->t_gif) $pre.= $this->i_gif;
    //�c���[�\���Ȃ�
    if(is_array($this->thread[$id][$oya])){
      foreach($this->thread[$id][$oya] as $no){
        //���c���[�������T�L���I����Ă��L�L��
        $img = (count($this->thread[$id][$oya])>1) ? $this->t_gif : $this->l_gif;
        //�q�L���͌��ɑ���
        $no = ($oya==0) ? array_shift($this->thread[$id][$oya]) : array_pop($this->thread[$id][$oya]);
        //�c���[�\���ƋL����z���
        $this->tree_arr[] = array($no,"$pre$img");
        //�ċA
        tree::make_tree($id,$no,$pre,$img);
      }
      return true;
    }else{
      return false;
    }
  }
  /***
   * HTML�����E�e���v���u��
   *
   */
  function show_msg($no, $act="", $mark=""){
    global $PHP_SELF,$all,$n,$log;

    if($log) $kako = "&log=$log";
    if(!$this->id[$no]) return false;
    list($id,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,) = explode("\t", $this->id[$no]);
    switch($act){
      case 'root':
        //if($oya == 0) $html = $this->root_msg;
        foreach($this->thread[$no] as $kidno){
          rsort($kidno);
          $tmp = $kidno[0];
          $max = ($tmp > $max) ? $tmp : $max;
          $resc += count($kidno);
        }
        $resc = $resc - 1;
        list($lno,$oya,$root,,$lname,$lemail,$ldate,$lcom,$lurl,$ltim,$lhost,) = explode("\t", $this->id[$max]);
        $html = $this->root_msg;
        $lcom = substr($lcom, 0, 30)."..";
        $html = str_replace('{res}', "$resc", $html);
        $html = str_replace('{lname}', "$lname", $html);
        $html = str_replace('{ldate}', "$ldate", $html);
        $html = str_replace('{lcom}', "$lcom", $html);
        break;
      case 'expn':
        $html = ($oya == 0) ? $this->expn_oya : $this->expn_res;
        if($email && $this->if_email=="") $html = str_replace('{name}', "<a href=mailto:$email>{name}</a>", $html);
        elseif($email) $html = str_replace('{email}', "<a href=mailto:$email>$this->if_email</a>", $html);
        else $html = str_replace('{email}', "", $html);
        break;
      case 'tree':
        if($oya == 0){
          $html = $this->tree_oya;
        }else{
          $html = $mark . $this->tree_res;
        }
        if($id == $n) $html = ereg_replace("(<a href=([^>]+)>)([^<]+)(</a>)","<b>\\3</b>", $html);
        if($all) $html = str_replace('{n}', "#$id", $html);
        if(!$all && !$n && (strlen($name)+strlen($subj)+strlen($mark) > $this->cut_size)){
          if(strlen($name) > strlen($subj)){
            $name = substr($name, 0, $this->cut_size-strlen($mark)+strlen($subj)) . "..";
          }else{
            $subj = substr($subj, 0, $this->cut_size-strlen($mark)+strlen($name)) . "..";
          }
        }
        break;
      case 'dump':
        $html = $this->dump_msg;
        if($email && $this->if_email=="") $html = str_replace('{name}', "<a href=mailto:$email>{name}</a>", $html);
        elseif($email) $html = str_replace('{email}', "<a href=mailto:$email>$this->if_email</a>", $html);
        else $html = str_replace('{email}', "", $html);
        if($id == $n) $html = str_replace('<a href={reply}>'."([^<]+)</a>", "", $html);
    }
    if($url) $html = str_replace('{url}',"<a href=\"http://$url\" target=_blank>$this->if_url</a>", $html);
    else $html = str_replace('{url}', "", $html);
    // ���������N
    $com = tree::auto_link($com);
    // �������鎞�͐F�ύX
    $com = ereg_replace("(^|>)(&gt;[^<]*)", "\\1<font color=$this->quote>\\2</font>", $com);

    $html = str_replace('{id}', $id, $html);
    $html = str_replace('{subj}', $subj, $html);
    $html = str_replace('{name}', $name, $html);
    $html = str_replace('{date}', $date, $html);
    $html = str_replace('{com}', $com, $html);
    $html = str_replace('{host}', $host, $html);
    $html = str_replace('{time}', $tim, $html);
    $html = str_replace('{n}', $PHP_SELF."?n=".$id.$kako, $html);
    $html = str_replace('{all}', $PHP_SELF."?all=".$root.$kako, $html);
    $html = str_replace('{reply}', $PHP_SELF."?n=".$id.$kako."#frm", $html);
    $html = str_replace('{nl}', "\n", $html);

    if(time() - $tim < $this->expire*3600) $html = str_replace('{new}', $this->new_msg, $html);
    else $html = str_replace('{new}', "", $html);

    return $html;
  }
  /***
   * �������ݑO�`�F�b�N
   *
   */
  function add_chk($root=0, $oya=0, $name, $email, $subj, $com, $url, $pass, $email_reply, $no=''){
    global $REQUEST_METHOD,$PHP_SELF;

    if ($_SESSION['time'] == "") {
      $this->error = "�s���ȓ��e�����Ȃ��ŉ�����";
      return false;
    }
    if (time() - $_SESSION['time'] < $this->posttime) {
      $this->error = "���e�͂������΂炭���Ԃ�u���Ă��炨�肢�v���܂�";
      return false;
    }
    // ������
    if (!is_numeric($root) || !is_numeric($oya)) {
      $this->error = "�s���ȓ��e�����Ȃ��ŉ�����";
      return false;
    }
    if ($no != "") $no = intval($no);

    if (preg_match("/(<a\b[^>]*?>|\[url(?:\s?=|\]))|href=/i", $com)) {
      $this->error = "�֎~���[�h�G���[�I�I";
      return false;
    }
    if($REQUEST_METHOD != "POST") { 
      $this->error = "�s���ȓ��e�����Ȃ��ŉ�����";
      return false;
    }
    if($url && !ereg("^http://", $url)){
      $this->error = "URL��http://����L�����Ă�������";
      return false;
    }
    if($email && !ereg(".+@.+\\..+", $email)){
      $this->error = "���[���A�h���X�͐������L�����Ă�������";
      return false;
    }
    if($name=="" || ereg("^( |�@)*$",$name)){
      $this->error = "���O���������܂�Ă��܂���";
      return false;
    }
    if($subj=="" || ereg("^( |�@)*$",$subj)){
      $this->error = "�薼���������܂�Ă��܂���";
      return false;
    }
    if($com=="" || ereg("^( |�@|[\r\n])*$",$com)){
      $this->error = "�{�����������܂�Ă��܂���";
      return false;
    }
    // �e�`�F�b�N�A�[���`�F�b�N�A���X���`�F�b�N
    if($root != 0 && $no == ''){
      if(!is_array($this->thread[$root])){
        $this->error = "�ԐM��̋L����������܂���ł���";
        return false;
      }
      if(count($this->thread[$root]) > $this->max_depth){
        $this->error = "�c���[�K�w���[���Ȃ肷�����̂�<br>����ȏ�ԐM�ł��܂���";
        return false;
      }
      foreach($this->thread[$root] as $kidno){
        $resc += count($kidno);
      }
      if($resc > $this->max_res){
        $this->error = "���X�������ɒB�����̂�<br>����ȏ�ԐM�ł��܂���";
        return false;
      }
    }
    if($no == "kako"){
      $this->error = "�ߋ����O�ɂ͓��e�ł��܂���";
      return false;
    }
    //���ݎ���
    $tim = time();
    // �z�X�g�����擾
    $ip = (getenv("HTTP_X_FORWARDED_FOR") != "") ? getenv("HTTP_X_FORWARDED_FOR") : getenv("REMOTE_ADDR");
    $host = gethostbyaddr($ip);
    //����z�X�g����̘A�����e�֎~
    list($lno,,,,$lname,,,$lcom,,$ltime,$lhost,) = explode("\t", $this->logarr[0]);
    if($this->renzoku && $host==$lhost && $tim - $ltime < $this->renzoku){
      $this->error = "�A�����e�͂������΂炭���Ԃ�u���Ă��炨�肢�v���܂�";
      return false;
    }
    //���Ԃ̃t�H�[�}�b�g
    $date = gmdate("m/d H:i",$tim+9*60*60);
    //url���`
    $url = trim($url);
    $url = ereg_replace("^http://", "", $url);
    $url = str_replace(" ", "%20", $url);
    //PW�Í���
    if(trim($pass)!="") $pwd = substr(md5($pass),2,8);
    //�e�L�X�g���`
    $subj = tree::CleanStr($subj);
    $name = tree::CleanStr($name);
    $email= tree::CleanStr($email);
    $url  = tree::CleanStr($url);
    $com  = tree::CleanStr($com,1);
    // ���s�����̓���B 
    $com = str_replace("\r\n", "\n", $com); 
    $com = str_replace("\r", "\n", $com);

    if(substr_count($com, "\n") > $this->max_line){
      $this->error = "�R�����g�s�����������܂�";
      return false;
    }
    // �A�������s����s�E\n��<br />\n��\n����
    $com = ereg_replace("\n((�@| )*\n){3,}","\n",$com);
    $com = nl2br($com);
    $com = str_replace("\n",  "", $com);

    // ��d���e�`�F�b�N
    if($no == '' && $name == $lname && $com == $lcom){
      $this->error = "��d���e�͋֎~�ł�";
      return false;
    }
    // �VNO�A�e�͎�����root
    if($no == ''){
      $no = $lno + 1;
      if($root == 0) $root = $no;
    } elseif(!$this->id[$no]){
      $this->error = "�Y���L�����݂���܂���";
      return false;
    }

    $no = intval($no);
    $oya = intval($oya);
    $root = intval($root);
    $dat = array($no,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,$pwd,$email_reply);

    // �N�b�L�[�ۑ�
    $cookvalue = implode(",", array($name,$email,$url,$pass));
    setcookie ($this->cookie_n, $cookvalue,time()+14*24*3600);

    return $dat;
  }
  /***
   * �V�K��������
   *
   */
  function add_post($dat){

    list($no,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,$pwd,$email_reply) = $dat;

    // �f�[�^�t�H�[�}�b�g
    $new_msg = implode("\t", $dat)."\n";
//    $new_msg = "$no\t$oya\t$root\t$subj\t$name\t$email\t$date\t$com\t$url\t$tim\t$host\t$pwd\t$email_reply\n";

    $mailbody = str_replace("&gt;", ">", $com);
    $mailbody = str_replace("&lt;", "<", $mailbody);
    $mailbody = str_replace("&quote;", '"', $mailbody);
    $mailbody = str_replace("&amp;", "&", $mailbody);
    $mailbody = eregi_replace("<br( /)?>", "\n", $mailbody);
    

    // �Ǘ��l�Ƀ��[���ʒm
    if($this->adm_mail && ereg(".+@.+\\..+", $this->adm_mail)){
      $subject = str_replace("{id}", "$no", $this->adm_subj);
      $subject = str_replace("{subj}", $subj, $subject);
      $body = "Date: $date\nHost: $host\n";
      $body.= "---------------------------------------------\n";
      $body.= $mailbody;
      tree::send_mail($name, $email, $this->adm_mail, $subject, $body);
    }
    // �V���O�z�񐶐�
    if($oya != 0){
      // ���X���������烁�[��
      list(,,,,,$o_email,,,,,,,$reply) = explode("\t", $this->id[$oya]);
      if(rtrim($reply) == "y" && ereg(".+@.+\\..+", $o_email)){
        $subject = $no."-".$subj;
        $body = "$name ���񂩂�̃��X\n";
        $body.= "Date: $date\nHost: $host\n";
        $body.= "---------------------------------------------\n";
        $body.= $mailbody;
        tree::send_mail($name, $email, $o_email, $subject, $body);
      }
      for ($i = 0; $i < count($this->logarr); $i++) {
        list($id, $oy, $rt) = explode("\t", $this->logarr[$i]);
        if($root == $rt) $age[] = $this->logarr[$i];
        else $buf[] = $this->logarr[$i];
      }
      $this->logarr = array_merge($age, $buf);
    }
    array_unshift($this->logarr, $new_msg);

    // �ߋ����O
    if($this->past){
      // ���O���I�[�o�[�͉ߋ����O�ɏ����o��
      if(count($this->logarr) > $this->max_log){
        $back = array_pop($this->logarr);
        $backline[] = $back;
        list($bid, $boya, $broot) = explode("\t", $back);
        for ($j = count($this->logarr); $j >= 0; $j--){
          list($id, $oy, $root) = explode("\t", $this->logarr[$j]);
          if($bid == $root){
            array_unshift($backline, $this->logarr[$j]);
            array_pop($this->logarr);
          }
        }
        // ��No�擾
        $no = file($this->pastno);
        // ���ߋ����O�ǂݍ���
        if(file_exists($this->pastdir.$no[0].".log")) {
          $pastline = file($this->pastdir.$no[0].".log");
        }
        // ���ߋ����O�s���I�[�o�[�Ȃ�NO�A�b�v
        if(count($pastline) > $this->max_log){
          $no[0]++;
          $fp = fopen($this->pastno, "w");
          fputs($fp, $no[0]);
          fclose($fp);
        }else{
          $backline = array_merge($backline, $pastline);
        }
        // �ߋ����O�X�V
        $fp = fopen($this->pastdir.$no[0].".log", "w");
        flock($fp, LOCK_EX);
        fputs($fp, implode('', $backline));
        fclose($fp);
      }
    }
    // ���O��������
    $fp = fopen($this->logfile, "w");
    flock($fp, LOCK_EX);
    fputs($fp, implode('', $this->logarr));
    fclose($fp);

    return true;
  }
  /***
   * �ҏW��������
   *
   */
  function edit_post($dat){

    list($no,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,$pwd,$email_reply) = $dat;

    // �f�[�^�t�H�[�}�b�g
    $new_msg = implode("\t", $dat)."\n";

    for($i = 0; $i < count($this->logarr); $i++){
      list($id,) = explode("\t", $this->logarr[$i]);
      if($id == $no){
        $this->logarr[$i] = $new_msg;
        break;
      }
    }
    // ���O��������
    $fp = fopen($this->logfile, "w");
    flock($fp, LOCK_EX);
    fputs($fp, implode('', $this->logarr));
    fclose($fp);

    return true;
  }
  /***
   * �^�O�E������
   *
   */
  function CleanStr($str,$com=0){
    //�擪�Ɩ����̋󔒏���
    $str = trim($str);
    //��؂蕶��\t�ϊ�
    $str = str_replace("\t", "    ", $str); 
    //�����폜
    if(get_magic_quotes_gpc()){
      $str = stripslashes($str);
    }
    if(isset($this->bad_word)) $str = ereg_replace($this->bad_word, "**", $str);
    if($this->allow_tag && $com == 1){
      //���^�O�ȊO����
     // $str = strip_tags($str, $this->allow_tag);
      $str = htmlspecialchars($str);
      $tags = str_replace("><", "|", $this->allow_tag);
      $tags = ereg_replace("[<>]", "", $tags);
      $str = preg_replace("/&lt;($tags)\b(.*?)&gt;/si", "<\\1\\2>", $str);
      $str = eregi_replace("&lt;\/($tags)&gt;", "</\\1>", $str);
      $str = eregi_replace("<(.*)(style|onmouse|onclick|script)[^=]*=", "<\\1", $str);
    }else{
      //�^�O�ϊ�
      $str = htmlspecialchars($str);
    }
    if ($com == 0) {
      $str = eregi_replace("[\r\n]", "", $str);
    }
    //���ꕶ��
    $str = str_replace("&amp;#", "&#", $str);
    $str = str_replace("&quot;", '"', $str);

    return $str;
  }
  /***
   * �폜
   *
   */
  function del_msg($id, $pwd){
    if(!isset($this->id[$id])){
      $this->error = "�Y���L����������܂���";
      return false;
    }
    if(trim($pwd)==""){
      $this->error = "�p�X���[�h����͂��Ă�������";
      return false;
    }
    list($no,$oya,$root,,,,,,,,,$pass,) = explode("\t", $this->id[$id]);

    if($this->adm_pass == $pwd || substr(md5($pwd),2,8) == $pass){
      $this->id[$id] = "$no\t$oya\t$root\t�폜\t\t\t\t���̋L���͍폜����܂���\t\t\t\t\t\n";

      $fp = fopen($this->logfile, "w");
      flock($fp, LOCK_EX);
      fputs($fp, implode('', $this->id));
      fclose($fp);
    } else {
      $this->error = "�p�X���[�h���Ⴂ�܂�";
      return false;
    }

    return true;
  }
  /***
   * �ҏW
   *
   */
  function edit_msg($id, $pwd){
    if(!isset($this->id[$id])){
      $this->error = "�Y���L����������܂���";
      return false;
    }
    if(trim($pwd)==""){
      $this->error = "�p�X���[�h����͂��Ă�������";
      return false;
    }
    list($no,$oya,$root,$subj,$name,$email,,$com,$url,,,$pass,) = explode("\t", $this->id[$id]);

    if($this->adm_pass == $pwd || substr(md5($pwd),2,8) == $pass){
      $com = eregi_replace("<br( /)?>", "\r", $com);
      $html = tree::show_form($root, $oya, $subj, $com, "edit", $name, $email, $url, $no, $pwd);
    } else {
      $this->error = "�p�X���[�h���Ⴂ�܂�";
      return false;
    }

    return $html;
  }
  /***
   * ����
   *
   */
  function search($word, $andor){
    global $log;
 
    if(!isset($word)) return false;
    if(get_magic_quotes_gpc()) $word = stripslashes($word);
    $keys = preg_split("/(�@| )+/", trim($word));
    foreach($this->logarr as $line){
      $find = FALSE;
      for($i = 0; $i < count($keys); $i++){
        if($keys[$i]=="") continue;
        if(stristr($line,$keys[$i])){
          $find = TRUE;
          list($id,) = explode("\t", $line);
        }elseif($andor == "and"){
          $find = FALSE;
          break;
        }
      }
      if($find) $result[] = $id;
    }
    if(is_array($result)){
      $word = htmlspecialchars($word);
      $html.= "<center>$word �̌������� ".count($result)."��</center>\n";
      foreach($result as $no){
        $html.= tree::show_msg($no, 'dump');
      }
    }
    return $html;
  }
  /***
   * ���[�����M
   *
   */
  function send_mail($name, $from, $to, $subject, $body){
    if($subject!="") $subject = tree::mime_enc($subject);
    $body = str_replace("\r\n", "\n", $body); 
    $body = str_replace("\r", "\n", $body);
    //$str = mb_convert_encoding($str, "JIS", "SJIS");
    $name = tree::mime_enc($name);
    $froma = ($from) ? "$name<$from>" : "$name<root@".getenv("HTTP_HOST").">";
    $head = "From: $froma\n";
    $head.= "X-REF: ".getenv("HTTP_REFERER");

    @mail($to, $subject, $body, $head);
  }
  /***
   * MIME�G���R
   *
   */
  function mime_enc($str){
    //$str = mb_convert_encoding($str, "JIS", "SJIS");
    $encode = "=?iso-2022-jp?B?" . base64_encode($str) . "?=";    //B�w�b�_�{�G���R�[�h 
    return $encode; 
  }
  /***
   * �w�b�_�擾
   *
   */
  function show_head(){
    if(file_exists("dcount.php")) include("dcount.php");
    $this->head = str_replace("{today}", $today, $this->head);
    $this->head = str_replace("{yesterday}", $yesterday, $this->head);
    $this->head = str_replace("{total}", $total, $this->head);
    return $this->head;
  }
  /***
   * �t�b�^�擾
   *
   */
  function show_foot(){
    $foot = str_replace("</body>","</body><p align=right><small><a href=http://php.s3.to>���b�cPHP!</a></small>",$this->foot);//������
    return $foot;
  }
  /***
   * �c���[�\���z��擾
   *
   */
  function get_tree(){
    return $this->tree_arr;
  }
  /***
   * �w��No�̃f�[�^�擾
   *
   */
  function get_line($no){
    return explode("\t", $this->id[$no]);
  }
  /***
   * �N�b�L�[���擾
   *
   */
  function cookie_s(){
    return $this->cookie_s;
  }
  /***
   * �I�[�g�����N
   *
   */
  function auto_link($uri){
    $uri = ereg_replace("(^|[^=\"'])(https?|ftp|news)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)","\\1<a href=\"\\2\\3\" target=\"_blank\">\\2\\3</a>",$uri);
    return $uri;
  }
  /***
   * �G���[���b�Z�[�W
   *
   */
  function error_msg($msg='') {
    if(!$msg) $msg = $this->error;
    $html = "<br><table cellpadding=3 cellspacing=1 width=90% align=center class=brdr>
<tr><td class=bgb align=center><font color=cc0000>[ �G���[�I�I  ]</font></td></tr><tr><td class=bgc align=center><br><b>$msg</b><br><br><a href=javascript:history.go(-1)>�߂�</a></td></tr></table>\n";
    return $html;
  }
  /***
   * �R�}���h���s��̃W�����v
   *
   */
  function jump_url($url) {
    echo '<meta http-equiv="refresh" content="0;url='.$url.'">';
  }
}

/*-------------���C��------------*/
$d = new tree($mode, $log);
$d->get_arr();

switch($act) {
  case 'post':
    if($dat = $d->add_chk($st, $re, $name, $email, $sub, $com, $url, $pass, $email_reply)){
      $d->add_post($dat);
      $d->jump_url($PHP_SELF);
    } else {
      echo $d->show_head();
      echo $d->error_msg();
      echo $d->show_foot();
    }
    exit;
  case 'editpost':
    if($dat = $d->add_chk($st, $re, $name, $email, $sub, $com, $url, $pass, $email_reply, $editno)){
      $d->edit_post($dat);
      $d->jump_url($PHP_SELF);
    } else {
      echo $d->show_head();
      echo $d->error_msg();
      echo $d->show_foot();
    }
    exit;
  case 'del':
    if(!$d->del_msg($id, $pwd)){
      echo $d->show_head();
      echo $d->error_msg();
      echo $d->show_foot();
    } else {
      $d->jump_url($PHP_SELF);
    }
    exit;
  case 'edit':
    if($html = $d->edit_msg($id, $pwd)){
      echo $d->show_head();
      echo $html;
      echo $d->show_foot();
    } else {
      echo $d->show_head();
      echo $d->error_msg();
      echo $d->show_foot();
    }
    exit;
  case 'setup_cookie':
    $cookvalue = implode(",", array($view,$pagetree,$pageexpn,$pageroot,$pagedump));
    setcookie($d->cookie_s(), $cookvalue,time()+365*24*3600);
    $d->jump_url($PHP_SELF);
    exit;
}

echo $d->show_head();
echo $d->show_navi();

  /* �L���P�ƕ\�� */
  if(is_numeric($n)){
    echo $d->show_msg($n,'dump');
    list($id, $oya, $root,$subj,,,,$com) = $d->get_line($n);
    echo $d->n_tree_st;
    $d->make_tree($root);
    $trees = $d->get_tree();
    foreach($trees as $val){
     echo $d->show_msg($val[0],'tree',$val[1]);
    }
    echo $d->n_tree_to;
    echo $d->show_form($root, $id, $subj, $com, "quote");
    echo $d->show_del($n);
    echo $d->show_foot();
    exit;
  }
  /* �c���[�P�ƕ\�� */
  if(is_numeric($all)){
    $d->make_tree($all);
    $trees = $d->get_tree();
    $d->all_tree_st = str_replace("{id}", "$all", $d->all_tree_st);
    echo $d->all_tree_st;
    foreach($trees as $val){
     echo $d->show_msg($val[0],'tree',$val[1]);
    }
    echo $d->all_tree_to;
    $trees = $d->get_tree();
    foreach($trees as $val){
     echo $d->show_msg($val[0],'dump');
    }
    echo $d->show_foot();
    exit;
  }
  switch($mode){
    case 'form':
      echo $d->show_form();
      break;
    case 'search':
      echo $d->show_search($word);
      echo $d->search($word, $andor);
      break;
    case 'setup':
      echo $d->show_setup();
      break;
    case 'past':
      echo $d->show_past();
      break;
    default:
      echo $d->show_main($page);
  }
echo $d->show_navi();
echo $d->show_foot();
?>