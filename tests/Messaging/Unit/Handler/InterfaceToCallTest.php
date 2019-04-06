<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler;

use SimplyCodedSoftware\Messaging\Annotation\Converter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\AbstractSuperAdmin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Admin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Email;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\ExampleTestAnnotation;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\LazyUser;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Permission;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\OnlineShop;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Password;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\SuperAdmin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\TwoStepPassword;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\User;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class InterfaceToCallTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCallTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeName"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("name", TypeDescriptor::create(TypeDescriptor::STRING)),
            $interfaceToCall->getParameterWithName("name")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_doc_block_guessing_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changePassword"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("password", TypeDescriptor::create(Password::class)),
            $interfaceToCall->getParameterWithName("password")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_global_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeDetails"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("details", TypeDescriptor::create("\\stdClass")),
            $interfaceToCall->getParameterWithName("details")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_than_class_it_self()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_from_different_namespace_using_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeSingleFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourite", TypeDescriptor::create("\Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite")),
            $interfaceToCall->getParameterWithName("favourite")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_transformation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "removeFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_class_name_from_use_statements()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "disableFavourites"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourites", TypeDescriptor::create("array<\Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\Favourite>")),
            $interfaceToCall->getParameterWithName("favourites")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_use_statement_alias()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addAdminPermission"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("adminPermission", TypeDescriptor::create(Permission::class)),
            $interfaceToCall->getParameterWithName("adminPermission")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_collection_of_scalar_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addRatings"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("ratings", TypeDescriptor::create("array<int>")),
            $interfaceToCall->getParameterWithName("ratings")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_retrieving_parameter_type_hint_with_primitive_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "removeRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("rating", TypeDescriptor::create(TypeDescriptor::INTEGER)),
            $interfaceToCall->getParameterWithName("rating")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_compound_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "randomRating"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("random",TypeDescriptor::create( TypeDescriptor::INTEGER)),
            $interfaceToCall->getParameterWithName("random")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_first_type_hint_from_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addPhones"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("phones", TypeDescriptor::create(TypeDescriptor::ARRAY)),
            $interfaceToCall->getParameterWithName("phones")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_type_hint_from_scalar_and_class_name()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "addEmail"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("email", TypeDescriptor::create(Email::class)),
            $interfaceToCall->getParameterWithName("email")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_interface_inherit_doc_with_different_parameter_name()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "buy"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("productId", TypeDescriptor::create(\stdClass::class)),
            $interfaceToCall->getParameterWithName("productId")
        );
    }

    public function test_retrieving_annotations_from_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "buy"
        );

        $this->assertEquals(
            [new ExampleTestAnnotation()],
            $interfaceToCall->getMethodAnnotations()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_inherit_doc_with_return_type_from_super_class_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            LazyUser::class, "bannedBy"
        );

        $this->assertEquals(
            TypeDescriptor::create(Admin::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_parameter_from_abstract_class_inherit_doc()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "findGames"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("gameId", TypeDescriptor::create(TypeDescriptor::STRING)),
            $interfaceToCall->getParameterWithName("gameId")
        );
    }

    public function test_resolving_return_type_type_hint_from_trait_in_different_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class, "getLessFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNotNullable("favourite", TypeDescriptor::create(Favourite::class)),
            $interfaceToCall->getParameterWithName("favourite")
        );
        $this->assertEquals(
            TypeDescriptor::create(Favourite::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_resolving_return_type_type_hint_located_in_docblock_from_trait_in_different_namespace()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class, "getYourVeryBestFavourite"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("favourite", TypeDescriptor::create(Favourite::class)),
            $interfaceToCall->getParameterWithName("favourite")
        );
        $this->assertEquals(
            TypeDescriptor::create(Favourite::class),
            $interfaceToCall->getReturnType()
        );
    }

    public function test_overriding_trait_method_return_type()
    {
        $interfaceToCall = InterfaceToCall::create(
            SuperAdmin::class, "getUser"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("user", TypeDescriptor::create(SuperAdmin::class)),
            $interfaceToCall->getParameterWithName("user")
        );
        $this->assertEquals(
            TypeDescriptor::create(SuperAdmin::class),
            $interfaceToCall->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_return_type_based_on_inherit_doc_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(
            OnlineShop::class, "findGames"
        );

        $this->assertTrue($interfaceToCall->doesItReturnIterable());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_guessing_unknown_if_no_type_hint_information_available()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeSurname"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("surname", TypeDescriptor::create(TypeDescriptor::UNKNOWN)),
            $interfaceToCall->getParameterWithName("surname")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function test_choosing_unknown_type_if_mixed_type_hint_in_doc_block()
    {
        $interfaceToCall = InterfaceToCall::create(
            User::class, "changeAddress"
        );

        $this->assertEquals(
            InterfaceParameter::createNullable("address", TypeDescriptor::create(TypeDescriptor::UNKNOWN)),
            $interfaceToCall->getParameterWithName("address")
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_choosing_declaring_class_if_use_this_or_self_or_static()
    {
        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "getSelf"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "getStatic"))->getReturnType()
        );

        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "getSelfWithoutDocBlock"))->getReturnType()
        );
    }

    public function test_different_different_class_under_same_alias_than_in_declaring_one()
    {
        $this->assertEquals(
            TypeDescriptor::create(TwoStepPassword::class),
            (InterfaceToCall::create(SuperAdmin::class, "getPassword"))->getParameterWithName("password")->getTypeDescriptor()
        );
        $this->assertEquals(
            TypeDescriptor::create(TwoStepPassword::class),
            (InterfaceToCall::create(SuperAdmin::class, "getPassword"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_type_hint_with_full_qualified_name()
    {
        $this->assertEquals(
            TypeDescriptor::create(User::class),
            (InterfaceToCall::create(User::class, "returnFullUser"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_guessing_type_hint_from_global_namespace()
    {
        $this->assertEquals(
            TypeDescriptor::create(\stdClass::class),
            (InterfaceToCall::create(User::class, "returnFromGlobalNamespace"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_resolving_sub_class_for_static_type_hint_return_type()
    {
        $this->assertEquals(
            TypeDescriptor::create(SuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getSuperAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_interface()
    {
        $this->assertEquals(
            TypeDescriptor::create(Admin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getAdmin"))->getReturnType()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_resolving_declaring_class_for_self_type_hint_declared_in_abstract_class()
    {
        $this->assertEquals(
            TypeDescriptor::create(AbstractSuperAdmin::class),
            (InterfaceToCall::create(SuperAdmin::class, "getInformation"))->getReturnType()
        );
    }

    public function test_retrieving_annotations()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->assertEquals(
            [new Converter()],
            $interfaceToCall->getMethodAnnotations()
        );
        $this->assertEquals(
            [new MessageEndpoint()],
            $interfaceToCall->getClassAnnotations()
        );
    }

    public function test_retrieving_specific_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->assertTrue($interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(Converter::class)));
        $this->assertFalse($interfaceToCall->hasMethodAnnotation(TypeDescriptor::create(MessageEndpoint::class)));

        $this->assertTrue($interfaceToCall->hasClassAnnotation(TypeDescriptor::create(MessageEndpoint::class)));
        $this->assertFalse($interfaceToCall->hasClassAnnotation(TypeDescriptor::create(Converter::class)));

        $this->assertEquals(
            new MessageEndpoint(),
            $interfaceToCall->getClassAnnotation(TypeDescriptor::create(MessageEndpoint::class))
        );
        $this->assertEquals(
            new Converter(),
            $interfaceToCall->getMethodAnnotation(TypeDescriptor::create(Converter::class))
        );
    }

    public function test_throwing_exception_when_retrieving_not_existing_method_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getMethodAnnotation(TypeDescriptor::create(MessageEndpoint::class));
    }

    public function test_throwing_exception_when_retrieving_not_existing_class_annotation()
    {
        $interfaceToCall = InterfaceToCall::create(ExampleConverterService::class, "convert");

        $this->expectException(InvalidArgumentException::class);

        $interfaceToCall->getClassAnnotation(TypeDescriptor::create(Converter::class));
    }
}