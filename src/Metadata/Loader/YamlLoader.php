<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoaderTrait;
use Kcs\Serializer\Annotation as Annotations;
use Kcs\Serializer\Metadata\ClassMetadata;
use Symfony\Component\Yaml\Yaml;

class YamlLoader extends AnnotationLoader
{
    use FileLoaderTrait;
    use LoaderTrait;

    /**
     * @var array
     */
    private $config;

    public function __construct($filePath)
    {
        parent::__construct();

        $this->config = (array) Yaml::parse($this->loadFile($filePath));
    }

    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata)
    {
        $config = $this->getClassConfig($classMetadata->getName());
        if (Annotations\ExclusionPolicy::ALL === $classMetadata->exclusionPolicy) {
            if (array_key_exists($property->name, $config['properties']) && null === $config['properties'][$property->name]) {
                return false;
            }

            return ! isset($config['properties'][$property->name]['expose']) || ! $config['properties'][$property->name]['expose'];
        }

        return isset($config['properties'][$property->name]['exclude']) && $config['properties'][$property->name]['exclude'];
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (! $this->hasClassConfig($classMetadata->getName())) {
            return true;
        }

        return parent::loadClassMetadata($classMetadata);
    }

    protected function isExcluded(\ReflectionClass $class)
    {
        $config = $this->getClassConfig($class->name);

        return isset($config['exclude']) ? (bool) $config['exclude'] : false;
    }

    protected function getClassAnnotations(ClassMetadata $classMetadata)
    {
        $config = $this->getClassConfig($classMetadata->getName());

        $annotations = [];
        foreach ($config as $key => $value) {
            if ('static_fields' === $key) {
                foreach ($value as $property => $item) {
                    if (! is_array($item)) {
                        $item = ['value' => $item];
                    }

                    $annotation = new Annotations\StaticField();
                    $annotation->name = $property;
                    $annotation->value = $item['value'];
                    unset($item['value']);

                    $annotation->attributes = $this->loadProperty($item);
                    $annotations[] = $annotation;
                }

                continue;
            }

            if (in_array($key, ['properties', 'virtual_properties'])) {
                continue;
            }

            $annotations = array_merge($annotations, $this->createAnnotationsForArray($value, $key));
        }

        return $annotations;
    }

    protected function getMethodAnnotations(\ReflectionMethod $method)
    {
        $annotations = [];
        $methodName = $method->name;
        $config = $this->getClassConfig($method->class);

        if (array_key_exists($methodName, $config['virtual_properties'])) {
            $annotations[] = new Annotations\VirtualProperty();

            $methodConfig = $config['virtual_properties'][$methodName] ?: [];
            $annotations = array_merge($annotations, $this->loadProperty($methodConfig));
        }

        if (array_search($methodName, $config['pre_serialize'])) {
            $annotations[] = new Annotations\PreSerialize();
        }
        if (array_search($methodName, $config['post_serialize'])) {
            $annotations[] = new Annotations\PostSerialize();
        }
        if (array_search($methodName, $config['post_deserialize'])) {
            $annotations[] = new Annotations\PostDeserialize();
        }

        return $annotations;
    }

    protected function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $config = $this->getClassConfig($property->class);
        $propertyName = $property->name;

        if (! isset($config['properties'][$propertyName])) {
            return [];
        }

        return $this->loadProperty($config['properties'][$propertyName]);
    }

    private static function isAssocArray(array $value)
    {
        return array_keys($value) !== array_keys(array_values($value));
    }

    private function loadProperty(array $config)
    {
        $annotations = [];

        foreach ($config as $key => $value) {
            $annotations = array_merge($annotations, $this->createAnnotationsForArray($value, $key));
        }

        return $annotations;
    }

    private function hasClassConfig($class)
    {
        return isset($this->config[$class]);
    }

    private function getClassConfig($class)
    {
        $config = isset($this->config[$class]) ? $this->config[$class] : [];

        return array_merge([
            'virtual_properties' => [],
            'pre_serialize' => [],
            'post_serialize' => [],
            'post_deserialize' => [],
        ], $config);
    }

    private function createAnnotationsForArray($value, $key)
    {
        $annotations = [];

        if (! is_array($value)) {
            $annotation = $this->createAnnotationObject($key);
            if ($property = $this->getDefaultPropertyName($annotation)) {
                $annotation->{$property} = $value;
            }

            $annotations[] = $annotation;
        } elseif (self::isAssocArray($value)) {
            $annotation = $this->createAnnotationObject($key);
            foreach ($value as $property => $val) {
                $annotation->{$property} = $val;
            }

            $annotations[] = $annotation;
        } elseif ('groups' === $key) {
            $annotation = new Annotations\Groups();
            $annotation->groups = $value;

            $annotations[] = $annotation;
        } else {
            foreach ($value as $annotValue) {
                $annotations = array_merge($annotations, $this->createAnnotationsForArray($annotValue, $key));
            }
        }

        return $annotations;
    }
}
