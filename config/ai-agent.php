<?php

return [
    'google' => [
        'api_key' => env('GOOGLE_AI_API_KEY'),
        'model' => env('GOOGLE_AI_MODEL', 'gemini-3.5-flash'),
        'embedding_model' => env('GOOGLE_EMBEDDING_MODEL', 'gemini-embedding-001'),

        // Priority list for failover system based on 2026 quotas
        'models_priority' => [
            'gemini-3.5-flash',       // Priority 1: Strongest (5 RPM)
            'gemma-4-26b-a4b-it',     // Priority 2: High intelligence, Unlimited TPM
            'gemini-3.1-flash-lite',  // Priority 3: High capacity (15 RPM)
            'gemini-2.5-flash-lite',  // Priority 4: Safe fallback (10 RPM)
        ],
    ],

    'rag' => [
        'top_k' => 5,
        'similarity_threshold' => 0.7,
    ],

    'cache_ttl' => 3600,
];
