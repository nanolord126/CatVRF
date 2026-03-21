<?php
$pdo = new PDO('sqlite:database/tenant.sqlite');

echo "=== TABLE: jobs ===\n";
$result = $pdo->query("PRAGMA table_info(jobs)");
foreach ($result as $row) {
    echo $row['name'] . " | " . $row['type'] . " | " . ($row['notnull'] ? "NOT NULL" : "NULL") . "\n";
}

echo "\n=== TABLE: job_batches ===\n";
$result = $pdo->query("PRAGMA table_info(job_batches)");
foreach ($result as $row) {
    echo $row['name'] . " | " . $row['type'] . " | " . ($row['notnull'] ? "NOT NULL" : "NULL") . "\n";
}

echo "\n=== TABLE: failed_jobs ===\n";
$result = $pdo->query("PRAGMA table_info(failed_jobs)");
foreach ($result as $row) {
    echo $row['name'] . " | " . $row['type'] . " | " . ($row['notnull'] ? "NOT NULL" : "NULL") . "\n";
}

echo "\n\n=== INDEXES ===\n";
echo "jobs:\n";
$indexes = $pdo->query("PRAGMA index_list(jobs)");
foreach ($indexes as $idx) {
    echo "  " . $idx['name'] . " (unique: " . $idx['unique'] . ")\n";
}

echo "\njob_batches:\n";
$indexes = $pdo->query("PRAGMA index_list(job_batches)");
foreach ($indexes as $idx) {
    echo "  " . $idx['name'] . " (unique: " . $idx['unique'] . ")\n";
}

echo "\nfailed_jobs:\n";
$indexes = $pdo->query("PRAGMA index_list(failed_jobs)");
foreach ($indexes as $idx) {
    echo "  " . $idx['name'] . " (unique: " . $idx['unique'] . ")\n";
}
