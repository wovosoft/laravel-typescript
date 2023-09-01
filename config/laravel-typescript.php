<?php

return [
    'output_path'       => resource_path('js/types/models.d.ts'),
    'source_dir'        => app_path('Models'),
    /**
     * Custom attributes should have return types defined.
     * But if it is not, then the return type should be this type.
     * And this value should be php supported return types.
     * like primitive types or any other classes
     */
    "custom_attributes" => [
        "fallback_return_type" => "string"
    ]
];
