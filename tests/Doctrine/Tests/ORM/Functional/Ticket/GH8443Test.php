<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

final class GH8443Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->createSchemaForModels(GH8443Foo::class);
    }

    #[Group('GH-8443')]
    public function testJoinRootEntityWithOnlyOneEntityInHierarchy(): void
    {
        $bar = new GH8443Foo('bar');

        $foo = new GH8443Foo('foo');
        $foo->setBar($bar);

        $this->_em->persist($bar);
        $this->_em->persist($foo);
        $this->_em->flush();
        $this->_em->clear();

        $foo = $this->_em->createQuery(
            'SELECT f from ' . GH8443Foo::class . " f JOIN f.bar b WITH b.name = 'bar'",
        )->getSingleResult();
        assert($foo instanceof GH8443Foo);

        $bar = $foo->getBar();
        assert($bar !== null);
        $this->assertEquals('bar', $bar->getName());
    }
}
#[Table(name: 'GH2947_foo')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['foo' => 'GH8443Foo'])]
class GH8443Foo
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int|null $id = null;

    /** @var GH8443Foo|null */
    #[OneToOne(targetEntity: 'GH8443Foo')]
    #[JoinColumn(name: 'bar_id', referencedColumnName: 'id')]
    private $bar;

    public function __construct(
        #[Column]
        private string $name,
    ) {
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setBar(GH8443Foo $bar): void
    {
        if ($bar !== $this->bar) {
            $this->bar      = $bar;
            $this->bar->bar = $this;
        }
    }

    public function getBar(): GH8443Foo|null
    {
        return $this->bar;
    }
}
