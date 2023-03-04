<?php


$domainName = rawurldecode(parse_url($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], PHP_URL_PATH));

$date = new DateTimeImmutable();
$expire_at = $date->modify('+1440 minutes')->getTimestamp();      // Add 60 seconds
$expire_at = strtotime('+1 day', strtotime('now'));

$service_id = "0oa5l8n9qxwPpMbTN5d7"; // Retrieved from filtered POST data
$JwtData = [
    'iat'  => $date->getTimestamp(), // Issued at: time when the token was generated
    'iss'  => $domainName,                       // Issuer
    'nbf'  => $date->getTimestamp(),         // Not before
    'exp'  => $expire_at,                   // Expire
    'service_id' => $service_id,                     // User name
];

$private_key = file_get_contents(__DIR__."/../../../jwtRS256.key");
$public_key = file_get_contents(__DIR__."/../../../jwtRS256.key.pub");

define("JWT_DATA", $JwtData);
define("PRIVATE_KEY", $private_key);
define("PUBLIC_KEY", $public_key);
define("SERVICE_ID", $service_id);
define("DOMAIN_NAME", $domainName);
define("NOW_DATE", $date);