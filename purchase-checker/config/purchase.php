<?php

return [
    'password' => env('APP_PASSWORD'),
    'managing_director' => [
        'email' => env('MANAGING_DIRECTOR_EMAIL', 'nurulhasan@example.com'),
        'password_hash' => env('MANAGING_DIRECTOR_PASSWORD_HASH'),
    ],
    'similar_threshold' => 0.65,   // lower = more "similar" matches

    // phrase fixes (applied on lowercase text)
    'phrases' => [
        'h2so4' => 'sulfuric acid',
        'sulphuric' => 'sulfuric',
        'h2o2' => 'hydrogen peroxide',
        'w. rod' => 'welding rod',
    ],

    // word fixes
    'words' => [
        'clump' => 'clamp', 'sefty' => 'safety', 'safty' => 'safety', 'granding' => 'grinding',
        'disc' => 'disk', 'exjust' => 'exhaust', 'pnumatric' => 'pneumatic', 'pnumatic' => 'pneumatic',
        'vulve' => 'valve', 'sheild' => 'shield', 'musk' => 'mask', 'niple' => 'nipple',
        'corugated' => 'corrugated', 'sulpher' => 'sulfur', 'sulphur' => 'sulfur', 'psc' => 'pcs',
        'repaire' => 'repair', 'maintaince' => 'maintenance', 'gloves' => 'glove', 'pipes' => 'pipe',
        'dichloromethene' => 'dichloromethane', 'stiraer' => 'stirrer', 'volomet' => 'volumetric',
    ],

    'stopwords' => ['the', 'of', 'and', 'for', 'a', 'an', 'with', 'new'],
];
