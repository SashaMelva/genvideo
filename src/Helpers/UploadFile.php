<?php

namespace App\Helpers;

use Slim\Psr7\UploadedFile;

class UploadFile
{
    public static function action(string $directory, UploadedFile $uploadedFile, string $basename): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $path = $directory  . $filename;

        #проверяем существует файл в катологе не зависимо от расширения
        $files = scandir($directory);
        foreach ($files as $file) {
            if (str_contains($file, $basename)) {
                unlink($directory . $file);
                break;
            }
        }

        $uploadedFile->moveTo($path);

        return $filename;
    }

    public static function special(string $directory, array $uploadedFile, string $basename): string
    {
        var_dump($uploadedFile);
//        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $filename = $uploadedFile["name"];// sprintf('%s.%0.8s', $basename, $extension);
        $path = $directory  . $filename;

//        #проверяем существует файл в катологе не зависимо от расширения
//        $files = scandir($directory);
//        foreach ($files as $file) {
//            if (str_contains($file, $basename)) {
//                unlink($directory . $file);
//                break;
//            }
//        }

        move_uploaded_file($uploadedFile["tmp_name"], $path);

        var_dump($path);
        return $filename;
    }
}