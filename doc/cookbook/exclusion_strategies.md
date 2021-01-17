Exclusion Strategies
====================

Introduction
------------
The serializer supports different exclusion strategies. Each strategy allows
you to define which properties of your objects should be serialized.

General Exclusion Strategies
----------------------------
If you would like to always expose, or exclude certain properties. Then, you can
do this with ExclusionPolicy, Exclude and Expose.

The default exclusion policy is to exclude nothing. That is, all properties of the
object will be serialized. If you only want to expose a few of the properties,
then it is easier to change the exclusion policy, and only mark these few properties:

```php
use Kcs\Serializer\Annotation\ExclusionPolicy;
use Kcs\Serializer\Annotation\Expose;

/**
 * The following annotations tells the serializer to skip all properties which
 * have not marked with Expose.
 */
#[ExclusionPolicy(ExclusionPolicy::ALL)]
class MyObject
{
    private $foo;
    private $bar;

    #[Expose()]
    private $name;
}
```

> A property that is excluded by `Exclude` cannot be exposed anymore by any of the following strategies, but is always hidden.

Versioning Objects
------------------

The serializer comes by default with a feature which allows you to add versioning support to your objects.

```php
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Since;
use Kcs\Serializer\Annotation\Until;

class VersionedObject
{
    #[Until('1.0.x')]
    private $name;

    #[Since('1.1')]
    #[SerializedName('name')]
    private $name2;
}
```

> `Until` and `Since` both accepts a version number accepted by PHP's `version_compare` function 

You can set the serialization version into the context:

```php
use Kcs\Serializer\SerializationContext;

$serializer->serialize(new VersionObject(), 'json', SerializationContext::create()->setVersion(1));
```


Creating Different Views of Your Objects
----------------------------------------
Another default exclusion strategy is to create different views of your objects.

Let's say you would like to serialize your object in a different view depending on the request: the 
user can request a list of items or the details of a single item.

You can achieve that by using the `Groups` attribute on your properties.

```php
use Kcs\Serializer\Annotation\Groups;

class BlogPost
{
    #[Groups(['list', 'details'])]
    private $id;

    #[Groups(['list', 'details'])]
    private $title;

    #[Groups(['list'])]
    private $nbComments;

    #[Groups(['details'])]
    private $comments;

    // You can also exclude a property if a serialization group is defined.
    #[Groups(['!details'])]
    private $createdAt;
}
```

You can then tell the serializer which groups to serialize in your controller::

```php
use Kcs\Serializer\SerializationContext;

// will output $id, $title and $nbComments.
$serializer->serialize(new BlogPost(), 'json', SerializationContext::create()->setGroups(['list']));

// will output $id, $title, $nbComments and $createdAt.
$serializer->serialize(new BlogPost(), 'json', SerializationContext::create()->setGroups(['Default', 'list']));

// will output $id, $title, $comments
$serializer->serialize(new BlogPost(), 'json', SerializationContext::create()->setGroups(['details']);
```

Overriding Groups of Deeper Branches of the Graph
-------------------------------------------------
In some cases you want to control more precisely what is serialized because you may have the same class at different
depths of the object graph.

For example if you have a User that has a manager and friends:

```php
use Kcs\Serializer\Annotation\Groups;

class User
{
    private $name;

    #[Groups(['manager_group'])]
    private $manager;

    #[Groups(['friends_group'])]
    private $friends;

    public function __construct($name, User $manager = null, array $friends = null)
    {
        $this->name = $name;
        $this->manager = $manager;
        $this->friends = $friends;
    }
}
```

And the following object graph:

```php
$john = new User(
    'John',
    new User(
        'John Manager',
        new User('The boss'),
        [
            new User('John Manager friend 1'),
        ]
    ),
    [
        new User(
            'John friend 1',
            new User('John friend 1 manager')
        ),
        new User(
            'John friend 2',
            new User('John friend 2 manager')
        ),
    ]
);
```

You can override groups on specific paths:

```php
use Kcs\Serializer\SerializationContext;

$context = SerializationContext::create()->setGroups([
    'Default', // Serialize John's name
    'manager_group', // Serialize John's manager
    'friends_group', // Serialize John's friends

    'manager' => [ // Override the groups for the manager of John
        'Default', // Serialize John manager's name
        'friends_group', // Serialize John manager's friends. If you do not override the groups for the friends, it will default to Default.
    ],

    'friends' => [ // Override the groups for the friends of John
        'manager_group', // Serialize John friends' managers.

        'manager' => [ // Override the groups for the John friends' manager
            'Default', // This would be the default if you did not override the groups of the manager property.
        ],
    ],
]);

$serializer->serialize($john, 'json', $context);
```

This would result in the following json::

```json
{
    "name": "John",
    "manager": {
        "name": "John Manager",
        "friends": [
            {
                "name": "John Manager friend 1"
            }
        ]
    },
    "friends": [
        {
            "manager": {
                "name": "John friend 1 manager"
            }
        },
        {
            "manager": {
                "name": "John friend 2 manager"
            }
        }
    ]
}
```

Change exclusion behavior
-------------------------
By default, if a property is excluded by groups or version rules, it will be skipped and will not be present
on the serialized output.

In some cases it is preferred to include the property but set it to `null`.

In these cases you can add the `OnExclude` attribute on the property setting the preferred exclusion behavior.

```php
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\OnExclude;

class MyObject
{
    public $name;
    public $surname;
    
    #[Groups(['admin_only'])]
    #[OnExclude(OnExclude::NULL)]
    public $email;
}
```

Limiting serialization depth of some properties
-----------------------------------------------
You can limit the depth of what will be serialized in a property with the `MaxDepth` attribute.

This exclusion strategy is a bit different from the others, because it will
affect the serialized content of others classes than the one you apply the
annotation to.

```php
use Kcs\Serializer\Annotation\MaxDepth;

class User
{
    private $username;

    #[MaxDepth(1)]
    private $friends;

    #[MaxDepth(2)]
    private $posts;
}

class Post
{
    private $title;

    private $author;
}
```

In this example, serializing a user, because the max depth of the `$friends`
property is 1, the user friends would be serialized, but not their friends;
and because the max depth of the `$posts` property is 2, the posts would
be serialized, and their author would also be serialized.

Max depth checks are not enabled by default, you need to enable them explicitly:

```php
use Kcs\Serializer\SerializationContext;

$serializer->serialize($data, 'json', SerializationContext::create()->enableMaxDepthChecks());
```
