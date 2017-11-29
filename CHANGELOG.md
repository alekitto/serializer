This document details changes between individual versions.

For instructions on how to upgrade from one version to another, please see the dedicated UPGRADING document.

2.0 (???)
---------
- [BC Break] Changed namespace to Kcs\Serializer
- [BC Break] Replaced metadata library. Now using `kcs/metadata` package
- [BC Break] Removed class metadata stack from `GraphNavigator`
- [BC Break] Made natural serialization order explicit (HHVM and PHP7 compatibility)
- [BC Break] Default access type is now `public_method`
- [BC Break] Removed custom event dispatcher. Use `symfony/event-dispatcher` component if available
- [BC Break] Serialization direction constants are now in `Kcs\Serializer\Direction` class
- [BC Break] Type is no more an array. It has been replaced by `Type` class
- [BC Break] Removed HandlerCallbacks. Can be replaced by an handler class.
- [BC Break] Metadata loading has been uniformed between Annotations, XML and YAML. As a result
  some xml and yaml mapping elements have been changed.
- [BC Break] `GenericSerializationVisitor::addData` cannot be publicly accessed. Use
  `@AdditionalField` annotation to replace it if called in a POST_SERIALIZE event
- [BC Break] Removed Twig extension
- [BC Break] Extracted MetadataStack and removed helper method from Context
- Added `InitializedObjectConstructor`: uses `target` attribute for root object deserialization
- Refactored `GraphNavigator`
- Serialize timestamp (DateTime<'U'>) as integer
- Ensure array as list if declared as `array<T>` and as map if `array<K,T>`
- Use `symfony/yaml` for YAML dumping/parsing
- Getter/Setter existence check is now made if requested ONLY
- Removed phpcollection dependency
- Allow removal/replace of version and groups in Context
- Type parser is now using doctrine parser lib
- Use `doctrine/instantiator` lib to construct object
- XML collection entries could be namespaced
- PSR-4 compliancy
- Added exclusion serialization groups support: any group preceded by a `!`
  will exclude the property if set.
- Added serialization groups to discriminator field
- Use all doctrine registry managers to retrieve an instance of an object to
  deserialize data into
- Added nested groups support
- Added symfony bundle (`Kcs\Serializer\Bundle\SerializerBundle`)
- Added `int` and `bool` shortcut for integer and boolean types
- Added `OnExclude` attribute. You can now choose to skip the excluded property or to expose it as null (default)
- `Context`s are now cloneable
