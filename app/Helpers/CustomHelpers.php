<?php

use App\Libraries\PermissionsLibrary;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;

if (! function_exists('image')) {
    function image($path, $image, $dummy = '')
    {
        try {
            $path = trim($path, '/');

            $final_image = file_exists(getcwd()."/{$path}/{$image}") ? asset("{$path}/{$image}") : ($dummy != '' ? asset('assets/images/'.$dummy) : asset('assets/images/no-image.png'));
        } catch (\Exception $e) {
            $final_image = asset('assets/images/no-image.png');
        }

        return asset($final_image);
    }
}

if (! function_exists('read_image')) {
    function read_image($image = null)
    {
        if (is_null($image) || $image == '' || $image == 0 || $image == 'null') {
            return asset('assets/images/no-image-uploaded.png');
        }

        try {
            $upload = Upload::find($image);

            if ($upload) {
                $final_image = file_exists(getcwd()."/uploads/images/{$upload->path}") ? asset("uploads/images/{$upload->path}") : asset('assets/images/no-image.png');
            } else {
                $final_image = asset('assets/images/no-image.png');
            }
        } catch (\Exception $e) {
            $final_image = asset('assets/images/no-image.png');
        }

        return $final_image;
    }
}

if (! function_exists('check_is_image')) {
    function check_is_image($image = null)
    {

        $upload = Upload::find($image);

        if ($upload) {
            return true;
        }

        return false;
    }
}

if (! function_exists('create_image')) {
    function create_image($image)
    {
        try {
            $upload = new Upload;
            $upload->path = $image;

            if ($upload->save()) {
                return $upload->id;
            } else {
                throw new Exception('asset image not created');
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

if (! function_exists('delete_image')) {
    function delete_image($image = null)
    {
        try {
            $upload = Upload::find($image);

            if ($upload) {
                Storage::disk('public')->delete("images/{$upload->path}");
                $upload->delete();
            }

            return 1;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

if (! function_exists('current_academic_year')) {
    function current_academic_year(): int
    {
        $value = config('options.current_academic_year');

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        return (int) date('Y');
    }
}

if (! function_exists('getPermissions')) {
    function getPermissions($key, $type, $permissions = [])
    {
        try {
            if (empty($permissions)) {
                $permissions = config('admin_permissions');

                // if permissions in config is empty
                if (is_null($permissions) || empty($permissions)) {
                    $permissionsLibrary = new PermissionsLibrary;
                    $permissionsLibrary->initPermissions();
                    $permissions = $permissionsLibrary->getPermissions();
                }
            }

            return $permissions[$key][$type];
        } catch (\Exception $e) {

            // if is in production mode
            if (config('app.debug') == false) {
                return false;
            }
            throw new \Exception("Permission for {$key} not found");
        }
    }
}
