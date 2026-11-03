<?php
declare(strict_types=1);

// Minimal platform health check. Deliberately exposes no configuration or secrets.
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo 'ok';
