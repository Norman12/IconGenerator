<?php

require_once "vendor/Slim/Slim.php";
require_once "lib/API.php";

use \Slim\Slim;
use \API;

Slim::registerAutoloader();

$app = new Slim();
$app->contentType("Content-Type: application/json; charset=utf-8");

//POST image
$app->post("/iphone", "generate_iphone");
$app->post("/iphoneold", "generate_iphone_old");
$app->post("/ipad", "generate_ipad");
$app->post("/ipadold", "generate_ipad_old");
$app->post("/universal", "generate_universal");
$app->post("/universalold", "generate_universal_old");
$app->post("/android", "generate_android");

//GET url
$app->get("/iphone/", "generate_iphoneu");
$app->get("/iphoneold/", "generate_iphone_oldu");
$app->get("/ipad/", "generate_ipadu");
$app->get("/ipadold/", "generate_ipad_oldu");
$app->get("/universal/", "generate_universalu");
$app->get("/universalold/", "generate_universal_oldu");
$app->get("/android/", "generate_androidu");

$app->get("/reset", "resetMem");
$app->get("/download/:id", "download");

$app->error(function (\Exception $e) use ($app) {
    $app->status(200);
    echo json_encode(array("status" => "failure", "error" => $e->getMessage()));
});

$app->config("debug", true);
$app->run();

function generate_iphone() {
    generate("iphone");
}

function generate_iphone_old() {
    generate("iphoneold");
}

function generate_ipad() {
    generate("ipad");
}

function generate_ipad_old() {
    generate("ipadold");
}

function generate_universal() {
    generate("universal");
}

function generate_universal_old() {
    generate("universalold");
}

function generate_android() {
    generate("android");
}

//fdfdfddf
function generate_iphoneu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("iphone", $url);
}

function generate_iphone_oldu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("iphoneold", $url);
}

function generate_ipadu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("ipad", $url);
}

function generate_ipad_oldu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("ipadold", $url);
}

function generate_universalu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("universal", $url);
}

function generate_universal_oldu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("universalold", $url);
}

function generate_androidu() {
    $request = Slim::getInstance()->request();
    $url = stripcslashes($request->params("url"));
    generateFromURL("android", $url);
}

function generate($platform) {
    $platform = API::replaceSpecialChars($platform);
    if (in_array($platform, API::$platforms)) {
        $path = API::getImage();
        if ($path != null) {
            if (!getimagesize($path)) {
                unlink($path);
                throw new Exception("Not an image.");
            }
            $memcache = API::getMemcache();
            $uid = md5($platform . sha1_file($path) . mt_rand(0, 1000000));

            if ($memcache->get($uid)) {
                echo $memcache->get($uid);
            } else {
                $result = json_encode(API::resize($platform, $path, $uid, true), JSON_UNESCAPED_SLASHES);
                $memcache->set($uid, $result, false, 1); //86400
                //$memcache->set("access" . $uid, $result, false, 86400); //86400
                echo $result;
            }
        } else {
            throw new Exception("Problem uploading file. make sure it is an image and its size does not exceed 4 megabytes.");
        }
    } else {
        throw new Exception("Invalid platform name supplied.");
    }
}

function generateFromURL($platform, $url) {
    $platform = API::replaceSpecialChars($platform);
    if (in_array($platform, API::$platforms)) {
        $uid = md5($platform . sha1($url) . mt_rand(0, 1000000));
        echo json_encode(API::resize($platform, $url, $uid, false), JSON_UNESCAPED_SLASHES);
    } else {
        throw new Exception("Invalid platform name supplied.");
    }
}

function download($id) {
    echo file_get_contents(API::$imageDir . $id . ".zip");
}

function resetMem() {
    $memcache = API::getMemcache();
    $memcache->flush();
}
