<?php

return [
    'adminEmail' => 'rubia.services@gmail.com',

    // Only these emails may sign in with the admin role, regardless of what user_role is set to in the
    // database. Having user_role = 'admin' is necessary but no longer sufficient for dashboard access.
    'superAdminEmails' => [
        'rubia.services@gmail.com',
        'anjaniservices369@gmail.com',
    ],

    'dispatch' => [
        // Seconds a ride may wait for a driver to accept before it is cancelled (checked while the rider polls).
        // 0 disables the automatic cancellation.
        'offerTimeout' => 120,
    ],

    'jwt' => [
        // Lifetime of an issued auth_code (JWT) in seconds; 90 days.
        'ttl' => 7776000,
        // true accepts pre-JWT random auth codes already stored in auth_session (temporary migration aid).
        'allowLegacy' => false,
    ],
];
