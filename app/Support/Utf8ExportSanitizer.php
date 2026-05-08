<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Utf8ExportSanitizer
{
    public static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::cleanString($value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::clean($item);
            }

            return $value;
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($item) => self::clean($item));
        }

        if ($value instanceof Model) {
            foreach ($value->getAttributes() as $attribute => $attributeValue) {
                if (is_string($attributeValue)) {
                    $value->setAttribute($attribute, self::cleanString($attributeValue));
                }
            }

            foreach ($value->getRelations() as $relation => $relationValue) {
                $value->setRelation($relation, self::clean($relationValue));
            }

            return $value;
        }

        if (is_object($value)) {
            foreach (get_object_vars($value) as $property => $propertyValue) {
                $value->{$property} = self::clean($propertyValue);
            }

            return $value;
        }

        return $value;
    }

    private static function cleanString(string $value): string
    {
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($cleaned === false) {
            $cleaned = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return preg_replace('/[^\P{C}\t\r\n]/u', '', $cleaned) ?? '';
    }
}

