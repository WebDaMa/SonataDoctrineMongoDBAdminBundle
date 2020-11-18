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

namespace Sonata\DoctrineMongoDBAdminBundle\Filter;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\NumberType;
use Sonata\AdminBundle\Form\Type\Operator\NumberOperatorType;

/**
 * @final since sonata-project/doctrine-mongodb-admin-bundle 3.5.
 */
class NumberFilter extends Filter
{
    private const CHOICES = [
        NumberOperatorType::TYPE_EQUAL => 'equals',
        NumberOperatorType::TYPE_GREATER_EQUAL => 'gte',
        NumberOperatorType::TYPE_GREATER_THAN => 'gt',
        NumberOperatorType::TYPE_LESS_EQUAL => 'lte',
        NumberOperatorType::TYPE_LESS_THAN => 'lt',
    ];

    /**
     * NEXT_MAJOR: Remove $alias parameter.
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value): void
    {
        if (!$value || !\is_array($value) || !\array_key_exists('value', $value) || !is_numeric($value['value'])) {
            return;
        }

        $type = $value['type'] ?? NumberOperatorType::TYPE_EQUAL;

        $operator = $this->getOperator((int) $type);

        $queryBuilder->field($field)->$operator((float) $value['value']);
        $this->active = true;
    }

    public function getDefaultOptions()
    {
        return [];
    }

    public function getRenderSettings()
    {
        return [NumberType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        return self::CHOICES[$type] ?? self::CHOICES[NumberOperatorType::TYPE_EQUAL];
    }
}
