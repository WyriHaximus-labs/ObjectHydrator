<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use DateTime;
use DateTimeImmutable;
use EventSauce\ObjectHydrator\Fixtures\ClassReferencedByUnionOne;
use EventSauce\ObjectHydrator\Fixtures\ClassReferencedByUnionTwo;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCasePublicMethod;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCustomDateTimeSerialization;
use EventSauce\ObjectHydrator\Fixtures\ClassWithListOfObjects;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUnionProperty;
use PHPUnit\Framework\TestCase;

abstract class ObjectSerializerTestCase extends TestCase
{
    abstract public function objectSerializer(): ObjectSerializer;

    /**
     * @test
     */
    public function serializing_an_object_with_a_public_property(): void
    {
        $serializer = $this->objectSerializer();
        $object = new ClassWithCamelCaseProperty('some_property');

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['snake_case' => 'some_property'], $payload);
    }

    /**
     * @test
     */
    public function serializing_an_object_with_a_public_method(): void
    {
        $serializer = $this->objectSerializer();
        $object = new ClassWithCamelCasePublicMethod('some_property');

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['camel_case' => 'some_property'], $payload);
    }

    /**
     * @test
     */
    public function serializing_a_list_of_custom_objects(): void
    {
        $serializer = $this->objectSerializer();
        $object = new ClassWithListOfObjects([
            new ClassWithCamelCasePublicMethod('first_element'),
            new ClassWithCamelCasePublicMethod('second_element'),
        ]);

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['children' => [
            ['camel_case' => 'first_element'],
            ['camel_case' => 'second_element'],
        ]], $payload);
    }

    /**
     * @test
     */
    public function serializing_a_list_of_internal_objects(): void
    {
        $serializer = $this->objectSerializer();
        $now = new DateTimeImmutable();
        $nowFormatted = $now->format('Y-m-d H:i:s.uO');
        $object = new ClassWithListOfObjects([$now]);

        $payload = $serializer->serializeObject($object);

        self::assertEquals(['children' => [$nowFormatted]], $payload);
    }

    /**
     * @test
     */
    public function serializing_using_custom_date_time_formats(): void
    {
        $serializer = $this->objectSerializer();
        $object = new ClassWithCustomDateTimeSerialization(
            promotedPublicProperty: DateTimeImmutable::createFromFormat('!Y-m-d', '1987-11-24'),
            regularPublicProperty: DateTimeImmutable::createFromFormat('!Y-m-d', '1987-11-25'),
            getterProperty: DateTime::createFromFormat('!Y-m-d', '1987-11-26')
        );

        $payload = $serializer->serializeObject($object);

        self::assertEquals([
            'promoted_public_property' => '24-11-1987',
            'regular_public_property' => '25-11-1987',
            'getter_property' => '26-11-1987',
        ], $payload);
    }

    /**
     * @test
     */
    public function serializing_a_class_with_a_union(): void
    {
        $serializer = $this->objectSerializer();
        $object1 = new ClassWithUnionProperty(
            new ClassReferencedByUnionOne(1234),
            'name',
            new ClassReferencedByUnionOne(1234),
        );
        $object2 = new ClassWithUnionProperty(
            new ClassReferencedByUnionTwo('name'),
            1234,
            2345
        );

        $payload1 = $serializer->serializeObject($object1);
        $payload2 = $serializer->serializeObject($object2);

        self::assertEquals([
            'union' => ['number' => 1234],
            'built_in_union' => 'name',
            'mixed_union' => ['number' => 1234],
        ], $payload1);
        self::assertEquals([
            'union' => ['text' => 'name'],
            'built_in_union' => 1234,
            'mixed_union' => 2345,
        ], $payload2);
    }
}
