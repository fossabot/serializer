<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Common\Annotations\Reader;
use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Annotation;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\Processor\AnnotationProcessor;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;

class AnnotationLoader implements LoaderInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var AnnotationProcessor
     */
    private $processor;

    public function __construct()
    {
        $this->processor = new AnnotationProcessor();
    }

    /**
     * @param Reader $reader
     */
    public function setReader(Reader $reader): void
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (! $classMetadata instanceof ClassMetadata) {
            throw new \LogicException('wrong metadata class');
        }

        /** @var ClassMetadata $classMetadata */
        $class = $classMetadata->getReflectionClass();

        if ($this->isExcluded($class)) {
            return true;
        }

        $this->processClassAnnotations($classMetadata);

        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $this->processMethodAnnotations($method, $classMetadata);
        }

        foreach ($class->getProperties() as $property) {
            if ($property->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $this->processPropertyAnnotations($property, $classMetadata);
        }

        return true;
    }

    /**
     * @param ClassMetadata $classMetadata
     */
    private function processClassAnnotations(ClassMetadata $classMetadata): void
    {
        $annotations = $this->getClassAnnotations($classMetadata);
        foreach ($annotations as $annotation) {
            $this->processor->process($annotation, $classMetadata);

            if ($annotation instanceof Annotation\AdditionalField) {
                $additionalMetadata = new AdditionalPropertyMetadata($classMetadata->name, $annotation->name);
                $this->loadExposedAttribute($additionalMetadata, $annotation->attributes, $classMetadata);
            } elseif ($annotation instanceof Annotation\StaticField) {
                $staticMetadata = new StaticPropertyMetadata($classMetadata->name, $annotation->name, $annotation->value);
                $this->loadExposedAttribute($staticMetadata, $annotation->attributes, $classMetadata);
            }
        }
    }

    private function processMethodAnnotations(\ReflectionMethod $method, ClassMetadata $classMetadata): void
    {
        $class = $method->class;

        $methodAnnotations = $this->getMethodAnnotations($method);
        foreach ($methodAnnotations as $annotation) {
            if ($annotation instanceof Annotation\VirtualProperty) {
                $virtualPropertyMetadata = new VirtualPropertyMetadata($class, $method->name);
                $this->loadExposedAttribute($virtualPropertyMetadata, $methodAnnotations, $classMetadata);
            }
        }
    }

    private function processPropertyAnnotations(\ReflectionProperty $property, ClassMetadata $classMetadata): void
    {
        $class = $property->class;

        if ($this->isPropertyExcluded($property, $classMetadata)) {
            return;
        }

        $metadata = new PropertyMetadata($class, $property->name);
        $annotations = $this->getPropertyAnnotations($property);
        $this->loadExposedAttribute($metadata, $annotations, $classMetadata);
    }

    private function loadExposedAttribute(PropertyMetadata $metadata, array $annotations, ClassMetadata $classMetadata): void
    {
        $metadata->readOnly = $metadata->readOnly || $classMetadata->readOnly;
        $accessType = $classMetadata->defaultAccessType;

        $accessor = [null, null];

        foreach ($annotations as $annotation) {
            $this->processor->process($annotation, $metadata);

            if ($annotation instanceof Annotation\AccessType) {
                $accessType = $annotation->type;
            } elseif ($annotation instanceof Annotation\Accessor) {
                $accessor = [$annotation->getter, $annotation->setter];
            }
        }

        $metadata->setAccessor($accessType, $accessor[0], $accessor[1]);
        $classMetadata->addAttributeMetadata($metadata);
    }

    protected function isExcluded(\ReflectionClass $class): bool
    {
        return null !== $this->reader->getClassAnnotation($class, Annotation\Exclude::class);
    }

    protected function getClassAnnotations(ClassMetadata $classMetadata): array
    {
        return $this->reader->getClassAnnotations($classMetadata->getReflectionClass());
    }

    protected function getMethodAnnotations(\ReflectionMethod $method): array
    {
        return $this->reader->getMethodAnnotations($method);
    }

    protected function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        return $this->reader->getPropertyAnnotations($property);
    }

    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        if (Annotation\ExclusionPolicy::ALL === $classMetadata->exclusionPolicy) {
            return null === $this->reader->getPropertyAnnotation($property, Annotation\Expose::class);
        }

        return null !== $this->reader->getPropertyAnnotation($property, Annotation\Exclude::class);
    }
}
