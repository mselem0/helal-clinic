<?php

return [
    'service_account_path' => env('GOOGLE_SHEETS_SERVICE_ACCOUNT_PATH'),
    'worksheet_prefix' => 'Helal bookings',
    'timeout' => 3,
    'api_root' => null, // Override only in local integration tests.
];
