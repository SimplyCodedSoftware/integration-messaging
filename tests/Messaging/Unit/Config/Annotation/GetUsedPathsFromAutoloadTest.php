<?php


namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Config\Annotation\GetUsedPathsFromAutoload;

/**
 * Class GetUsedPathsFromAutoloadTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetUsedPathsFromAutoloadTest extends TestCase
{
    public function test_retrieve_when_psr_4_namespace_is_equal_to_required()
    {
        $requiredNamepaces = ["Ecotone"];
        $autoload = [
            "Ecotone" => ["/src"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_0_namespace_is_equal_to_required()
    {
        $requiredNamepaces = ["Ecotone"];
        $autoload = [
            "Ecotone" => ["/src"]
        ];
        $autoloadPsr4 = false;
        $expectedPaths = ["/src/Ecotone"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespace_is_longer_than_required()
    {
        $requiredNamepaces = ["Ecotone"];
        $autoload = [
            "Ecotone\Some" => ["/src"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespace_is_shorter_than_required()
    {
        $requiredNamepaces = ["Ecotone\Some\Domain"];
        $autoload = [
            "Ecotone" => ["/src"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src/Some/Domain"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespaces_begins_with_similar_prefix()
    {
        $requiredNamepaces = ["Ecotone\Implementation"];
        $autoload = [
            "Ecotone\Test" => ["/src1"],
            "Ecotone\Implementation" => ["/src2"],
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src2"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespaces_continues_in_path()
    {
        $requiredNamepaces = ["Ecotone\Test\Domain"];
        $autoload = [
            "Ecotone\Test" => ["/src1"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src1/Domain"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieving_src_catalog()
    {
        $getUsedPathsFromAutoload = new GetUsedPathsFromAutoload();

        $this->assertEquals(
            ["SimplyCodedSoftware\One", "SimplyCodedSoftware\Two"],
            $getUsedPathsFromAutoload->getNamespacesForSrcCatalog(
                [
                    "psr-4" => ["SimplyCodedSoftware\One" => "src"],
                    "psr-0" => ["SimplyCodedSoftware\Two" => "src"]
                ]
            )
        );
    }

    public function test_not_retrieving_when_not_in_src_catalog()
    {
        $getUsedPathsFromAutoload = new GetUsedPathsFromAutoload();

        $this->assertEquals(
            [],
            $getUsedPathsFromAutoload->getNamespacesForSrcCatalog(
                [
                    "psr-4" => ["SimplyCodedSoftware\One" => "tests"],
                    "psr-0" => ["SimplyCodedSoftware\Two" => "tests"]
                ]
            )
        );
    }

    /**
     * @param array $requiredNamepaces
     * @param array $autoload
     * @param bool $autoloadPsr4
     * @param array $expectedPaths
     */
    private function validateExpectedPaths(array $requiredNamepaces, array $autoload, bool $autoloadPsr4, array $expectedPaths): void
    {
        $getUsedPathsFromAutoload = new GetUsedPathsFromAutoload();
        $resultsPaths = $getUsedPathsFromAutoload->getFor(
            $requiredNamepaces,
            $autoload,
            $autoloadPsr4
        );

        $this->assertEquals($expectedPaths, $resultsPaths);
    }
}