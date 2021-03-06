<?php

namespace MNC\DoctrineCarbonBundle\Doctrine;

use Carbon\Carbon;
use DateTime;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints\Date;

class CarbonPropertyListener implements EventSubscriber
{
    /**
     * @var Carbon
     */
    private $carbon;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $exludedClasses;

    /**
     * CarbonDateListener constructor.
     * @param Carbon $carbon
     * @param array|null $properties
     */
    public function __construct(
        Carbon $carbon,
        array $properties,
        array $excludedClasses
    )
    {
        $this->carbon = $carbon;
        $this->properties = $properties;
        $this->exludedClasses = $excludedClasses;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return ['postLoad'];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity =  $args->getEntity();
        if ($this->classIsExcluded(get_class($entity))) {
            return;
        }
        $this->setCarbonInstances($entity);
    }

    /**
     * Checks if the current class is excluded of the converting process.
     * @param \ReflectionClass $classMeta
     * @return bool
     */
    public function classIsExcluded($className)
    {
        if ($this->exludedClasses !== null ) {
            return in_array($className, $this->exludedClasses);
        }
        return false;
    }

    /**
     * Sets the Carbon Instances over the DateTime ones for each indicated property.
     * @param $entity
     */
    public function setCarbonInstances($entity)
    {
        $pa = PropertyAccess::createPropertyAccessor();
        foreach ($this->properties as $property) {
            if ($pa->isReadable($entity, $property) && $pa->isWritable($entity, $property)) {
                if ($pa->getValue($entity, $property) instanceof DateTime && $pa->getValue($entity, $property) !== null) {
                    $pa->setValue($entity, $property, $this->carbon::instance($pa->getValue($entity, $property)));
                }
            }
        }
    }
}