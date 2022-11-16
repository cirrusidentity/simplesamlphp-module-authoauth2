<?php

namespace SimpleSAML\Module\authoauth2;

/**
 *
 * Take the arbitrarily complex response from OAuth2 endpoints and
 * flatten into a format usable by SSP.
 * Class AttributeManipulator
 * @package SimpleSAML\Module\authoauth2
 */
class AttributeManipulator
{
    /**
     * Take the attributes from an OAuth2 provider and convert them into the structure used by SSP.
     * @param array $array the attributes to flatten and prefix
     * @param string $prefix The prefix to use
     *
     * @return array the array with the new concatenated keys and all values in an array
     */
    public function prefixAndFlatten(array $array, string $prefix = ''): array
    {
        $result = array();
        foreach ($array as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                if ($this->isSimpleSequentialArray($value)) {
                    $result[$prefix . $key] = $this->stringify($value);
                } else {
                    $result = $result + $this->prefixAndFlatten($value, $prefix . $key . '.');
                }
            } else {
                // User strval to handle non-string types
                $result[$prefix . $key] = array($this->stringify($value));
            }
        }
        return $result;
    }

    /**
     * Attempt to stringify the input
     * @param mixed $input if an array stringify the values, removing nulls
     * @return array|string
     */
    protected function stringify($input)
    {
        if (is_bool($input)) {
            return $input ? 'true' : 'false';
        } else {
            if (is_array($input)) {
                $array = [];
                foreach ($input as $key => $value) {
                    if ($value === null) {
                        continue;
                    }
                    $array[$key] = $this->stringify($value);
                }
                return $array;
            }
        }
        return strval($input);
    }

    /**
     * Determine if the array is a sequential [ 'a', 'b'] or [ 0 => 'a', 1 => 'b'] array with all values being
     * simple types
     * @param array $array The array to check
     * @return bool true if is sequential and values are simple (not array)
     */
    private function isSimpleSequentialArray(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (!is_int($key) || is_array($value)) {
                return false;
            }
        }
        return true;
    }
}
