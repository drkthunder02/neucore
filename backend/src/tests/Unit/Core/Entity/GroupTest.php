<?php declare(strict_types=1);

namespace Tests\Unit\Core\Entity;

use Brave\Core\Entity\App;
use Brave\Core\Entity\Alliance;
use Brave\Core\Entity\Corporation;
use Brave\Core\Entity\Group;
use Brave\Core\Entity\GroupApplication;
use Brave\Core\Entity\Player;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testJsonSerialize()
    {
        $group = new Group();
        $group->setName('g.name');
        $required1 = (new Group())->setName('reg1');
        $required2 = (new Group())->setName('reg2');
        $group->addRequiredGroup($required1);
        $group->addRequiredBy($required2);
        $required1->addRequiredGroup($required2);
        $required2->addRequiredBy($required1);

        $this->assertSame(
            ['id' => null, 'name' => 'g.name', 'visibility' => Group::VISIBILITY_PRIVATE],
            json_decode(json_encode($group), true)
        );

        $this->assertSame(
            [
                'id' => null,
                'name' => 'g.name',
                'visibility' => Group::VISIBILITY_PRIVATE,
                'requiredGroups' => [
                    ['id' => null, 'name' => 'reg1', 'visibility' => Group::VISIBILITY_PRIVATE]
                ],
                'requiredBy' => [
                    ['id' => null, 'name' => 'reg2', 'visibility' => Group::VISIBILITY_PRIVATE]
                ],
            ],
            json_decode(json_encode($group->jsonSerialize(true)), true)
        );
    }

    public function testGetId()
    {
        $this->assertNull((new Group)->getId());
    }

    public function testSetGetName()
    {
        $group = new Group();
        $group->setName('nam');
        $this->assertSame('nam', $group->getName());
    }

    public function testSetVisibilityException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter must be one of ');

        $group = new Group();
        $group->setVisibility('invalid');
    }

    public function testSetGetVisibility()
    {
        $group = new Group();
        $this->assertsame(Group::VISIBILITY_PRIVATE, $group->getVisibility());
        $group->setVisibility(Group::VISIBILITY_PUBLIC);
        $this->assertsame(Group::VISIBILITY_PUBLIC, $group->getVisibility());
    }

    public function testAddGetRemoveApplicant()
    {
        $group = new Group();
        $a1 = new GroupApplication();
        $a2 = new GroupApplication();

        $this->assertSame([], $group->getApplication());

        $group->addApplication($a1);
        $group->addApplication($a2);
        $this->assertSame([$a1, $a2], $group->getApplication());

        $group->removeApplication($a2);
        $this->assertSame([$a1], $group->getApplication());
    }

    public function testAddGetRemovePlayer()
    {
        $group = new Group();
        $p1 = new Player();
        $p2 = new Player();

        $this->assertSame([], $group->getPlayers());

        $group->addPlayer($p1);
        $group->addPlayer($p2);
        $this->assertSame([$p1, $p2], $group->getPlayers());

        $group->removePlayer($p2);
        $group->removePlayer($p1);
        $this->assertSame([], $group->getPlayers());
    }

    public function testAddGetRemoveManager()
    {
        $group = new Group();
        $p1 = new Player();
        $p2 = new Player();

        $this->assertSame([], $group->getManagers());

        $group->addManager($p1);
        $group->addManager($p2);
        $this->assertSame([$p1, $p2], $group->getManagers());

        $group->removeManager($p2);
        $this->assertSame([$p1], $group->getManagers());
    }

    public function testAddGetRemoveApp()
    {
        $group = new Group();
        $a1 = new App();
        $a2 = new App();

        $this->assertSame([], $group->getApps());

        $group->addApp($a1);
        $group->addApp($a2);
        $this->assertSame([$a1, $a2], $group->getApps());

        $group->removeApp($a2);
        $group->removeApp($a1);
        $this->assertSame([], $group->getApps());
    }

    public function testAddGetRemoveCorporation()
    {
        $group = new Group();
        $c1 = new Corporation();
        $c2 = new Corporation();

        $this->assertSame([], $group->getCorporations());

        $group->addCorporation($c1);
        $group->addCorporation($c2);
        $this->assertSame([$c1, $c2], $group->getCorporations());

        $group->removeCorporation($c2);
        $this->assertSame([$c1], $group->getCorporations());
    }

    public function testAddGetRemoveAlliance()
    {
        $group = new Group();
        $a1 = new Alliance();
        $a2 = new Alliance();

        $this->assertSame([], $group->getAlliances());

        $group->addAlliance($a1);
        $group->addAlliance($a2);
        $this->assertSame([$a1, $a2], $group->getAlliances());

        $group->removeAlliance($a2);
        $this->assertSame([$a1], $group->getAlliances());
    }

    public function testAddGetRemoveRequiredGroups()
    {
        $group = new Group();
        $required1 = new Group();
        $required2 = new Group();

        $this->assertSame([], $group->getRequiredGroups());

        $group->addRequiredGroup($required1);
        $group->addRequiredGroup($required2);
        $this->assertSame([$required1, $required2], $group->getRequiredGroups());

        $group->removeRequiredGroup($required2);
        $this->assertSame([$required1], $group->getRequiredGroups());
    }

    public function testAddGetRemoveRequiredBy()
    {
        $group = new Group();
        $dependent1 = new Group();
        $dependent2 = new Group();

        $this->assertSame([], $group->getRequiredBy());

        $group->addRequiredBy($dependent1);
        $group->addRequiredBy($dependent2);
        $this->assertSame([$dependent1, $dependent2], $group->getRequiredBy());

        $group->removeRequiredBy($dependent2);
        $this->assertSame([$dependent1], $group->getRequiredBy());
    }
}
