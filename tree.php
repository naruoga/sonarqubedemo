<?php
session_start();

// アクセス拒否ホスト 後方一致
$deny = array("kinja.com",".br",".sa",".pl",".it");

$host = gethostbyaddr(getenv("REMOTE_ADDR"));
foreach ($deny as $denyhost) {
  if (eregi("$denyhost$", $host)) {
    // 指定ページに飛ばす
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
 * ツリー掲示板です。
 * ・いろんな表示モードが選べます
 * ・クッキーによりデフォルトの表示モードと件数が設定できます
 * ・検索機能があります
 * ・二重投稿・連続投稿防止機能があります
 * ・禁止語句を**に変換します
 * ・許可タグを指定できます
 * ・新着記事にはNew等の文字を表示できます
 * ・ツリーの深さを制限できます
 * ・好きなデザインにしやすいです
 *
 * 準備
 * ・空のログファイルを用意して書き込み属性を与えて下さい
 * ・過去ログを使う場合は過去ログディレクトリを書き込み可にして、
 * 　past.txtを用意して中に1とだけ書いて保存し、書き込み可にして下さい。
 *
  * 6/17 過去ログ修正、検索可
  * 04/2/22 レス数制限、script除去追加
  * 04/08/14  Line943: /&lt;($tags)(.*?)&gt;/si → /&lt;($tags)\b(.*?)&gt;/si
  * 09/06/22 XSS脆弱性修正
 */
class tree{
  var $logarr	= array();
  var $id	= array();
  var $thread	= array();
  var $root	= array();
  var $tree_arr = array();
  var $error	= "";

  /* 設定ここから */
  // ツリー記号
  var $i_gif = "│";
  var $t_gif = "├";
  var $l_gif = "└";
  var $nogif = "　";
  // ログファイル名
  var $logfile = "log.log";
  // ログ最大行
  var $max_log   = 100;
  // 過去ログ機能を使う？YES=1,No=0
  var $past      = 0;
  // 過去ログNoファイル(初期値は1)(1.log 2.logを作成
  var $pastno    = "past.txt";
  // 過去ログ保存ディレクトリ(書き込み可能にする。707等
  var $pastdir   = "./dat/";
  // コメント最大行
  var $max_line  = 25;
  // 投稿フォーム表示から投稿までの制限秒
  var $posttime  = 3;
  // 連続投稿制限秒
  var $renzoku   = 10;
  // 最新？時間内の投稿にNew記号
  var $expire    = 24;
  // ツリーの深さ制限
  var $max_depth = 20;
  // ツリーのレス数制限
  var $max_res   = 50;
  // ツリー時の一行表示文字数（記号+題名+文字を半角で）
  var $cut_size  = 60;
  // 許可するタグ（本文のみ）
  var $allow_tag = "<b><i><s><p>";
  // 禁止する文字（**に変換）
  var $bad_word  = "href=|url=|バカ|死ね|氏ね|架空口座|販売|精力|媚薬|中絶|http://";
  // >引用がついた時の色
  var $quote	= "#aaaacc";
  // 投稿があると管理人にメールで通知する場合のアドレス（不要なら空）
  var $adm_mail = "";
  // 管理人に来るメールのsubject（{id},{subj}使用可）
  var $adm_subj = "[BBS-{id} {subj} ]";
  // 管理人パスワード。単独記事表示の削除フォームから
  var $adm_pass = "pass1212";
  // 初期表示モード(tree=ツリー,expn=スレッド,root=タイトル,dump=一覧
  var $mode = "root";
  // 表示件数の初期状態
  var $page_tree = 10;
  var $page_expn = 5;
  var $page_root = 15;
  var $page_dump = 20;
  // クッキー保存名
  var $cookie_n  = "treebbs_cookie";
  var $cookie_s  = "treebbs_setting";
  // ヘッダ部分
  var $head = '<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
  <title>サポート掲示板</title>
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
  <center><h2>サポート掲示板</h2><br>今日：{today} 昨日：{yesterday} 合計：{total}</center>';

  /** 変数説明
    * {id}-記事No {subj}-題名 {name}-投稿者 {date}-投稿日 {com}-本文 {host}-ホスト {time}-投稿time
    * {n}-単独記事表示リンク {all}-ツリー一覧表示リンク {reply}-返信リンク {nl}で\n（''で囲った時用）
    * {new}で新着$new_msg表示 {email}で以下の$if_email文字にリンク {url}で以下の$if_url文字にリンク
    */
  // タイトル表示ヘッダ
  var $root_head = "<br><table align=center width=90% cellpadding=3 cellspacing=1 class=brdr><tr class=bga><td align=center>No.</td><td>タイトル</a></td><td align=center>投稿者</td><td align=center>レス</td><td>最終更新</td></tr>\n";
  // タイトル表示本文 {lname}最新投稿者 {ldate}最新投稿日 {lcom} 最新本文
  var $root_msg = "<tr class=bgc><td align=center>{num}</td><td><a href={all}>{subj}</a></td><td align=center>{name}</td><td align=center>{res}</td><td><small>{ldate} <br>by {lname} {host}</small></td></tr>\n";
  // タイトル表示フッタ
  var $root_foot = "</table><br>\n";

  // ツリー表示ヘッダ
  var $tree_head = "<table width=90% align=center class=list><tr><td>";
  // ツリー表示親記事
  var $tree_oya = "<hr size=1><a href={all}>▼</a>-<a href={n}>{subj}</a> [{name}] <font color=gray size=2>({date})</font><br\n>";
  // ツリー表示子記事
  var $tree_res = "<a href={n}>{subj}</a> [{name}] <font color=gray size=2>({date})</font> {new}<br\n>";
  // ツリー表示フッタ
  var $tree_foot = "</td></tr></table><hr size=1 width=90%>\n";

  // 展開表示親記事
  var $expn_oya = "<a name={num}><table cellpadding=5 cellspacing=1 width=90% align=center class=brdr><tr><td class=bgb> {num}: <font size=4 color=4444aa>{subj}</font></td></tr><tr><td class=bgc><tt>Name: {name} {url} {email}<br>Date: {date}</tt><blockquote>{com}</blockquote>
<div align=right>No.{id}<a href={reply}>返信する</a></div><ul>\n";
  // 展開表示子記事
  var $expn_res = "<hr size=1> [{id}] {subj}<br><tt>Name: {name}  {url} {email}<br>Date: {date}</tt><blockquote>{com}</blockquote>
<div align=right><a href={reply}>返信する</a></div>\n";
  // 展開表示テーブル閉じ
  var $expn_bott = "</ul></td></tr></table><br>\n";

  // 一覧・単独表示
  var $dump_msg = "<br><a name={id}><table cellpadding=3 cellspacing=1 width=90% align=center class=brdr>
<tr><td class=bgb> [<a href={n}>{id}</a>] <font size=4 color=4444aa>{subj}</font></td></tr><tr><td class=bgc><tt>Name: {name} {url} {email}<br>Date: {date}</tt><blockquote>{com}</blockquote>
<p align=right><a href={reply}>返信する</a></p></td></tr></table>\n";

  //単独表示時関連ツリー開始・終了タグ
  var $n_tree_st = "<table width=90% align=center class=slist><tr><td><hr size=1>関連ツリー\n";
  var $n_tree_to = "</td></tr></table><hr size=1 width=90%>\n";
  //ツリー一括時ツリー開始・終了タグ
  var $all_tree_st = "<table width=90% align=center class=slist><tr><td>No.{id}に関するツリー";
  var $all_tree_to = "</td></tr></table>\n";

  // フッタ部分
  var $foot = "\n<hr size=1></body></html>";

  // 新着記事記号
  var $new_msg = "<font color=dd4444 size=1>New!</font>";
  // E-Mailがあるとき{email}のリンク文字（空なら名前にリンク）
  var $if_email = "E-MAIL";
  // URLがあるとき{url}のリンク文字
  var $if_url = "(HOME)";

  /***
   * コンストラクタ
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
   * なびめにゅう表示
   *
   */
  function show_navi(){
    global $PHP_SELF,$all,$n,$log;
    if($log) $kako = "&log=$log";

    $form = ($this->mode == "form" && !$all && !$n) ? "新規投稿" : "<a href=$PHP_SELF?mode=form{$kako}>新規投稿</a>";
    $tree = ($this->mode == "tree" && !$all && !$n) ? "ツリー" : "<a href=$PHP_SELF?mode=tree{$kako}>ツリー</a>";
    $expn = ($this->mode == "expn" && !$all && !$n) ? "スレッド" : "<a href=$PHP_SELF?mode=expn{$kako}>スレッド</a>";
    $root = ($this->mode == "root" && !$all && !$n) ? "タイトル" : "<a href=$PHP_SELF?mode=root{$kako}>タイトル</a>";
    $dump = ($this->mode == "dump" && !$all && !$n) ? "一覧" : "<a href=$PHP_SELF?mode=dump{$kako}>一覧</a>";
    $search = ($this->mode == "search") ? "検索" : "<a href=$PHP_SELF?mode=search{$kako}>検索</a>";
    if ($this->past) $past_m = "<a href=$PHP_SELF?mode=past>ログ</a>　｜　";
    $setup = ($this->mode == "setup") ? "設定" : "<a href=$PHP_SELF?mode=setup>設定</a>";

    $html = "<br><table border=0 cellspacing=1 cellpadding=1 class=brdr align=center width=90%>
<tr><td align=center class=bga>　{$form}　｜　{$tree}　｜　{$expn}　｜　{$root}　｜　{$dump}　｜　{$search}　｜　{$past_m}{$setup}　</td></tr></table><br>\n";
    return $html;
  }
  /***
   * 改ページ表示
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
    $html.= "<center>[ {$prev_st}前の {$limit} 件{$prev_to} | {$offset} /{$num_page}ページ | {$next_st}次の {$limit} 件{$next_to} ]</center>\n";

    return $html;
  }
  /***
   * ヘッドライン表示
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
      if($i < $st+$limit) $html.= "<a href=#$num>".$num.": ".$subj."(".$resc.")</a>　\n";
      else $html.= "<small><a href=$PHP_SELF?all=$id$kako><font color=6666ff>".$num.": ".$subj."(".count($this->thread[$id]).")</font></a></small>　";
    }
    $html.= "</td></tr></table><br>\n";

    return $html;
  }
  /***
   * 投稿フォーム表示
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
    if($this->allow_tag) $tag = "タグ使用可 ".htmlspecialchars($this->allow_tag);
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
    if($mode == "quote") $html.= ($root) ? "返信フォーム <small>[<a href='#frm' onClick='Inyou()'>引用</a>]</small>" : "新規投稿フォーム";
    if($mode == "edit") $html.= "編集フォーム";
    $html.= "
    </td>
</tr>
<tr><td><br></td></tr>
<tr>
    <td class=bga>&nbsp;名前</td>
    <td><input type=text name=name size=30 maxlength=100 value='$c_name' class=input> *</td>
</tr>
<tr>
    <td class=bga>&nbsp;E-Mail</td>
    <td><input type=text name=email size=30 maxlength=100 value='$c_email' class=input></td>
</tr>
<tr>
    <td class=bga>&nbsp;題名</td>
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
    <input type=checkbox name=email_reply value=y> レスが付けば上記のEmailアドレスにメールで知らせる</td>
</tr>
<tr>
    <td colspan=2><br> 
    <input type=submit value='  投稿する  ' class=submit> <input type=reset value='  クリア  ' class=submit>
 </table></form>";

    return $html;
  }
  /***
   * 検索フォーム表示
   *
   */
  function show_search($word){
    global $PHP_SELF,$log;

    if(get_magic_quotes_gpc()) $word = stripslashes($word);
    $word = htmlspecialchars($word);
    if (is_numeric($log)) {
      $num = sprintf("%03d",$log);
      $kako = "過去ログ $num から";
      $hide = "<input type=hidden name=log value='{$log}'>";
    }

    $html = "<br><table cellpadding=3 cellspacing=1 width=45% align=center class=brdr>
    <tr><td class=bgb align=center><font size=3><b>[ {$kako}記事検索 ]</b></font></td></tr>
    <tr><td class=bgc align=center><br>
    <form method=post action='{$PHP_SELF}'>{$hide}
    <input type=hidden name=mode value='search'>
     <input type=text name=word size=20 value=\"{$word}\" class=input><select name=andor style='background-color:#eee;color:#444;'>
       <option value=and selected>AND</option>
       <option value=or>OR</option>
     </select>
     <input type=submit class=submit value='  検索  ' class=submit>
     </form></td></tr></table><br><br>";

    return $html;
  }
  /***
   * 設定フォーム表示
   *
   */
  function show_setup(){
    global $PHP_SELF,${$this->cookie_s};

    list($view,) = explode(",", ${$this->cookie_s});
    $$view = " selected";
    if(!$view) $tree = " selected";

    $html = "<br><table cellpadding=3 cellspacing=1 width=60% align=center class=brdr>
    <tr><td class=bgb align=center><font size=3><b>[ 環境設定 ]</b></font></td></tr>
    <tr><td class=bgc align=center><br>
    <form method=post action='$PHP_SELF'>
    <input type=hidden name=act value='setup_cookie'>
     <table width=90%><tr><td>デフォルトの表示モード  </td><td>
     <select name=view style='background-color:#eee;color:#444;'>
       <option value=tree$tree>ツリー表示</option>
       <option value=expn$expn>展開表示</option>
       <option value=root$root>タイトル表示</option>
       <option value=dump$dump>一覧表示</option>
     </select>
      </td></tr><tr><td colspan=2><hr size=1></td></tr>
      <tr><td>ツリー表示時の１ページの表示数</td>
      <td><input type=text name=pagetree size=8 value=$this->page_tree></td></tr>
      <tr><td>展開表示時の１ページの表示数</td>
      <td><input type=text name=pageexpn size=8 value=$this->page_expn></td></tr>
      <tr><td>タイトル表示時の１ページの表示数</td>
      <td><input type=text name=pageroot size=8 value=$this->page_root></td></tr>
      <tr><td>一覧表示時の１ページの表示数</td>
      <td><input type=text name=pagedump size=8 value=$this->page_dump></td></tr>
      <tr><td colspan=2><hr size=1></td></tr>
      <tr><td colspan=2 align=center><input type=submit class=submit value='  設定  ' class=submit>
      </td></tr></table>
     </form></td></tr></table><br><br>";

    return $html;
  }
  /***
   * 過去ログフォーム
   *
   */
  function show_past(){
    global $PHP_SELF;

    $html = "<br><table cellpadding=3 cellspacing=1 width=60% align=center class=brdr>
    <tr><td class=bgb align=center><font size=3><b>[ ログ選択 ]</b></font></td></tr>
    <tr><td class=bgc align=center>古い順に並んでいます<br><br>";

    $no = file($this->pastno);
    for($i=1; $i<=$no[0]; $i++){
      $num = sprintf("%03d",$i);
      if(file_exists($this->pastdir.$i.".log")){
        $html.="<a href=$PHP_SELF?log=$i>過去ログ $num </a><br>";
      }
    }

    $html.="<a href=$PHP_SELF?>現在のログ</a><br><br></td></tr></table><br><br>";

    return $html;
  }
  /***
   * 削除フォーム
   *
   */
  function show_del($n){
    global $PHP_SELF,${$this->cookie_n};

    list(,,,$pass) = explode(",", ${$this->cookie_n});

    $html = "<hr size=1><div align=right>
    <form method=post action='$PHP_SELF'>
    <input type=hidden name=id value='$n'>
    Pass <input type=password size=8 name=pwd value='$pass'><select name=act>
    <option value='del'>削除<option value='edit'>編集</select>
    <input type=submit value='実行'>
    </form></div>";

    return $html;
  }    
  /***
   * ログを配列に
   *
   */
  function get_arr(){
    for($i = 0; $i < count($this->logarr); $i++){
      //記事番号\t親記事番号\t根記事番号
      list($id, $oya, $root,) = explode("\t", $this->logarr[$i]);
      //$this->id : 記事番号をキーにしたログデータ配列
      $this->id[$id] = $this->logarr[$i];
      //$this->thread : スレッド構造の配列。値は記事番号
      $this->thread[$root][$oya][] = $id;
      //$this->root : 根記事のみの配列。ログ順
      if($oya==0)  $this->root[] = $id;
    }
    //print_r($this->thread);
  }
  /***
   * メインHTML生成処理
   *
   */
  function show_main($page){
    if(intval($page) == 0) $page = 1;//最初はページ1
    //ヘッダ部分
    switch($this->mode){
      //タイトルモード
      case 'root':
        //ページング生成
        $jump = tree::show_jump($page, $this->page_root, count($this->root));
        //ヘッダHTML
        $html = $jump . $this->root_head;
        //1ページ表示数決定
        $page_def = $this->page_root;
        break;
      //展開モード
      case 'expn':
        $jump = tree::show_jump($page, $this->page_expn, count($this->root));
        //ヘッドライン
        $html = $jump . tree::show_headline($page, $this->page_expn);
        $page_def = $this->page_expn;
        break;
      //ツリーモード
      case 'tree':
        $jump = tree::show_jump($page, $this->page_tree, count($this->root));
        $html = $jump . $this->tree_head;
        $page_def = $this->page_tree;
        break;
      //一覧モード
      case 'dump':
        $jump = tree::show_jump($page, $this->page_dump, count($this->id));
        $html = $jump;
        //最新番号取得
        list($st,) = explode("\t", $this->logarr[0]);
        //データ開始位置
        $st = ($page) ? $st - ($page-1) * $this->page_dump : $st;
        //最新記事順に表示
        for($i = $st; $i > $st-$this->page_dump; $i--){
          $html.= tree::show_msg($i, $this->mode);
        }
        //フッタ表示で終了
        $html.= "<br>" . $jump;
        return $html;
        break;
    }
    //データ開始位置
    $st = ($page) ? ($page-1) * $page_def : 0;
    //親記事番号でループ
    for($i = $st; $i < $st+$page_def; $i++){
      if($this->root[$i]=="") continue;
      switch($this->mode){
        //タイトルモード
        case 'root':
          $html.= tree::show_msg($this->root[$i], 'root');
          $html = str_replace('{num}', $i+1, $html);
          break;
        //展開モード
        case 'expn':
          //ツリー構造を初期化
          $this->tree_arr = array();
          //ツリー生成
          tree::make_tree($this->root[$i]);
          //ツリー順に表示
          foreach($this->tree_arr as $val){
            $html.= tree::show_msg($val[0], 'expn');
            $html = str_replace('{num}', $i+1, $html);
          }
          //テーブル閉じ
          $html.= $this->expn_bott;
          break;
        //ツリーモード
        case 'tree':
          $this->tree_arr = array();
          tree::make_tree($this->root[$i]);
          foreach($this->tree_arr as $val){
            $html.= tree::show_msg($val[0], 'tree', $val[1]);
          }
          break;
      }
    }
    //フッタ部分
    switch($this->mode){
      case 'root':
        $html.= $this->root_foot;
        break;
      case 'tree':
        $html.= $this->tree_foot;
        break;
    }
    //ページング表示
    $html.= $jump;

    return $html;
  }
  /***
   * ツリーの生成、並び替え
   *
   */
  function make_tree($id, $oya=0, $pre="", $img=""){
    //前ツリーがLならその下は空
    if($img == $this->l_gif) $pre.= $this->nogif;
    //前ツリーがTならその下はI
    if($img == $this->t_gif) $pre.= $this->i_gif;
    //ツリー構造なら
    if(is_array($this->thread[$id][$oya])){
      foreach($this->thread[$id][$oya] as $no){
        //下ツリーがあればT記号終わってればL記号
        $img = (count($this->thread[$id][$oya])>1) ? $this->t_gif : $this->l_gif;
        //子記事は後ろに送る
        $no = ($oya==0) ? array_shift($this->thread[$id][$oya]) : array_pop($this->thread[$id][$oya]);
        //ツリー構造と記号を配列に
        $this->tree_arr[] = array($no,"$pre$img");
        //再帰
        tree::make_tree($id,$no,$pre,$img);
      }
      return true;
    }else{
      return false;
    }
  }
  /***
   * HTML生成・テンプレ置換
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
    // 自動リンク
    $com = tree::auto_link($com);
    // ＞がある時は色変更
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
   * 書き込み前チェック
   *
   */
  function add_chk($root=0, $oya=0, $name, $email, $subj, $com, $url, $pass, $email_reply, $no=''){
    global $REQUEST_METHOD,$PHP_SELF;

    if ($_SESSION['time'] == "") {
      $this->error = "不正な投稿をしないで下さい";
      return false;
    }
    if (time() - $_SESSION['time'] < $this->posttime) {
      $this->error = "投稿はもうしばらく時間を置いてからお願い致します";
      return false;
    }
    // 数字化
    if (!is_numeric($root) || !is_numeric($oya)) {
      $this->error = "不正な投稿をしないで下さい";
      return false;
    }
    if ($no != "") $no = intval($no);

    if (preg_match("/(<a\b[^>]*?>|\[url(?:\s?=|\]))|href=/i", $com)) {
      $this->error = "禁止ワードエラー！！";
      return false;
    }
    if($REQUEST_METHOD != "POST") { 
      $this->error = "不正な投稿をしないで下さい";
      return false;
    }
    if($url && !ereg("^http://", $url)){
      $this->error = "URLはhttp://から記入してください";
      return false;
    }
    if($email && !ereg(".+@.+\\..+", $email)){
      $this->error = "メールアドレスは正しく記入してください";
      return false;
    }
    if($name=="" || ereg("^( |　)*$",$name)){
      $this->error = "名前が書き込まれていません";
      return false;
    }
    if($subj=="" || ereg("^( |　)*$",$subj)){
      $this->error = "題名が書き込まれていません";
      return false;
    }
    if($com=="" || ereg("^( |　|[\r\n])*$",$com)){
      $this->error = "本文が書き込まれていません";
      return false;
    }
    // 親チェック、深さチェック、レス数チェック
    if($root != 0 && $no == ''){
      if(!is_array($this->thread[$root])){
        $this->error = "返信先の記事が見つかりませんでした";
        return false;
      }
      if(count($this->thread[$root]) > $this->max_depth){
        $this->error = "ツリー階層が深くなりすぎたので<br>これ以上返信できません";
        return false;
      }
      foreach($this->thread[$root] as $kidno){
        $resc += count($kidno);
      }
      if($resc > $this->max_res){
        $this->error = "レス数制限に達したので<br>これ以上返信できません";
        return false;
      }
    }
    if($no == "kako"){
      $this->error = "過去ログには投稿できません";
      return false;
    }
    //現在時刻
    $tim = time();
    // ホスト名を取得
    $ip = (getenv("HTTP_X_FORWARDED_FOR") != "") ? getenv("HTTP_X_FORWARDED_FOR") : getenv("REMOTE_ADDR");
    $host = gethostbyaddr($ip);
    //同一ホストからの連続投稿禁止
    list($lno,,,,$lname,,,$lcom,,$ltime,$lhost,) = explode("\t", $this->logarr[0]);
    if($this->renzoku && $host==$lhost && $tim - $ltime < $this->renzoku){
      $this->error = "連続投稿はもうしばらく時間を置いてからお願い致します";
      return false;
    }
    //時間のフォーマット
    $date = gmdate("m/d H:i",$tim+9*60*60);
    //url整形
    $url = trim($url);
    $url = ereg_replace("^http://", "", $url);
    $url = str_replace(" ", "%20", $url);
    //PW暗号化
    if(trim($pass)!="") $pwd = substr(md5($pass),2,8);
    //テキスト整形
    $subj = tree::CleanStr($subj);
    $name = tree::CleanStr($name);
    $email= tree::CleanStr($email);
    $url  = tree::CleanStr($url);
    $com  = tree::CleanStr($com,1);
    // 改行文字の統一。 
    $com = str_replace("\r\n", "\n", $com); 
    $com = str_replace("\r", "\n", $com);

    if(substr_count($com, "\n") > $this->max_line){
      $this->error = "コメント行数が長すぎます";
      return false;
    }
    // 連続する空行を一行・\n→<br />\n→\n除去
    $com = ereg_replace("\n((　| )*\n){3,}","\n",$com);
    $com = nl2br($com);
    $com = str_replace("\n",  "", $com);

    // 二重投稿チェック
    if($no == '' && $name == $lname && $com == $lcom){
      $this->error = "二重投稿は禁止です";
      return false;
    }
    // 新NO、親は自分がroot
    if($no == ''){
      $no = $lno + 1;
      if($root == 0) $root = $no;
    } elseif(!$this->id[$no]){
      $this->error = "該当記事がみつかりません";
      return false;
    }

    $no = intval($no);
    $oya = intval($oya);
    $root = intval($root);
    $dat = array($no,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,$pwd,$email_reply);

    // クッキー保存
    $cookvalue = implode(",", array($name,$email,$url,$pass));
    setcookie ($this->cookie_n, $cookvalue,time()+14*24*3600);

    return $dat;
  }
  /***
   * 新規書き込み
   *
   */
  function add_post($dat){

    list($no,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,$pwd,$email_reply) = $dat;

    // データフォーマット
    $new_msg = implode("\t", $dat)."\n";
//    $new_msg = "$no\t$oya\t$root\t$subj\t$name\t$email\t$date\t$com\t$url\t$tim\t$host\t$pwd\t$email_reply\n";

    $mailbody = str_replace("&gt;", ">", $com);
    $mailbody = str_replace("&lt;", "<", $mailbody);
    $mailbody = str_replace("&quote;", '"', $mailbody);
    $mailbody = str_replace("&amp;", "&", $mailbody);
    $mailbody = eregi_replace("<br( /)?>", "\n", $mailbody);
    

    // 管理人にメール通知
    if($this->adm_mail && ereg(".+@.+\\..+", $this->adm_mail)){
      $subject = str_replace("{id}", "$no", $this->adm_subj);
      $subject = str_replace("{subj}", $subj, $subject);
      $body = "Date: $date\nHost: $host\n";
      $body.= "---------------------------------------------\n";
      $body.= $mailbody;
      tree::send_mail($name, $email, $this->adm_mail, $subject, $body);
    }
    // 新ログ配列生成
    if($oya != 0){
      // レスがあったらメール
      list(,,,,,$o_email,,,,,,,$reply) = explode("\t", $this->id[$oya]);
      if(rtrim($reply) == "y" && ereg(".+@.+\\..+", $o_email)){
        $subject = $no."-".$subj;
        $body = "$name さんからのレス\n";
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

    // 過去ログ
    if($this->past){
      // ログ数オーバーは過去ログに書き出す
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
        // 現No取得
        $no = file($this->pastno);
        // 現過去ログ読み込み
        if(file_exists($this->pastdir.$no[0].".log")) {
          $pastline = file($this->pastdir.$no[0].".log");
        }
        // 現過去ログ行数オーバーならNOアップ
        if(count($pastline) > $this->max_log){
          $no[0]++;
          $fp = fopen($this->pastno, "w");
          fputs($fp, $no[0]);
          fclose($fp);
        }else{
          $backline = array_merge($backline, $pastline);
        }
        // 過去ログ更新
        $fp = fopen($this->pastdir.$no[0].".log", "w");
        flock($fp, LOCK_EX);
        fputs($fp, implode('', $backline));
        fclose($fp);
      }
    }
    // ログ書き込み
    $fp = fopen($this->logfile, "w");
    flock($fp, LOCK_EX);
    fputs($fp, implode('', $this->logarr));
    fclose($fp);

    return true;
  }
  /***
   * 編集書き込み
   *
   */
  function edit_post($dat){

    list($no,$oya,$root,$subj,$name,$email,$date,$com,$url,$tim,$host,$pwd,$email_reply) = $dat;

    // データフォーマット
    $new_msg = implode("\t", $dat)."\n";

    for($i = 0; $i < count($this->logarr); $i++){
      list($id,) = explode("\t", $this->logarr[$i]);
      if($id == $no){
        $this->logarr[$i] = $new_msg;
        break;
      }
    }
    // ログ書き込み
    $fp = fopen($this->logfile, "w");
    flock($fp, LOCK_EX);
    fputs($fp, implode('', $this->logarr));
    fclose($fp);

    return true;
  }
  /***
   * タグ・￥除去
   *
   */
  function CleanStr($str,$com=0){
    //先頭と末尾の空白除去
    $str = trim($str);
    //区切り文字\t変換
    $str = str_replace("\t", "    ", $str); 
    //￥を削除
    if(get_magic_quotes_gpc()){
      $str = stripslashes($str);
    }
    if(isset($this->bad_word)) $str = ereg_replace($this->bad_word, "**", $str);
    if($this->allow_tag && $com == 1){
      //許可タグ以外除去
     // $str = strip_tags($str, $this->allow_tag);
      $str = htmlspecialchars($str);
      $tags = str_replace("><", "|", $this->allow_tag);
      $tags = ereg_replace("[<>]", "", $tags);
      $str = preg_replace("/&lt;($tags)\b(.*?)&gt;/si", "<\\1\\2>", $str);
      $str = eregi_replace("&lt;\/($tags)&gt;", "</\\1>", $str);
      $str = eregi_replace("<(.*)(style|onmouse|onclick|script)[^=]*=", "<\\1", $str);
    }else{
      //タグ変換
      $str = htmlspecialchars($str);
    }
    if ($com == 0) {
      $str = eregi_replace("[\r\n]", "", $str);
    }
    //特殊文字
    $str = str_replace("&amp;#", "&#", $str);
    $str = str_replace("&quot;", '"', $str);

    return $str;
  }
  /***
   * 削除
   *
   */
  function del_msg($id, $pwd){
    if(!isset($this->id[$id])){
      $this->error = "該当記事が見つかりません";
      return false;
    }
    if(trim($pwd)==""){
      $this->error = "パスワードを入力してください";
      return false;
    }
    list($no,$oya,$root,,,,,,,,,$pass,) = explode("\t", $this->id[$id]);

    if($this->adm_pass == $pwd || substr(md5($pwd),2,8) == $pass){
      $this->id[$id] = "$no\t$oya\t$root\t削除\t\t\t\tこの記事は削除されました\t\t\t\t\t\n";

      $fp = fopen($this->logfile, "w");
      flock($fp, LOCK_EX);
      fputs($fp, implode('', $this->id));
      fclose($fp);
    } else {
      $this->error = "パスワードが違います";
      return false;
    }

    return true;
  }
  /***
   * 編集
   *
   */
  function edit_msg($id, $pwd){
    if(!isset($this->id[$id])){
      $this->error = "該当記事が見つかりません";
      return false;
    }
    if(trim($pwd)==""){
      $this->error = "パスワードを入力してください";
      return false;
    }
    list($no,$oya,$root,$subj,$name,$email,,$com,$url,,,$pass,) = explode("\t", $this->id[$id]);

    if($this->adm_pass == $pwd || substr(md5($pwd),2,8) == $pass){
      $com = eregi_replace("<br( /)?>", "\r", $com);
      $html = tree::show_form($root, $oya, $subj, $com, "edit", $name, $email, $url, $no, $pwd);
    } else {
      $this->error = "パスワードが違います";
      return false;
    }

    return $html;
  }
  /***
   * 検索
   *
   */
  function search($word, $andor){
    global $log;
 
    if(!isset($word)) return false;
    if(get_magic_quotes_gpc()) $word = stripslashes($word);
    $keys = preg_split("/(　| )+/", trim($word));
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
      $html.= "<center>$word の検索結果 ".count($result)."件</center>\n";
      foreach($result as $no){
        $html.= tree::show_msg($no, 'dump');
      }
    }
    return $html;
  }
  /***
   * メール送信
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
   * MIMEエンコ
   *
   */
  function mime_enc($str){
    //$str = mb_convert_encoding($str, "JIS", "SJIS");
    $encode = "=?iso-2022-jp?B?" . base64_encode($str) . "?=";    //Bヘッダ＋エンコード 
    return $encode; 
  }
  /***
   * ヘッダ取得
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
   * フッタ取得
   *
   */
  function show_foot(){
    $foot = str_replace("</body>","</body><p align=right><small><a href=http://php.s3.to>レッツPHP!</a></small>",$this->foot);//消すな
    return $foot;
  }
  /***
   * ツリー構造配列取得
   *
   */
  function get_tree(){
    return $this->tree_arr;
  }
  /***
   * 指定Noのデータ取得
   *
   */
  function get_line($no){
    return explode("\t", $this->id[$no]);
  }
  /***
   * クッキー名取得
   *
   */
  function cookie_s(){
    return $this->cookie_s;
  }
  /***
   * オートリンク
   *
   */
  function auto_link($uri){
    $uri = ereg_replace("(^|[^=\"'])(https?|ftp|news)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)","\\1<a href=\"\\2\\3\" target=\"_blank\">\\2\\3</a>",$uri);
    return $uri;
  }
  /***
   * エラーメッセージ
   *
   */
  function error_msg($msg='') {
    if(!$msg) $msg = $this->error;
    $html = "<br><table cellpadding=3 cellspacing=1 width=90% align=center class=brdr>
<tr><td class=bgb align=center><font color=cc0000>[ エラー！！  ]</font></td></tr><tr><td class=bgc align=center><br><b>$msg</b><br><br><a href=javascript:history.go(-1)>戻る</a></td></tr></table>\n";
    return $html;
  }
  /***
   * コマンド実行後のジャンプ
   *
   */
  function jump_url($url) {
    echo '<meta http-equiv="refresh" content="0;url='.$url.'">';
  }
}

/*-------------メイン------------*/
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

  /* 記事単独表示 */
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
  /* ツリー単独表示 */
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