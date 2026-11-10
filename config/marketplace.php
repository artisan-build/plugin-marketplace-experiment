<?php

return [
    'source' => base_path('marketplace'),
    'repository' => storage_path('app/marketplace.git'),
    'commit_date' => env('MARKETPLACE_COMMIT_DATE', '2026-01-01T00:00:00Z'),
    'admin_email' => env('POC_ADMIN_EMAIL'),
    'admin_password' => env('POC_ADMIN_PASSWORD'),
    'test_passwords' => [env('POC_CLI_PASSWORD'), env('POC_MAC_PASSWORD'), env('POC_WINDOWS_PASSWORD')],
];
