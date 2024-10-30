<?php

namespace BitForm\Utils;

class FileUtils
{
    const EXTENSIONS = array('png', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'zip', 'mp3', 'wma', 'wmv', 'mpg', 'flv', 'avi', 'jpg', 'jpeg', 'png', 'gif', 'ods', 'rar', 'ppt', 'pptx', 'tif', 'wav', 'mov', 'psd', 'eps', 'sit', 'sitx', 'cdr', 'ai', 'mp4', 'm4a', 'bmp', 'pps', 'aif', 'pdf', 'svg', 'odt', 'psa', 'stp', 'step', 'igs', 'x_t', 'dwg', 'obj', 'stl', 'bin', 'ols', 'sketch', 'msg', 'eml', 'cr2', 'raw', 'dxf', 'dog', 'sldprt', 'slddrw', 'sldasm', 'step', 'ages', 'its', 'obj');

    public static function getExtension($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, self::EXTENSIONS, true) ? $extension : null;
    }

    public static function getBaseDir($path = '/')
    {
        $uploads = wp_upload_dir();
        $baseDir = untrailingslashit($uploads['basedir']) . '/bitform' . $path;
        if (!file_exists($baseDir) || !wp_is_writable($baseDir)) {
            wp_mkdir_p($baseDir);
            $index = $baseDir . 'index.html';
            file_put_contents($index, '');
        }
        return [
            'basedir' => $baseDir,
            'baseurl' => $uploads['baseurl'] . '/bitform' . $path
        ];
    }

    public static function getTmpDir()
    {
        return get_temp_dir();
    }

    public static function getFormDir($formId)
    {
        return self::getBaseDir("/$formId/");
    }

    public static function getRandomFileName()
    {
        return uniqid() . mt_rand();
    }
}
