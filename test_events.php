<?php require "vendor/autoload.php"; require "bootstrap/app.php"; $app = app(); $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap(); print_r(DB::select("PRAGMA table_info(events)"));
