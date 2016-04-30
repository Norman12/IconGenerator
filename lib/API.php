<?php

require_once dirname(dirname(__FILE__)) . "/vendor/Upload/Autoloader.php";

/**
 * Description of API
 *
 * @author Marcin
 */
class API {

    public static $platforms = array("iphone", "iphoneold", "ipad", "ipadold", "universal", "universalold", "android", "windowsphone");
    public static $imageDir = "/usr/share/nginx/www/api3/images/";
    public static $uploadDir = "/usr/share/nginx/www/api3/uploads/";
    private static $sizes = array(
        "iphone" => array(
            "iTunesArtwork@2x" => 1024,
            "iTunesArtwork" => 512,
            "Icon-60@3x.png" => 180,
            "Icon-76@2x.png" => 152,
            "Icon-60@2x.png" => 120,
            "Icon-Small-40@3x.png" => 120,
            "Icon-Small@3x.png" => 87,
            "Icon-Small-40@2x.png" => 80,
            "Icon-76.png" => 76,
            "Icon-Small@2x.png" => 58,
            "Icon-Small-40.png" => 40,
            "Icon-Small.png" => 29,
        ),
        "iphoneold" => array(
            "Icon-72@2x.png" => 144,
            "Icon@2x.png" => 114,
            "Icon-Small-50@2x.png" => 100,
            "Icon-72.png" => 72,
            "Icon-Small@2x.png" => 58,
            "Icon.png" => 57,
            "Icon-Small-50.png" => 50,
            "Icon-Small.png" => 29,
        ),
        "ipad" => array(
            "iTunesArtwork@2x" => 1024,
            "iTunesArtwork" => 512,
            "Icon-76@2x.png" => 152,
            "Icon-Small-40@2x.png" => 80,
            "Icon-76.png" => 76,
            "Icon-Small@2x.png" => 58,
            "Icon-Small-40.png" => 40,
            "Icon-Small.png" => 29
        ),
        "ipadold" => array(
            "Icon-72@2x.png" => 144,
            "Icon-Small-50@2x.png" => 100,
            "Icon-72.png" => 72,
            "Icon-Small-50.png" => 50,
        ),
        "universal" => array(
            "iTunesArtwork@2x" => 1024,
            "iTunesArtwork" => 512,
            "Icon-60@3x.png" => 180,
            "Icon-76@2x.png" => 152,
            "Icon-Small-40@3x.png" => 120,
            "Icon-60@2x.png" => 120,
            "Icon-Small@3x.png" => 87,
            "Icon-Small-40@2x.png" => 80,
            "Icon-76.png" => 76,
            "Icon-Small@2x.png" => 58,
            "Icon-Small-40.png" => 40,
            "Icon-Small.png" => 29
        ),
        "universalold" => array(
            "Icon-72@2x.png" => 144,
            "Icon@2x.png" => 114,
            "Icon-Small-50@2x.png" => 100,
            "Icon-72.png" => 72,
            "Icon-Small@2x.png" => 58,
            "Icon.png" => 57,
            "Icon-Small-50.png" => 50,
            "Icon-Small.png" => 29
        ),
        "android" => array(
            "web.png" => 512,
            "xxxhdpi.png" => 192,
            "xxhdpi.png" => 144,
            "xhdpi.png" => 96,
            "hdpi.png" => 72,
            "mdpi.png" => 48,
            "ldpi.png" => 36,
        ),
        "windowsphone" => array()
    );
    private static $iphone_round_factor = 0.175438596;
    private static $special_chars = array("?", "!", "_");

    public static function resize($platform, $imageLocation, $uid, $filemode) {

        $filename = API::$imageDir . $uid . ".zip";

        if ($filemode) {
            $handle = fopen($imageLocation, "rb");
        } else {
            $temphandle = file_get_contents($imageLocation);
            $imageLocation = self::$uploadDir . uniqid();
            file_put_contents($imageLocation, $temphandle);
            $handle = fopen($imageLocation, "rb");
        }

        $image = new Imagick();
        $image->readImageFile($handle);
        $image->setImageFormat("png");
        $image->setBackgroundColor(new ImagickPixel('transparent'));
        $image->setimagematte(true);

        unlink($imageLocation);

        $d = $image->getImageGeometry();
        if ($d["width"] != $d["height"]) {
            throw new Exception("Image is not a square, cannot make icon!");
        }

        $zip = new ZipArchive;
        $zip->open($filename, ZipArchive::CREATE);

        if ($platform !== "android") {
            //$image->roundCorners(round($d["width"] * self::$iphone_round_factor), round($d["height"] * self::$iphone_round_factor));
            $rect = new Imagick();
            $rect->newImage($d["width"], $d["height"], 'none');
            $rect->setimageformat('png');
            $rect->setimagematte(true);
            $draw = new ImagickDraw();
            $draw->setfillcolor('#ffffff');
            $draw->roundrectangle(0, 0, $d["width"], $d["height"], round($d["width"] * self::$iphone_round_factor), round($d["height"] * self::$iphone_round_factor));
            $rect->drawimage($draw);
            $image->compositeimage($rect, Imagick::COMPOSITE_DSTIN, 0, 0);
        }

        foreach (self::$sizes[$platform] as $name => $size) {
            $tmp = $image;
            if ($size >= $d["width"]) {
                $filter = imagick::FILTER_MITCHELL;
            } else {
                $filter = imagick::FILTER_LANCZOS;
            }
            $tmp->resizeImage($size, $size, $filter, 0.9, TRUE);

            $zip->addFromString($name, $tmp);
        }

        $image->destroy();
        $zip->close();
        return array("status" => "success", "download" => "http://www.marcinpraski.com/icongenerator/download.php?type=" . $platform . "&id=" . $uid);
    }

    public static function getImage() {
        $storage = new \Upload\Storage\FileSystem(self::$uploadDir);
        $file = new \Upload\File("image", $storage);
        $uid = uniqid();
        $file->setName($uid);

        $file->addValidations(array(
            new \Upload\Validation\Size("4M")
        ));

        try {
            $file->upload();
            return self::$uploadDir . $file->getNameWithExtension();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getMemcache() {
        static $memcache = null;
        if (null === $memcache) {
            $memcache = new Memcache;
            $memcache->connect("localhost", 11211);
        }
        return $memcache;
    }

    public static function replaceSpecialChars($whatev) {
        return str_replace(self::$special_chars, "", $whatev);
    }

    public static function checkImage($url) {
        return getimagesize($url) || exif_imagetype($url);
    }

}
