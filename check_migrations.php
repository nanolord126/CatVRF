<?php
$pdo = new PDO('sqlite:database/tenant.sqlite');
$result = $pdo->query("SELECT migration FROM migrations WHERE migration LIKE '%jobs%' ORDER BY batch DESC");
echo "=== Migrations for jobs ===\n";
foreach ($result as $row) {
    echo $row['migration'] . "\n";
}
