<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = require 'bootstrap/app.php';

\Illuminate\Support\Facades\DB::table('migrations')->delete();
echo "✓ Migrations table cleared\n";
