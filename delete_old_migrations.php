<?php declare(strict_types=1);

// Delete all unwanted old migrations
$files_to_delete = [
    'database/migrations/2018_11_06_222923_create_transactions_table.php',
    'database/migrations/2018_11_07_192923_create_transfers_table.php',
    'database/migrations/2018_11_15_124230_create_wallets_table.php',
    'database/migrations/2021_11_02_202021_update_wallets_uuid_table.php',
    'database/migrations/2023_12_30_113122_extra_columns_removed.php',
    'database/migrations/2023_12_30_204610_soft_delete.php',
    'database/migrations/2024_01_24_185401_add_extra_column_in_transfer.php',
];

foreach ($files_to_delete as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "✅ Deleted: " . basename($file) . "\n";
    }
}

echo "\n✅ All old migrations cleaned\n";
