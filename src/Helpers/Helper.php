<?php

namespace Mostafaznv\SimpleSDP\Helpers;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class Helper
{
    /**
     * Return Original config file.
     *
     * @param null $key
     * @return array|mixed
     */
    public static function originalConfig($key = null)
    {
        $path = null;

        if (is_file(base_path('vendor/mostafaznv/larupload/config/config.php')))
            $path = base_path('vendor/mostafaznv/larupload/config/config.php');
        else if (is_file(base_path('packages/mostafaznv/larupload/config/config.php')))
            $path = base_path('packages/mostafaznv/larupload/config/config.php');

        if ($path) {
            $config = File::getRequire(base_path('packages/mostafaznv/larupload/config/config.php'));
            if ($key and isset($config[$key]))
                return $config[$key];
            return $config;
        }
        return [];
    }

    /**
     * Merge two array recursively.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function arrayMergeRecursiveDistinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            }
            else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Validate files options.
     *
     * @param array $config
     * @return bool
     * @throws Exception
     */
    public static function validate(Array $config = [])
    {
        $rules = [
            'storage'       => 'string',
            'mode'          => 'string|in:light,heavy',
            'naming_method' => 'string|in:slug,hash_file,time',

            'styles'          => 'array',
            'styles.*'        => 'array',
            'styles.*.height' => 'numeric',
            'styles.*.width'  => 'numeric',
            'styles.*.mode'   => 'string:in:landscape,portrait,crop,exact,auto',
            'styles.*.type'   => 'array:in:image,video',

            'dominant_color'     => 'boolean',
            'generate_cover'     => 'boolean',
            'cover_style'        => 'array',
            'cover_style.width'  => 'numeric',
            'cover_style.height' => 'numeric',
            'cover_style.mode'   => 'string:in:landscape,portrait,crop,exact,auto',

            'keep_old_files'     => 'boolean',
            'preserve_files'     => 'boolean',
            'allowed_mime_types' => 'array',
            'allowed_mimes'      => 'array',
        ];

        $validator = Validator::make($config, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->getMessages();
            $fields = implode(', ', array_keys($errors));

            throw new Exception("invalid fields: $fields");
        }

        return true;
    }
}