<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;

/**
 * @AccessType("property")
 */
class GroupsUser
{
    private $name;

    /**
     * @Groups({"nickname_group"})
     */
    private $nickname = 'nickname';

    /**
     * @Groups({"manager_group"})
     */
    private $manager;

    /**
     * @Groups({"friends_group"})
     */
    private $friends;

    public function __construct($name, self $manager = null, array $friends = [])
    {
        $this->name = $name;
        $this->manager = $manager;
        $this->friends = $friends;
    }
}
