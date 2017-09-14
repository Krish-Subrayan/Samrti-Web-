<?php
$file = 'index.html';

$fh = fopen($file, "r");
$fcontent = fread($fh, filesize($file));


$fh2 = fopen('new.txt', 'w+');
fwrite($fh2, $fcontent);
fclose($fh);
fclose($fh2);
?>

