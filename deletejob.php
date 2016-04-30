<?php
$memcache = new Memcache;
$memcache->connect("localhost", 11211);
$files = scandir('/images');
foreach($files as &$file){
    if (!$memcache->get(basename($file))) {
        unlink('/images'.$file);
    }
}