<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Wovosoft\LaravelTypescript\Types\Type as GenericType;

final class Types {
    public static function getGenericType(string $key): GenericType {
        $transformers = [
            'ascii_string'         => GenericType::string(comment: 'ASCII String'),
            'bigint'               => GenericType::number(),
            'int8'                 => GenericType::number(),
            'bool'                 => GenericType::boolean(),
            'binary'               => GenericType::unknown(comment: 'Binary Data'),
            'blob'                 => GenericType::unknown(comment: 'Binary Large Object'),
            'boolean'              => GenericType::boolean(),
            'timestamp'            => GenericType::string(comment: 'Timestamp'),
            'character'            => GenericType::string(comment: 'Character'),
            'varchar'              => GenericType::string(comment: 'Variable Character'),
            'char'                 => GenericType::string(comment: 'Character'),
            'date'                 => GenericType::string(comment: 'Date'),
            'date_mutable'         => GenericType::mutableDate(),
            'date_immutable'       => GenericType::immutableDate(),
            'dateinterval'         => GenericType::string(comment: 'date interval'),
            'datetime'             => GenericType::string(comment: 'Datetime'),
            'datetime_immutable'   => GenericType::immutableDatetime(),
            'datetimetz_mutable'   => GenericType::mutableDatetimeZ(),
            'datetimetz_immutable' => GenericType::immutableDatetimeZ(),
            'decimal'              => GenericType::number(),
            'float'                => GenericType::float(),
            'guid'                 => GenericType::string(comment: 'GUID'),
            'integer'              => GenericType::number(),
            'json'                 => GenericType::json(),
            'simple_array'         => GenericType::any(isMultiple: true, comment: 'Simple Array'),
            'smallint'             => GenericType::number(comment: "Small Integer"),
            'string'               => GenericType::string(),
            'text'                 => GenericType::string(comment: "Text"),
            'time_mutable'         => GenericType::mutableTime(),
            'time_immutable'       => GenericType::immutableTime(),
            'serial'               => GenericType::number(comment: "Serial"),
            'serial8'              => GenericType::number(comment: "Serial 8"),
            'money'                => GenericType::number(comment: "Money"),
            'xml'                  => GenericType::string(comment: "XML"),
            'uuid'                 => GenericType::string(comment: "UUID"),
            'citext'               => GenericType::string(comment: "Case Insensitive Text"),
            'macaddr'              => GenericType::string(comment: "MAC Address"),
            'inet'                 => GenericType::string(comment: "Internet Address"),
            'cidr'                 => GenericType::string(comment: "CIDR"),
            'tsvector'             => GenericType::string(comment: "Text Search Vector"),
            'tsquery'              => GenericType::string(comment: "Text Search Query"),
            'tinyint'              => GenericType::number(comment: "Tiny Integer"),
            'mediumint'            => GenericType::number(comment: "Medium Integer"),
            'numeric'              => GenericType::number(comment: "Numeric"),
            'dec'                  => GenericType::number(comment: "Decimal"),
            'double'               => GenericType::number(comment: "Double"),
            'double_precision'     => GenericType::number(comment: "Double Precision"),
            'real'                 => GenericType::number(comment: "Real"),
            'bit'                  => GenericType::number(comment: "Bit"),
            'enum'                 => GenericType::string(comment: "Enum"),
            'set'                  => GenericType::string(comment: "Set"),
            'smalldatetime'        => GenericType::string(comment: "Small Datetime"),
            'datetime2'            => GenericType::string(comment: "Datetime2"),
            'datetimeoffset'       => GenericType::string(comment: "Datetime Offset"),
            'time'                 => GenericType::string(comment: "Time"),
            'smallmoney'           => GenericType::number(comment: "Small Money"),
            'image'                => GenericType::unknown(comment: "Image"),
            'ntext'                => GenericType::string(comment: "NText"),
            'uniqueidentifier'     => GenericType::string(comment: "Unique Identifier"),
            'rowversion'           => GenericType::string(comment: "Row Version"),
            'geography'            => GenericType::string(comment: "Geography"),
            'geometry'             => GenericType::string(comment: "Geometry"),
            'null'                 => GenericType::unknown(comment: "Null"),
        ];

        return $transformers[$key] ?? GenericType::any();
    }

    private function __construct() {
    }
}

