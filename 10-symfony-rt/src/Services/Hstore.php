<?php

namespace App\Services;

/**
 * Helper class to deal with the Postgres hstore data field. The class offers
 * methods to serialize and de-serialize the values of a Hstore and convert
 * those datasets into native PHP arrays.
 */
class Hstore
{
    /**
     * Helper method to serialize the given PHP array into the native postgres
     * representation of a Hstore.
     *
     * @param iterable $data
     * @return string
     */
    public static function serialize(iterable $data): string
    {
        // convert array to hstore representation as described here
        // http://www.postgresql.org/docs/devel/static/hstore.html
        $return = array();
        foreach ($data as $key => $value) {
            //try to convert objects to arrays
            if (\is_object($value)) {
                if (\method_exists($value, 'toArray')) {
                    $value = $value->toArray();
                } else {
                    $value = (array)$value;
                }
            }

            // neither key nor value can be arrays. The Postgres HStore is a
            // one-dimensional key => value store!
            if (\is_array($value)) {
                $value = \json_encode($value);
            }

            $return[] = '"' . self::encode($key) . '"=>"' . self::encode($value) . '"';
        }

        return \implode(',', $return);
    }

    /**
     * Helper method to deserialize the given Hstore string into a php array
     *
     * @param string $inputData
     * @return iterable
     */
    public static function deserialize(string $inputData): iterable
    {
        $return = [];
        //split into key/value pairs
        \preg_match_all("/\"(.*?)\"=>((?<!\\\)\"(.*?)(?<!\\\)\")/", $inputData, $parts);

        foreach ($parts[1] as $index => $key) {
            $key = self::decode($key);
            $value = self::decode($parts[3][$index]);
            $return[$key] = self::isJsonString($value) ? \json_decode($value, true) : $value;
        }

        return $return;
    }

    /**
     * @param $string
     * @return bool
     */
    public static function isJsonString($string)
    {
        if (!\is_string($string)) {
            return false;
        }
        if (!in_array(substr($string, 0, 1), ['{', '['])) {
            return false;
        }
        if (!in_array(substr($string, -1), ['}', ']'])) {
            return false;
        }
        \json_decode($string, true);
        return (\json_last_error() == JSON_ERROR_NONE);
    }

    protected static function encode(mixed $value): string
    {
        return addslashes(\htmlspecialchars($value, ENT_QUOTES));
    }

    protected static function decode(mixed $value): string
    {
        return \htmlspecialchars_decode(stripslashes($value), ENT_QUOTES);
    }
}
