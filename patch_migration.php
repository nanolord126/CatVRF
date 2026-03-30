<?php
$f = 'database/migrations/2026_03_25_000001_create_wallet_payment_promo_tables.php';
$c = file_get_contents($f);
$c = preg_replace('/if\s*\(\w+::hasTable\(\'wallets\'\)\)\s*return;/', '// $0 removed, it was skipping the whole file', $c);
file_put_contents($f, $c);
echo "Patched\n";