@TODO 

- fix issue with reading annotations from src
- change namespace to configuration instead of parameter
- aggregate root as a service
- allow simple types for aggregate commands/queries
- write tests for bus routers
- check possible to debug with endpoint and channel names
- allow to intercept part of namespace
- define how event handler subscribing should work in context of classes and channel names
- presend interceptor. So it can be called before sending to channel
- amqp add possibility to define point to point or publish subscribe amqp backend channel
- possibility to order event handler
- gateway and endpoint same parameter converters? Check how it's handled in java
- should throw exception if class not found