<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Handles traversal along the object graph.
 *
 * This class handles traversal along the graph, and calls different methods
 * on visitors, or custom handlers to process its nodes.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
abstract class GraphNavigator
{
    private const BUILTIN_TYPES = [
        'NULL' => true,
        'string' => true,
        'integer' => true,
        'int' => true,
        'boolean' => true,
        'bool' => true,
        'double' => true,
        'float' => true,
        'array' => true,
        'resource' => true,
    ];

    /**
     * @var EventDispatcherInterface|null
     */
    protected $dispatcher;

    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var HandlerRegistryInterface
     */
    private $handlerRegistry;

    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        HandlerRegistryInterface $handlerRegistry,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->dispatcher = $dispatcher;
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
    }

    /**
     * Called for each node of the graph that is being traversed.
     *
     * @param mixed                                               $data    the data depends on the direction, and type of visitor
     * @param Type|null                                           $type    array has the format ["name" => string, "params" => array]
     * @param SerializationContext|DeserializationContext|Context $context
     *
     * @return mixed the return value depends on the direction, and type of visitor
     */
    abstract public function accept($data, ?Type $type, Context $context);

    protected function callVisitor($data, Type $type, Context $context, ClassMetadata $metadata = null)
    {
        $visitor = $context->visitor;

        // First, try whether a custom handler exists for the given type
        if (null !== $handler = $this->handlerRegistry->getHandler($context->direction, $type->name)) {
            return $visitor->visitCustom($handler, $data, $type, $context);
        }

        switch ($type->name) {
            case 'NULL':
                return $visitor->visitNull($data, $type, $context);

            case 'string':
                return $visitor->visitString($data, $type, $context);

            case 'integer':
            case 'int':
                return $visitor->visitInteger($data, $type, $context);

            case 'boolean':
            case 'bool':
                return $visitor->visitBoolean($data, $type, $context);

            case 'double':
            case 'float':
                return $visitor->visitDouble($data, $type, $context);

            case 'array':
                if (\method_exists($visitor, 'visitHash')) {
                    if (1 === $type->countParams()) {
                        return $visitor->visitArray($data, $type, $context);
                    }

                    return $visitor->visitHash($data, $type, $context);
                }

                return $visitor->visitArray($data, $type, $context);

            case 'resource':
                $msg = 'Resources are not supported in serialized data.';
                throw new RuntimeException($msg);
            default:
                if (null === $metadata) {
                    // Missing handler for custom type
                    return null;
                }

                $exclusionStrategy = $context->getExclusionStrategy();
                if (null !== $exclusionStrategy && $exclusionStrategy->shouldSkipClass($metadata, $context)) {
                    return null;
                }

                return $this->visitObject($metadata, $data, $type, $context);
        }
    }

    /**
     * Get ClassMetadata instance for type. Returns null if class does not exist.
     *
     * @param Type $type
     *
     * @return ClassMetadata|null
     */
    protected function getMetadataForType(Type $type): ?ClassMetadata
    {
        if ($metadata = $type->metadata) {
            return $metadata;
        }

        $name = $type->name;
        if (isset(self::BUILTIN_TYPES[$name]) || (! \class_exists($name) && ! \interface_exists($name))) {
            return null;
        }

        $metadata = $this->metadataFactory->getMetadataFor($name);

        return $type->metadata = $metadata;
    }

    abstract protected function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context);
}
