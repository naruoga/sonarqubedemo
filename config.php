<?php
// ��M���[���T�[�o�[�̐ݒ�
// POP3�T�[�o�[
$host = "localhost";
// ���[�U�[ID
$user = "���[��user";
// �p�X���[�h
$pass = "���[��pass";

// �摜�ۑ��ިڸ�؁i�p�[�~�b�V����777���ɕύX�j
$tmpdir  = "./data/";
// ���O�t�@�C���̏ꏊ�i�p�[�~�b�V����666���ɕύX�j
$log     = "./mail.cgi";
// �X�V��̃W�����v��i�\���X�N���v�gURL�j
$jump    = "http://php.s3.to/php/mailbbs.php";

//�\���ݒ�
// 1�y�[�W�̕\�����i�ʏ�j
$page_def_pc = 7;
// 1�y�[�W�̕\�����i�g�сj
$page_def_i = 7;
// 1�y�[�W�̕\�����i�Ǘ��j
$page_def_admin = 15;
// �폜�p�X
$delpass  = "127834";
// ���t�̃t�H�[�}�b�g http://jp2.php.net/date
$format   = "y/m/d G:i";

// ���O�ۑ�����
$maxline = 100;
// �ő�Y�t�ʁi�o�C�g�E1�t�@�C���ɂ��j����������͕̂ۑ����Ȃ�
$maxbyte = 102400; //100KB
// �ő�{���������i���p��
$maxtext = 1000;
// �Ή�MIME�T�u�^�C�v�i���K�\���jContent-Type: image/jpeg�̌��̕����Boctet-stream�͊댯����
$subtype = "gif|jpe?g|png|bmp|pmd|mld|mid|smd|smaf|mpeg|kjx|3gpp";

// ���e�񋖉A�h���X�i���O�ɋL�^���Ȃ��j
$deny_from = array('163.com','bigfoot.com','boss.com');
// ���e�񋖉����i���O�ɋL�^���Ȃ��j
$deny_subj = array('������','�L��','ocument','equest','essage','elivery');
// ���e�񋖉{���i���O�ɋL�^���Ȃ��j
$deny_text = array('�X�[�p�[�R�s�[','http://php.s3.to','http://se-buo');
// �ۑ����Ȃ��t�@�C��(���K�\��)
$viri = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";

// �{������폜���镶����
$word[] = "����o�^�͖���  �[�������o�i�A�C�e���Ȃ� MSN �I�[�N�V����";
$word[] = "http://auction.msn.co.jp/";
$word[] = "Do You Yahoo!?";
$word[] = "Yahoo! BB is Broadband by Yahoo!";
$word[] = "http://bb.yahoo.co.jp/";
$word[] = "�F�B��24���ԃz�b�g���C���uMSN ���b�Z���W���[�v�A�������_�E�����[�h�I";
$word[] = "http://messenger.msn.co.jp";

// �Y�t���[���̂݋L�^����HYes=1 No=0�i�{���݂̂̓��O�ɍڂ��Ȃ��j
$imgonly = 0;
// �������Ȃ��Ƃ��̑薼
$nosubject = "��������";
// ���̕b���ȓ��̓��ꑗ�M�҂���̘A�����e�֎~�i0�ŘA���j
$wtime = 30;
// ���t�@�C�����ŕۑ�����HYes=1 No=0�i0�̏ꍇ ����.�g���q�j
$original = 1;

/*-- �T���l�C��--*/
//����ȏ�̑傫���摜��jpg,png�̃T���l�C���쐬
$W = 140;
$H = 140;
//�T���l�C���ۑ��f�B���N�g���i�p�[�~�b�V����777���ɕύX�j
$thumb_dir = "./data/s/";

/* �e���v���[�g���
{$self}�@�@�@�@�@�\���X�N���v�g���imailbbs.php�j
[loop main]�@�@�@�L���J��Ԃ��̊J�n
 ���ȉ�[loop main]��
 [if main/sam]�`[/if]      �T���l�C���摜������ꍇ
 [if main/img]�`[if]�@     �摜������ꍇ
 [if main/noimg]�`[/if]    �摜�ȊO�̓Y�t������ꍇ
 [if main/filename]�`[/if] �Y�t������ꍇ
 {$main/id}�@�@�@�@�L���ԍ�
 {$main/subject}�@ ����
 {$main/date}�@�@�@���t
 {$main/url}�@�@�@ �摜URL�i�f�B���N�g���{�t�@�C�����j
 {$main/size}      �摜�T�C�Y
 {$main/body}      �{��
 {$main/filename}  �Y�t�t�@�C����
 {$main/tail}      �Y�t�g���q
 {$main/sam_url}�@ �T���l�C���摜URL�i�f�B���N�g���{�t�@�C�����j
 {$main/sam_size}  �T���l�C���摜�T�C�Y�iKB)
 �@���T���l�C���������ꍇ��{$main/url}{$main/size}���g�p����܂�
 {$main/amc}�@�@�@ amc�pobject�^�O
[/loop]           �L���̏I���

[if prev]�`[/if]  �O�y�[�W������ꍇ
{$prev}�@�O�y�[�W�ւ�URL�imailbbs.php?page=x�j
[if next]�`[/if]�@���y�[�W������ꍇ
{$prev}�@���y�[�W�ւ�URL�imailbbs.php?page=x�j
*/
?>