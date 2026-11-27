<?php
declare(strict_types=1);
function api_request(string $route, string $method='GET', ?array $payload=null): array {
    $cfg=require dirname(__DIR__).'/config.php';
    $url=rtrim($cfg['api_base'],'/').'/'.ltrim($route,'/');
    if($method==='GET' && $payload){$url.='?'.http_build_query($payload);}
    $ch=curl_init($url);
    $headers=['Authorization: Bearer '.$cfg['api_token'],'Accept: application/json'];
    if($payload && $method!=='GET'){$headers[]='Content-Type: application/json';curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_UNESCAPED_UNICODE));}
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>10,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);
    $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
    if($body===false)throw new RuntimeException('API недоступен: '.$err);
    $json=json_decode($body,true);
    if(!is_array($json))throw new RuntimeException('API вернул некорректный JSON');
    if($status<200 || $status>=300 || !($json['ok']??false))throw new RuntimeException($json['error']['message']??'Ошибка API');
    return $json['data'];
}
