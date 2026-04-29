<?php
declare(strict_types=1);

//Prevent direct access 
if (!defined('APP_ROOT')) {
    die('Direct access not permitted.');
}

define('APP_NAME',    'MyCloud');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');
define('BASE_URL',    getenv('APP_URL') ?: 'http://localhost/mycloud/public');

define('STORAGE_PATH',  APP_ROOT . '/storage/uploads');
define('DB_PATH',       APP_ROOT . '/db/cloud.db');
define('LOG_PATH',      APP_ROOT . '/storage/logs');

define('SESSION_LIFETIME',      3600);      
define('CSRF_TOKEN_LENGTH',     32);
define('MAX_LOGIN_ATTEMPTS',    5);        
define('LOGIN_LOCKOUT_SECONDS', 900);     

define('MAX_UPLOAD_BYTES', 500 * 1024 * 1024); 


define('ALLOWED_TYPES', [
    // Images
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/gif'       => 'gif',
    'image/webp'      => 'webp',
    'image/svg+xml'   => 'svg',
    // Video
    'video/mp4'       => 'mp4',
    'video/webm'      => 'webm',
    'video/ogg'       => 'ogv',
    // Audio
    'audio/mpeg'      => 'mp3',
    'audio/ogg'       => 'ogg',
    'audio/wav'       => 'wav',
    // Documents
    'application/pdf' => 'pdf',
    // Archives
    'application/zip'                                                  => 'zip',
    'application/x-tar'                                                => 'tar',
    'application/gzip'                                                 => 'gz',
    // Data
    'text/plain'      => 'txt',
    'text/csv'        => 'csv',
    'application/json'=> 'json',
]);


define('OWNER_USERNAME', 'AREYAN');
define('OWNER_PASSWORD_HASH',
    '$argon2id$v=19$m=65536,t=4,p=1$bkdsV241ejVPemJnSUxmRg$xGPZOlDlsIU8XB1Wbu9Hijd3TMriZHUZ9iZmx5+mMkI'
);
