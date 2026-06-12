<?php
$file = 'db_kdr_insert_only.sql';
$content = file_get_contents($file);
$content = str_replace('`nik`', '`nip_nik`', $content);
file_put_contents($file, $content);
echo "Replaced nik with nip_nik in SQL file.";
?>
