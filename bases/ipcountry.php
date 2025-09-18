<?php
require_once __DIR__ . '/../logging.php';
require_once __DIR__ . '/geoip2.phar';
use GeoIp2\Database\Reader as GeoIp2Reader;
use GeoIp2\Exception\AddressNotFoundException as ANFException;

function getip(array|null $headers = null): string
{
    if (is_null($headers)) $headers = $_SERVER;
    
    $remoteAddr = $headers['REMOTE_ADDR'];
    
    //Will return Cloudflare's connecting ip only if requests come from Cloudflare
    if (isset($headers['HTTP_CF_CONNECTING_IP'])){
        $cfip = $headers['HTTP_CF_CONNECTING_IP'];
        if (str_contains(strtolower(getisp($remoteAddr)), "cloudflare"))
            return $cfip;
        else
            add_log("bases", "Fake CloudflareIP: $cfip, RemoteAddr: $remoteAddr");
    }

    if ($remoteAddr === '::1' || !is_public_ip($remoteAddr))
        return '109.124.224.100'; //for debugging
    
    return $remoteAddr;
}

function is_public_ip(string $ip): bool
{
    return filter_var(
    $ip,
    FILTER_VALIDATE_IP,
    FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === $ip;
}

function getcountry(string $ip): string
{
    if ($ip === 'Unknown') return 'Unknown';
    $reader = new GeoIp2Reader(__DIR__ . '/GeoLite2-Country.mmdb');
    if ($ip === '::1' || $ip === '127.0.0.1')
        $ip = '31.177.76.70'; //for debugging
    try {
        $record = $reader->country($ip);
        return $record->country->isoCode;
    } catch (ANFException $exception) {
        add_log("bases", "GetCountry AddressNotFoundException: $ip");
        return 'Unknown';
    }
}

function getisp(string $ip)
{
    if ($ip === 'Unknown') return 'Unknown';
    $reader = new GeoIp2Reader(__DIR__ . '/GeoLite2-ASN.mmdb');
    if ($ip === '::1' || $ip === '127.0.0.1')
        $ip = '31.177.76.70'; //for debugging
    try {
        $record = $reader->asn($ip);
        return $record->autonomousSystemOrganization;
    } catch (ANFException $exception) {
        add_log("bases", "GetISP AddressNotFoundException: $ip");
        return 'Unknown';
    }
}