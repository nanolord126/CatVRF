<?php

$db = new PDO('sqlite:database/database.sqlite');
$result = $db->query('SELECT COUNT(*) as count FROM sqlite_master WHERE type="table"');
$row = $result->fetch(PDO::FETCH_ASSOC);
echo "Tables created: " . $row['count'] . "\n";

$result = $db->query('SELECT name FROM sqlite_master WHERE type="table" LIMIT 5');
foreach($result as $row) {
    echo "- " . $row['name'] . "\n";
}
