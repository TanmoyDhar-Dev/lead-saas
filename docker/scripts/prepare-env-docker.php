<?php

$path = dirname(__DIR__, 2).'/.env.docker';
$example = dirname(__DIR__, 2).'/.env.docker.example';

if (! is_file($example)) {
    fwrite(STDERR, "Missing .env.docker.example\n");
    exit(1);
}

$content = file_get_contents($example);
$content = str_replace('change_me_db_password', bin2hex(random_bytes(12)), $content);
$content = str_replace('change_me_root_password', bin2hex(random_bytes(12)), $content);
$content = str_replace('change_me_redis_password', bin2hex(random_bytes(12)), $content);
$content = str_replace('change_me_admin_password', bin2hex(random_bytes(12)), $content);
$content = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $content);

file_put_contents($path, $content);
echo "Wrote {$path}\n";
