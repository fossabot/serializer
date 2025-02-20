<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Kcs\Serializer\Construction\DoctrineObjectConstructor;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DoctrineObjectConstructorTest extends TestCase
{
    public function testConstructorUseFallbackIfNoManagerMatch(): void
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();
        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn();

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), [], new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, [], Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    public function testConstructorUseFallbackIfObjectIsTransient(): void
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();

        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn($objectManager = $this->prophesize(ObjectManager::class));

        $objectManager->getMetadataFactory()->willReturn($metadataFactory = $this->prophesize(ClassMetadataFactory::class));
        $metadataFactory->isTransient('EntityObject')->willReturn(true);

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), [], new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, [], Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    public function testConstructorUseFallbackIfFindReturnsNull(): void
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();

        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn($objectManager = $this->prophesize(ObjectManager::class));

        $objectManager->find('EntityObject', 4)->willReturn();
        $objectManager->getMetadataFactory()->willReturn($metadataFactory = $this->prophesize(ClassMetadataFactory::class));
        $metadataFactory->isTransient('EntityObject')->willReturn(false);

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), 4, new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, 4, Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    public function testConstructorUseFallbackIfDataDoesNotContainsIdentifier(): void
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();

        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn($objectManager = $this->prophesize(ObjectManager::class));

        $objectManager->find(Argument::cetera())->shouldNotBeCalled();
        $objectManager->getMetadataFactory()->willReturn($metadataFactory = $this->prophesize(ClassMetadataFactory::class));
        $metadataFactory->isTransient('EntityObject')->willReturn(false);

        $objectManager->getClassMetadata('EntityObject')
            ->willReturn($classMetadata = $this->prophesize(\Doctrine\Common\Persistence\Mapping\ClassMetadata::class));
        $classMetadata->getIdentifierFieldNames()->willReturn(['id']);

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), ['field' => 'text'], new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, ['field' => 'text'], Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }
}
