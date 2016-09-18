From 1.0 to 2.0
================

- You need to change all the references to the new namespace `Kcs\Serializer`
- Metadata library has been replaced. Please refer to the `kcs/metadata` library doc
  to know how to use it or extend it to your needs.
- `GraphNavigator`, `SerializationVisitor` and `Context classes and subclasses have`
  been refactored in deep. Most notable changes are:
  - Introduction of `Type` classes that replaces type arrays
  - `Context::getNonSkippedProperties` method can be used to avoid exclusion strategy
    checks in the visitor classes
  - `DIRECTION_SERIALIZATION` and `DIRECTION_DESERIALIZATION` are now in the
    `Kcs\Serializer\Direction` class
- Symfony EventDispatcher component is used, replacing the custom one.
  All the events object are now subclasses of symfony Event class
- Type parsing now uses `doctrine/lexer` library.
  Please refer to its documentation if needed.
- The default access type is now public method. This means that objects needs
  getters and setters for properties to be accessed. You can restore the property
  access type (access values through reflection) setting the `@AccessType` annotation
  where needed.
- `@HandlerCallback`s have been removed. Can be easily replaced by an handler class
- `pushPropertyMetadata` and `popPropertyMetadata` methods have been removed from
  `Context` class. Use `$context->getMetadataStack()->push()` (or `pop`) method instead
