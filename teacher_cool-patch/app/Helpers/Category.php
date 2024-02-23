<?php

namespace App\Helpers;

class Category {

    public const CONTENT_CATEGORY_BOTH = 0;
    public const CONTENT_CATEGORY_IT = 1;
    public const CONTENT_CATEGORY_NON_IT = 2;
    public const CONTENT_CATEGORY_IT_WITHOUT_CODING = 3;
    public const CONTENT_OTHERS = 4;

    public static function getCetegory()
    {
        return [
            ['value'=>static::CONTENT_CATEGORY_BOTH, 'name' => "Both"],
            ['value'=>static::CONTENT_CATEGORY_IT, 'name' => "IT"],
            ['value'=>static::CONTENT_CATEGORY_NON_IT, 'name' =>  "Non-IT"],
        ];
    }

    public static function getCetegoryForOuestions()
    {
        return [
            ['value'=>static::CONTENT_CATEGORY_IT, 'name' => "IT Coding"],
            ['value'=>static::CONTENT_CATEGORY_IT_WITHOUT_CODING, 'name' => "IT"],
            ['value'=>static::CONTENT_CATEGORY_NON_IT, 'name' =>  "Non-IT"],
            ['value'=>static::CONTENT_OTHERS, 'name' =>  "Others"],
        ];
    }

    public static function subjectCetegory()
    {
        return [
            // ['value'=>static::CONTENT_CATEGORY_BOTH, 'name' => "Both"],
            ['value'=>static::CONTENT_CATEGORY_IT, 'name' => "IT Coding"],
            ['value'=>static::CONTENT_CATEGORY_IT_WITHOUT_CODING, 'name' => "IT"],
            ['value'=>static::CONTENT_CATEGORY_NON_IT, 'name' =>  "Non-IT"],
            
        ];
    }
}