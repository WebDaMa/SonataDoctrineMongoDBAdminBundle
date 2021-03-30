<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineMongoDBAdminBundle\Builder;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Builder\DatagridBuilderInterface;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface as DeprecatedTypeGuesserInterface;
use Sonata\DoctrineMongoDBAdminBundle\Datagrid\Pager;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

final class DatagridBuilder implements DatagridBuilderInterface
{
    /**
     * @var FilterFactoryInterface
     */
    private $filterFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * NEXT_MAJOR: Remove DeprecatedTypeGuesserInterface type.
     *
     * @var DeprecatedTypeGuesserInterface|TypeGuesserInterface
     */
    private $guesser;

    /**
     * Indicates that csrf protection enabled.
     *
     * @var bool
     */
    private $csrfTokenEnabled;

    /**
     * NEXT_MAJOR: Remove DeprecatedTypeGuesserInterface type and add TypeGuesserInterface to the constructor.
     *
     * @param DeprecatedTypeGuesserInterface|TypeGuesserInterface $guesser
     * @param bool                                                $csrfTokenEnabled
     */
    public function __construct(FormFactoryInterface $formFactory, FilterFactoryInterface $filterFactory, $guesser, $csrfTokenEnabled = true)
    {
        $this->formFactory = $formFactory;
        $this->filterFactory = $filterFactory;
        $this->guesser = $guesser;
        $this->csrfTokenEnabled = $csrfTokenEnabled;
    }

    public function fixFieldDescription(FieldDescriptionInterface $fieldDescription): void
    {
        if ([] !== $fieldDescription->getFieldMapping()) {
            $fieldDescription->setOption('field_mapping', $fieldDescription->getOption('field_mapping', $fieldDescription->getFieldMapping()));

            if ('string' === $fieldDescription->getFieldMapping()['type']) {
                $fieldDescription->setOption('global_search', $fieldDescription->getOption('global_search', true)); // always search on string field only
            }
        }

        if ([] !== $fieldDescription->getAssociationMapping()) {
            $fieldDescription->setOption('association_mapping', $fieldDescription->getOption('association_mapping', $fieldDescription->getAssociationMapping()));
        }

        if ([] !== $fieldDescription->getParentAssociationMappings()) {
            $fieldDescription->setOption('parent_association_mappings', $fieldDescription->getOption('parent_association_mappings', $fieldDescription->getParentAssociationMappings()));
        }

        $fieldDescription->setOption('name', $fieldDescription->getOption('name', $fieldDescription->getName()));

        if (\in_array($fieldDescription->getMappingType(), [ClassMetadata::ONE, ClassMetadata::MANY], true)) {
            $fieldDescription->getAdmin()->attachAdminClass($fieldDescription);
        }
    }

    public function addFilter(DatagridInterface $datagrid, $type, FieldDescriptionInterface $fieldDescription): void
    {
        if (null === $type) {
            // NEXT_MAJOR: Remove the condition and keep the if part.
            if ($this->guesser instanceof TypeGuesserInterface) {
                $guessType = $this->guesser->guess($fieldDescription);
            } else {
                $guessType = $this->guesser->guessType(
                    $fieldDescription->getAdmin()->getClass(),
                    $fieldDescription->getName(),
                    $fieldDescription->getAdmin()->getModelManager()
                );
            }

            $type = $guessType->getType();

            $fieldDescription->setType($type);

            $options = $guessType->getOptions();

            foreach ($options as $name => $value) {
                if (\is_array($value)) {
                    $fieldDescription->setOption($name, array_merge($value, $fieldDescription->getOption($name, [])));
                } else {
                    $fieldDescription->setOption($name, $fieldDescription->getOption($name, $value));
                }
            }
        } else {
            $fieldDescription->setType($type);
        }

        $this->fixFieldDescription($fieldDescription);
        $fieldDescription->getAdmin()->addFilterFieldDescription($fieldDescription->getName(), $fieldDescription);

        $fieldDescription->mergeOption('field_options', ['required' => false]);
        $filter = $this->filterFactory->create($fieldDescription->getName(), $type, $fieldDescription->getOptions());
        $datagrid->addFilter($filter);
    }

    public function getBaseDatagrid(AdminInterface $admin, array $values = []): DatagridInterface
    {
        $pager = new Pager();

        $defaultOptions = [];
        if ($this->csrfTokenEnabled) {
            $defaultOptions['csrf_protection'] = false;
        }

        $formBuilder = $this->formFactory->createNamedBuilder('filter', FormType::class, [], $defaultOptions);

        return new Datagrid($admin->createQuery(), $admin->getList(), $pager, $formBuilder, $values);
    }
}
