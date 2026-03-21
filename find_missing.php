<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\ = app('router')->getRoutes();
\ = [];
foreach (\ as \) {
    if (isset(\->action['uses']) && is_string(\->action['uses'])) {
        \ = \->action['uses'];
        if (str_contains(\, '@')) {
            \ = explode('@', \)[0];
            if (!class_exists(\)) {
                \[] = \;
            }
        } else {
            if (!class_exists(\)) {
                 \[] = \;
            }
        }
    }
}
print_r(array_unique(\));

