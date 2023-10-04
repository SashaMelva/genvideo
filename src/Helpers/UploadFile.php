<?php

namespace App\Helpers;

use Slim\Psr7\UploadedFile;

class UploadFile
{
    public static function action(string $directory, UploadedFile $uploadedFile, string $basename): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        #проверяем существует файл в катологе не зависимо от расширения
        $files = scandir($directory);
        foreach ($files as $file) {
            if (str_contains($file, $basename)) {
                unlink($directory . DIRECTORY_SEPARATOR . $file);
                break;
            }
        }

        $uploadedFile->moveTo($path);

        return $filename;
    }
}