<?php
namespace leoding86\BaiduService;

interface ICacheObject
{
    public function cacheAccessToken($access_token);
    public function getAccessToken();
}