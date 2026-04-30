<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | DID Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Decentralized Identity (DID) and Verifiable Credentials (VC)
    |
    */

    'enabled' => env('DID_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Issuer DID
    |--------------------------------------------------------------------------
    |
    | The DID of the issuer (CatVRF platform)
    |
    */
    'issuer_did' => env('DID_ISSUER_DID', 'did:web:catvrf.ru:issuer'),

    /*
    |--------------------------------------------------------------------------
    | DID Methods
    |--------------------------------------------------------------------------
    |
    | Supported DID methods
    |
    */
    'methods' => [
        'did:web' => env('DID_METHOD_WEB', true),
        'did:key' => env('DID_METHOD_KEY', true),
        'did:ethr' => env('DID_METHOD_ETHR', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default DID Method
    |--------------------------------------------------------------------------
    |
    | The default DID method to use when generating DIDs
    |
    */
    'default_method' => env('DID_DEFAULT_METHOD', 'did:web'),

    /*
    |--------------------------------------------------------------------------
    | DID Expiration
    |--------------------------------------------------------------------------
    |
    | Default expiration time for DIDs in years
    |
    */
    'did_expiration_years' => env('DID_EXPIRATION_YEARS', 5),

    /*
    |--------------------------------------------------------------------------
    | VC Expiration
    |--------------------------------------------------------------------------
    |
    | Default expiration time for different VC types in days
    |
    */
    'vc_expiration' => [
        'KYBCompletionCredential' => 365, // 1 year
        'IdentityCredential' => 730, // 2 years
        'default' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Госуслуги Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Russian government services integration
    |
    */
    'gosuslugi' => [
        'enabled' => env('GOSUSLUGI_ENABLED', false),
        'api_url' => env('GOSUSLUGI_API_URL'),
        'client_id' => env('GOSUSLUGI_CLIENT_ID'),
        'client_secret' => env('GOSUSLUGI_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Method
    |--------------------------------------------------------------------------
    |
    | Default cryptographic verification method
    |
    */
    'verification_method' => env('DID_VERIFICATION_METHOD', 'Ed25519VerificationKey2020'),

    /*
    |--------------------------------------------------------------------------
    | Key Rotation
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic key rotation
    |
    */
    'key_rotation' => [
        'enabled' => env('DID_KEY_ROTATION_ENABLED', false),
        'interval_days' => env('DID_KEY_ROTATION_INTERVAL_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trust Registry
    |--------------------------------------------------------------------------
    |
    | Configuration for DID trust registry
    |
    */
    'trust_registry' => [
        'enabled' => env('DID_TRUST_REGISTRY_ENABLED', true),
        'trusted_issuers' => explode(',', env('DID_TRUSTED_ISSUERS', 'did:web:catvrf.ru:issuer')),
    ],
];
