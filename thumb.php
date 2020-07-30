<?php
//�T���l�C���p�֐�
//PHP��GD�I�v�V�������K�v�ł�
function thumb_create($src, $W, $H, $thumb_dir="./"){
  // �摜�̕��ƍ����ƃ^�C�v���擾
  $size = GetImageSize($src);
  switch ($size[2]) {
    case 1 : $im_in = @ImageCreateFromGif($src); break;
    case 2 : $im_in = @ImageCreateFromJPEG($src); break;
    case 3 : $im_in = @ImageCreateFromPNG($src);  break;
  }
  if (!$im_in) die("GD���T�|�[�g���Ă��Ȃ����A�\�[�X��������܂���<br>phpinfo()��GD�I�v�V�������m�F���Ă�������");
  // ���T�C�Y
  if ($size[0] > $W || $size[1] > $H) {
    $key_w = $W / $size[0];
    $key_h = $H / $size[1];
    ($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
    $out_w = $size[0] * $keys;
    $out_h = $size[1] * $keys;
  } else {
    $out_w = $size[0];
    $out_h = $size[1];
  }
  // �o�͉摜�i�T���l�C���j�̃C���[�W���쐬���A���摜���R�s�[���܂��B(GD2.0�p)
  $im_out = @ImageCreateTrueColor($out_w, $out_h);
  if (!$im_out) $im_out = ImageCreate($out_w, $out_h);
  $resize = @ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
  if (!$resize) $resize = ImageCopyResized($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
  // �T���l�C���摜���u���E�U�ɏo�́A�ۑ�
  $filename = substr($src, strrpos($src,"/")+1);
  $filename = substr($filename, 0, strrpos($filename,"."));
  // �摜�쐬�A�ۑ�
  ImageJPEG($im_out, $thumb_dir.$filename.".jpg");
  //ImagePNG($im_out, $thumb_dir.$filename.".png");
  // �쐬�����C���[�W��j��
  ImageDestroy($im_in);
  ImageDestroy($im_out);
}
?>