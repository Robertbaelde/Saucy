<?php

namespace Robertbaelde\Saucy\ClassNameMapper;

use DirectoryIterator;

class ClassResolver
{
    public const CLASS_NAMESPACE_REGEX = "#namespace[\s]*([^\n\s\(\)\[\]\{\}\$]*);#";

    public function getClasses(string | array $namespaces, string $baseDir): array
    {
        if(is_string($namespaces)) {
            $namespaces = [$namespaces];
        }
        if (!is_dir($baseDir)) {
            throw new \InvalidArgumentException(sprintf('directory "%s" does not exist.', $baseDir));
        }
        $files = $this->loadClassesFromDirectory($baseDir);

        $classes = [];

        foreach ($files as $file) {
            if (preg_match_all(self::CLASS_NAMESPACE_REGEX, file_get_contents($file), $results)) {
                $namespace = isset($results[1][0]) ? trim($results[1][0]) : '';
                $namespace = trim($namespace, "\t\n\r\\");
                $classes[] = $namespace . '\\' . basename($file, '.php');
            }
        }

        // Now you can retrieve all classes in the namespace
        $classesInNamespace = array_filter(
            $classes,
            function ($className) use ($namespaces) {
                foreach ($namespaces as $namespaceToUse) {
                    if(strpos($className, $namespaceToUse) === 0){
                        return true;
                    }
                }
                return false;
            }
        );

        return $classesInNamespace;
    }

    private function loadClassesFromDirectory($dir): array {
        $classNames = [];
        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) continue;

            if ($file->isDir()) {
                $classNames = array_merge($this->loadClassesFromDirectory($file->getPathname()), $classNames);
            } elseif ($file->getExtension() === 'php') {
                $classNames[] =  $file->getPathname();
            }
        }
        return $classNames;
    }

}
