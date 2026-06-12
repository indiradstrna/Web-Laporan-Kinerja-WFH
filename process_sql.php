<?php
$inputFile = 'db_kdr.sql';
$outputFile = 'db_kdr_insert_only.sql';

$in = fopen($inputFile, 'r');
$out = fopen($outputFile, 'w');

if ($in && $out) {
    $ignore = false;
    while (($line = fgets($in)) !== false) {
        $trimLine = trim($line);
        
        // Start ignoring CREATE TABLE and ALTER TABLE blocks
        if (strpos($trimLine, 'CREATE TABLE') === 0 || strpos($trimLine, 'ALTER TABLE') === 0) {
            $ignore = true;
        }
        
        // If we are ignoring, check if this line closes the block
        if ($ignore) {
            if (substr($trimLine, -1) === ';') {
                $ignore = false;
            }
            continue; // Skip writing this line
        }
        
        // Also skip individual DROP, ADD CONSTRAINT, etc. if any are one-liners
        if (strpos($trimLine, '--') === 0 && strpos($trimLine, '-- Indeks') !== false) {
            continue;
        }

        // Make inserts IGNORE to prevent duplicate errors
        if (strpos($trimLine, 'INSERT INTO') === 0) {
            $line = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $line);
        }

        fwrite($out, $line);
    }
    fclose($in);
    fclose($out);
    echo "SQL processing completed.";
} else {
    echo "Failed to open files.";
}
?>
