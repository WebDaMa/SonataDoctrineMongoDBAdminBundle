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

namespace Sonata\DoctrineMongoDBAdminBundle\Tests\Fixtures\Document;

class SimpleDocument
{
    public $schwifty;
    private $schmeckles;
    private $multiWordProperty;
    private $plumbus;

    public function getSchmeckles()
    {
        return $this->schmeckles;
    }

    public function setSchmeckles($value): void
    {
        $this->schmeckles = $value;
    }

    public function getMultiWordProperty()
    {
        return $this->multiWordProperty;
    }

    public function setMultiWordProperty($value): void
    {
        $this->multiWordProperty = $value;
    }
}
