<?php

/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 1/19/18
 * Time: 4:17 PM
 */

namespace Test\SimpleSAML;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\AttributeManipulator;
use SimpleSAML\Utils\Attributes;

class AttributeManipulatorTest extends TestCase
{
    /**
     * Test that resource owner attributes are flattened into a SSPs attributes array format
     */
    public function testPrefixAndFlatten()
    {

        // Single values always become arrays and complex objects are flattened, and not strings are stringified
        $attributes = [
            'a' => 'b',
            'complex' => ['e' => 'f'],
            'arrayValues' => ['a', 'b', 'c', 123, null],
            'bool' => false,
            'num' => 123,
            'missing' => null,
            // Google plus style emails are array of objects that have key value pairs
            "emails" => [
                0 => [
                    "value" => "monitor@cirrusidentity.com",
                    "type" => "account"
                ],
            ],
        ];

        $attributeManipulator = new AttributeManipulator();
        $flattenAttributes = $attributeManipulator->prefixAndFlatten($attributes);
        // Single values always become arrays and complex objects are flattened, and not strings are stringified
        $expectedAttributes = [
            'a' => ['b'],
            'complex.e' => ['f'],
            'arrayValues' => ['a', 'b', 'c', '123'],
            'bool' => ['false'],
            'num' => ['123'],
            'emails.0.value' => ['monitor@cirrusidentity.com'],
            'emails.0.type' => ['account'],
        ];
        $this->assertEquals($expectedAttributes, $flattenAttributes);

        $this->assertEquals($expectedAttributes, (new Attributes())->normalizeAttributesArray($flattenAttributes));
    }
}
