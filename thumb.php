<?php
//サムネイル用関数
//PHPにGDオプションが必要です
function thumb_create($src, $W, $H, $thumb_dir="./"){
  // 画像の幅と高さとタイプを取得
  $size = GetImageSize($src);
  switch ($size[2]) {
    case 1 : $im_in = @ImageCreateFromGif($src); break;
    case 2 : $im_in = @ImageCreateFromJPEG($src); break;
    case 3 : $im_in = @ImageCreateFromPNG($src);  break;
  }
  if (!$im_in) die("GDをサポートしていないか、ソースが見つかりません<br>phpinfo()でGDオプションを確認してください");
  // リサイズ
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
  // 出力画像（サムネイル）のイメージを作成し、元画像をコピーします。(GD2.0用)
  $im_out = @ImageCreateTrueColor($out_w, $out_h);
  if (!$im_out) $im_out = ImageCreate($out_w, $out_h);
  $resize = @ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
  if (!$resize) $resize = ImageCopyResized($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
  // サムネイル画像をブラウザに出力、保存
  $filename = substr($src, strrpos($src,"/")+1);
  $filename = substr($filename, 0, strrpos($filename,"."));
  // 画像作成、保存
  ImageJPEG($im_out, $thumb_dir.$filename.".jpg");
  //ImagePNG($im_out, $thumb_dir.$filename.".png");
  // 作成したイメージを破棄
  ImageDestroy($im_in);
  ImageDestroy($im_out);
}
?>