<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class PriceRuleAttributeProvider
{
    const FIELD_TYPE_NATIVE = 'native';
    const FIELD_TYPE_VIRTUAL = 'virtual';

    const SUPPORTED_TYPES = ['integer' => true, 'float' => true, 'money' => true, 'decimal' => true];

    /**
     * @var VirtualFieldProviderInterface
     */
    protected $virtualFieldProvider;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $supportedClasses = [];

    /**
     * @var array
     */
    protected $availableRuleAttributes;

    /**
     * @var array
     */
    protected $availableConditionAttributes;

    /**
     * @param Registry $registry
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     */
    public function __construct(Registry $registry, VirtualFieldProviderInterface $virtualFieldProvider)
    {
        $this->registry = $registry;
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function getAvailableRuleAttributes($className)
    {
        if (!$this->isClassSupported($className)) {
            throw new \Exception('Class does not supported');
        }
        $this->ensureRuleAttributes();

        return $this->availableRuleAttributes[$className];
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function getAvailableConditionAttributes($className)
    {
        if (!$this->isClassSupported($className)) {
            throw new \Exception('Class does not supported');
        }
        $this->ensureConditionAttributes();

        return $this->availableConditionAttributes[$className];
    }

    public function isClassSupported($className)
    {
        return array_key_exists($className, $this->supportedClasses);
    }

    /**
     * @return array|string[]
     */
    public function getSupportedClasses()
    {
        return array_keys($this->supportedClasses);
    }

    /**
     * @param string $class
     */
    public function addSupportedClass($class)
    {
        $this->supportedClasses[$class] = true;
    }

    protected function ensureRuleAttributes()
    {
        if ($this->availableRuleAttributes === null) {
            $this->availableRuleAttributes = [];

            foreach ($this->getSupportedClasses() as $class) {
                $this->availableRuleAttributes[$class] = [];
                $metadata = $this->registry->getManagerForClass($class)->getClassMetadata($class);

                foreach ($metadata->getFieldNames() as $fieldName) {
                    $type = $metadata->getTypeOfField($fieldName);
                    if (!empty(self::SUPPORTED_TYPES[$type])) {
                        $field = ['name' => $fieldName, 'type' => self::FIELD_TYPE_NATIVE];
                        $this->availableRuleAttributes[$class][$fieldName] = $field;
                    }
                }
            }
        }
    }

    protected function ensureConditionAttributes()
    {
        if ($this->availableConditionAttributes === null) {
            $this->availableConditionAttributes = [];

            foreach ($this->getSupportedClasses() as $class) {
                $this->availableConditionAttributes[$class] = [];
                $metadata = $this->registry
                    ->getManagerForClass($class)
                    ->getClassMetadata($class);

                foreach ($metadata->getFieldNames() as $fieldName) {
                    $field = ['name' => $fieldName, 'type' => self::FIELD_TYPE_NATIVE];
                    $this->availableConditionAttributes[$class][$fieldName] = $field;
                }

                $virtualFields = $this->virtualFieldProvider->getVirtualFields($class);
                foreach ($virtualFields as $fieldName2) {
                    $field = ['name' => $fieldName2, 'type' => self::FIELD_TYPE_VIRTUAL];
                    $this->availableConditionAttributes[$class][$fieldName2] = $field;
                }
            }
        }
    }
}
