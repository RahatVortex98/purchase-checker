<?php
echo '<pre>';
var_dump([
  'ini_file'       => php_ini_loaded_file(),
  'upload_tmp_dir' => ini_get('upload_tmp_dir'),
  'sys_temp'       => sys_get_temp_dir(),
  'tmp_writable'   => is_writable('C:\\tmp'),
  'tempnam_test'   => @tempnam('C:\\tmp', 'x'),
  'sapi'           => PHP_SAPI,
  'pid'            => getmypid(),
]);
if ($_FILES) var_dump($_FILES);
echo '</pre>';
?>
<form method="post" enctype="multipart/form-data">
  <input type="file" name="f"><button>Upload</button>
</form>