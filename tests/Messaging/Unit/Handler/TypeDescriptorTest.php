<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class TypeDescriptorTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TypeDescriptorTest extends TestCase
{
    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_compound_type_and_array_of_scalar_type()
    {
        $typeDescription = TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY,  "array<string>");

        $this->assertEquals(
            'array<string>',
            $typeDescription->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_null()
    {
        $this->assertEquals(
            TypeDescriptor::UNKNOWN,
            ($typeDescription = TypeDescriptor::create("null"))->getTypeHint()
        );

        $this->assertEquals(
            TypeDescriptor::UNKNOWN,
            ($typeDescription = TypeDescriptor::createWithDocBlock(TypeDescriptor::UNKNOWN, "null"))->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_doc_block_type_is_incorrect()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY,  "array<bla>");
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_type_hint_is_incorrect()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock("bla",  TypeDescriptor::ARRAY);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_incompatible_scalar_type_hint_and_compound_doc_block_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::STRING,  TypeDescriptor::ARRAY);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_incompatible_compound_type_hint_and_scalar_doc_block_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY,  TypeDescriptor::INTEGER);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_incompatible_resource_type_hint_and_scalar_doc_block_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::RESOURCE, TypeDescriptor::INTEGER);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_incompatible_scalar_type_hint_and_resource_doc_block_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::INTEGER,  TypeDescriptor::RESOURCE);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_incompatible_resource_hint_and_compound_doc_block_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::RESOURCE,  TypeDescriptor::ARRAY);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passing_incompatible_compound_hint_and_resource_doc_block_type()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  TypeDescriptor::RESOURCE);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_converting_doc_block_array_type_to_generic()
    {
        $this->assertEquals(
            "array<\stdClass>",
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  "\stdClass[]")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_on_incompatible_class_type_hint_and_array_doc_block()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(\stdClass::class,  "array<\stdClass>");
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_on_incompatible_array_type_hint_and_class_doc_block()
    {
        $this->expectException(TypeDefinitionException::class);

        TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY, \stdClass::class);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_doc_block_type_hint_over_compound()
    {
        $this->assertEquals(
            "array<\stdClass>",
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ARRAY, "array<\stdClass>")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_doc_block_collection_type_hint_over_compound()
    {
        $typeDescriptor = TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  "\ArrayCollection<\stdClass>");

        $this->assertEquals(
            "\ArrayCollection<\stdClass>",
            $typeDescriptor->getTypeHint()
        );

        $this->assertEquals(
            [TypeDescriptor::create(\stdClass::class)],
            $typeDescriptor->resolveGenericTypes()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_resolving_collection_type_for_non_collection()
    {
        $typeDescriptor = TypeDescriptor::create(TypeDescriptor::STRING);

        $this->expectException(InvalidArgumentException::class);

        $typeDescriptor->resolveGenericTypes();
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_checking_equality()
    {
        $this->assertTrue(
            TypeDescriptor::create(TypeDescriptor::STRING)
                ->sameTypeAs(TypeDescriptor::create(TypeDescriptor::STRING))
        );

        $this->assertTrue(
            TypeDescriptor::createWithDocBlock(TypeDescriptor::ITERABLE,  "\stdClass[]")
                ->sameTypeAs(TypeDescriptor::create("array<\stdClass>"))
        );

        $this->assertFalse(
            TypeDescriptor::create(TypeDescriptor::OBJECT)
                ->sameTypeAs(TypeDescriptor::create(TypeDescriptor::INTEGER))
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_doc_block_class_type_over_class_type_hint()
    {
        $this->assertEquals(
            "\\" . \stdClass::class,
            TypeDescriptor::createWithDocBlock(\Countable::class, \stdClass::class)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_picking_class_from_doc_block_if_type_hint_is_compound_object()
    {
        $this->assertEquals(
            "\\" . \stdClass::class,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::OBJECT, \stdClass::class)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_first_type_if_union_doc_block_type_hint()
    {
        $this->assertEquals(
            "\\" . \stdClass::class,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::OBJECT, "\stdClass|\Countable")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_doc_block_type_if_type_hint_is_unknown()
    {
        $this->assertEquals(
            TypeDescriptor::ARRAY,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::UNKNOWN,  TypeDescriptor::ARRAY)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_with_prefixed_type()
    {
        $this->assertEquals(
            "\\" . \stdClass::class,
            TypeDescriptor::create("\stdClass")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_with_compound_object_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::OBJECT,
            TypeDescriptor::create(TypeDescriptor::OBJECT)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_for_void_return_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::VOID,
            TypeDescriptor::create(TypeDescriptor::VOID)->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_with_mixed_type_result_in_unknown_type_hint()
    {
        $this->assertEquals(
            TypeDescriptor::UNKNOWN,
            TypeDescriptor::createWithDocBlock(TypeDescriptor::UNKNOWN,  "mixed")->getTypeHint()
        );
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_guessing_type_from_variable()
    {
        $this->assertEquals(TypeDescriptor::FLOAT, TypeDescriptor::createFromVariable(1.21));
        $this->assertEquals(TypeDescriptor::INTEGER, TypeDescriptor::createFromVariable(121));
        $this->assertEquals(TypeDescriptor::STRING, TypeDescriptor::createFromVariable("text"));
        $this->assertEquals(TypeDescriptor::ITERABLE, TypeDescriptor::createFromVariable([]));
        $this->assertEquals("\\" . \stdClass::class, TypeDescriptor::createFromVariable(new \stdClass()));
        $this->assertEquals(TypeDescriptor::RESOURCE, TypeDescriptor::createFromVariable(fopen('file', 'w+')));
        $this->assertEquals(TypeDescriptor::UNKNOWN, TypeDescriptor::createFromVariable(null));
        $this->assertEquals(TypeDescriptor::CALLABLE, TypeDescriptor::createFromVariable(function (){}));
    }

    /**
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_collection_type()
    {
        $this->assertEquals("array<\stdClass>", TypeDescriptor::createCollection(\stdClass::class)->toString());
    }

    public function test_creating_for_boolean_with_full_name()
    {
        $this->assertEquals(
            TypeDescriptor::createBooleanType(),
            TypeDescriptor::create("boolean")
        );
    }

    public function test_creating_for_integer_with_full_name()
    {
        $this->assertEquals(
            TypeDescriptor::createIntegerType(),
            TypeDescriptor::create("integer")
        );
    }
}