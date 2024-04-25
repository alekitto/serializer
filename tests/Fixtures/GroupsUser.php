<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Groups;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class GroupsUser
{
    private $name;

    #[Groups(['nickname_group'])]
    private $nickname = 'nickname';

    #[Groups(['manager_group'])]
    private $manager;

    #[Groups(['friends_group'])]
    private $friends;

    public function __construct($name, self $manager = null, array $friends = [])
    {
        $this->name = $name;
        $this->manager = $manager;
        $this->friends = $friends;
    }
}
