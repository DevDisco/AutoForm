<?php

//todo: create image resizer

/**
 * The routines for fiule uploads are so different end extensive that 
 * I collect them here as static functions
 */
class FileUploads
{

    /**
     * Checks cleanPost to see if there are any file inputs,
     * and deals with either base64 conversion for database inserts,
     * or file uploads to a directory.
     */
    public static function processFiles(Request $request, array $cleanPost, array $fieldList): array
    {

        foreach ($fieldList as $field) {

            if ($field['type'] === "file") {

                $path = $request->config->UPLOAD_ROOT ?? false;
                $key  = $field['name'];
                $prefill = self::checkUploadState($key, $cleanPost[$key]);
                $prefillValue = $_POST["prefill_" . $key] ?? "";

                if ($prefill === UPL_KEEP) {

                    Logger::toLog("Keep current file", "processFiles");
                } else if ($prefill === UPL_NONE) {

                    Logger::toLog("Empty optional file input", "processFiles");
                } else if ($prefill === UPL_DELETE) {

                    Logger::toLog("Delete orphaned file " . $prefillValue, "processFiles");
                    Core::deleteFile($path . $prefillValue);
                } else if ($path) {

                    $path .= ($field['path'] ?? "");

                    if (is_dir($path)) {

                        $filename = self::createSafeFilename($path, $_FILES[$key]['name']);

                        //update cleanPost
                        $cleanPost[$key] = $filename;

                        // Location
                        $target_file = $path . $filename;


                        // Upload file
                        if (!move_uploaded_file(
                            $_FILES[$key]['tmp_name'],
                            $target_file
                        )) {

                            //error
                            $request->error->setError("Can't move file to folder $path", 500);
                            $request->error->showAndAbort();
                        }

                        Logger::toLog("Uploaded " . $target_file, "processFiles");

                        if ($prefill === UPL_DELETE_UPLOAD) {

                            Logger::toLog("Deleted previous file " . $prefillValue, "processFiles");
                            Core::deleteFile($path . $prefillValue);
                        }

                        //Logger::toLog($cleanPost, "cleanPost");
                        //Logger::toLog($_POST, "_POST");
                        //Logger::toLog($_FILES, "_FILES");
                        //Logger::toLog($fieldList, "fieldList");
                        //process upload

                    } else {

                        //error
                        $request->error->setError("Can't find upload folder $path", 500);
                        $request->error->showAndAbort();
                    }
                } else {

                    //images and other files will be stored inside db
                    $image_base64 = base64_encode(file_get_contents($_FILES[$key]['tmp_name']));
                    $image = "data:" . $_FILES[$key]['type'] . ";base64," . $image_base64;
                    $cleanPost[$key] = $image;
                    Logger::toLog("Insert image as base64", "processFiles");
                }
            }
        }

        return $cleanPost;
    }

    private static function checkUploadState(string $inputName, string $postValue)
    {

        $prefillValue = $_POST["prefill_" . $inputName] ?? "";
        $name_FILES = $_FILES[$inputName]['name'] ?? "";

        if (!Session::getCurrentId() || $prefillValue === "") {

            //not an update
            if ($name_FILES === "") {
                //empty optional file, do nothing
                return UPL_NONE;
            } else {
                //upload file
                return UPL_UPLOAD;
            }
        } else if ($postValue === $prefillValue) {

            //update, nothing changed to the file
            return UPL_KEEP;
        } else if ($postValue === "" && $name_FILES === "") {

            //update, file was deleted, nothing new chosen
            return UPL_DELETE;
        } else if ($postValue === "" && $name_FILES !== "") {

            //update, file was deleted, new file chosen
            return UPL_DELETE_UPLOAD;
        }

        return UPL_NONE;
    }

    //todo: check length sql field
    private static function createSafeFilename(string $path, string $filename, int $rand = 0): string
    {

        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $filename);

        if ($rand > 0) {

            $pathinfo = pathinfo($path . $filename);

            $filename = $pathinfo['filename'] . "_" . $rand . "." . $pathinfo['extension'];
        }

        if (is_file($path . $filename)) {

            return self::createSafeFilename($path, $filename, $rand = rand(1000, 9999));
        }

        return $filename;
    }

    public static function deleteLinkedFiles(Request $request, array $record): void
    {
        //get fields def
        $fieldlist = $request->fields->get();
        $uploadFolder = $request->config->UPLOAD_ROOT;

        foreach ($fieldlist as $field) {

            //there's a file input and the record has a value for the field
            if ($field['type'] === "file" && $record[$field['name']]) {

                $file = $uploadFolder . $field['path'] . $record[$field['name']];

                if (is_file($file)) {

                    unlink($file);
                    Logger::toLog($record[$field['name']], "Successfully removed file");
                }
                else{

                    Logger::toLog($record[$field['name']], "Could not find file to remove");
                }
            }
        }
    }
    
    public static function validateFileInput( Request $request, array $field, string|null $postValue ):bool{

        extract($field);

        //todo: use prefill checker

        if (Session::getCurrentId()) {

            //we're updating, check if text input has been set
            $prefillValue = $_POST["prefill_" . $name];

            if ($prefillValue !== "" && $postValue === $prefillValue) {

                return true;
            }
        }

        if (!isset($_FILES[$name])) {

            $request->error->setError("Upload: No file can be found for " . $name . ".");
            return false;
        } else if ($_FILES[$name]['name'] === "" && !$required) {

            //empty upload on updating an optional file input
            return true;
        } else if ($_FILES[$name]['size'] > $maxfilesize) {

            $request->error->setError("Upload: The uploaded file for " . $name . " is too large.");
            return false;
        } else if ($component === "input_image") {

            $imageArray = getimagesize($_FILES[$name]['tmp_name']);
            //Logger::toLog($imageArray);

            $width ?? false;
            $heigth ?? false;

            if (
                !Core::checkAgainstDimension($width, $imageArray[0]) ||
                !Core::checkAgainstDimension($height, $imageArray[1])
            ) {

                $error = "There is something wrong with the dimensions of the uploaded image. The image should meet these conditions: width must be " . htmlentities($width) . " pixels, height must be " . htmlentities($height) . " pixels";

                $request->error->setError(rtrim($error, ",") . ". ");

                return false;
            }
        }
        
        return true;
    }
}
