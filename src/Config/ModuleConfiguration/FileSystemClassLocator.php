<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationReader;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;

/**
 * Class ClassLocator
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemClassLocator implements ClassLocator {
    const INTEGRATION_MESSAGING_NAMESPACE = 'IntegrationMessaging';
    const SIMPLY_CODED_SOFTWARE_NAMESPACE = 'SimplyCodedSoftware';

    private const FILE_EXTENSION = '.php';
    /**
     * @var array|string[]
     */
    private $projectClasses;
    /**
     * @var array|string[]
     */
    private $namespacesToUse;

    /**
     * @param string|array $paths One or multiple paths where mapping documents can be found.
     * @param array|string[] $namespaces
     */
    public function __construct(array $paths, array $namespaces)
    {
        $this->namespacesToUse = $namespaces;
        $this->init($paths);
    }

    /**
     * @return array|string[]
     */
    public function getAllClasses() : array
    {
        return $this->projectClasses;
    }

    /**
     * @param string $annotationName
     * @return array|string[]
     */
    public function getAllClassesWithAnnotation(string $annotationName) : array
    {
        $classesWithAnnotations = [];
        foreach ($this->getAllClasses() as $class) {
            $annotationReader = new AnnotationReader();
            $classAnnotation = $annotationReader->getClassAnnotation($class, $annotationName);

            if ($classAnnotation) {
                $classesWithAnnotations[] = $class;
            }
        }

        return $classesWithAnnotations;
    }

    /**
     * {@inheritDoc}
     */
    private function init(array $paths)
    {
        $classes = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                throw ConfigurationException::create("There is no path: {$path}");
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                $fileName = $file->getBasename(self::FILE_EXTENSION);
                $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename();

                if ($fileName == $file->getBasename()) {
                    continue;
                }

                $classContent = file_get_contents($filePath);
                preg_match_all("#\snamespace([^\n]*);#", $classContent, $results);
                $namespace = isset($results[1][0]) ? trim($results[1][0]) : "";

                foreach ($this->namespacesToUse as $namespaceToUse) {
                    if (substr($namespace, 0, strlen($namespaceToUse)) !== $namespaceToUse) {
                        continue;
                    }

                    $classes[] = trim($namespace) . '\\' . $fileName;
                }
            }
        }

        $this->projectClasses = $classes;
    }

}