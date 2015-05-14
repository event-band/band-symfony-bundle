EventBandSymfonyBundle
======================

Symfony2 Bundle for EventBand framework

# Quick start
## Adding event-band to a symfony2 project
Add the following lines to your composer.json
```json
"event-band/symfony-bundle": "dev-master",
"event-band/amqplib-transport": "dev-master",
"event-band/jms-serializer": "dev-master"
```

then run the `composer update` command.

## Simple configuration
### Creating event and listener
Create an event, extending the EventBand\Adapter\Symfony\SerializableSymfonyEvent
```php
<?php
namespace Acme\EventBundle\Event;
use EventBand\Adapter\Symfony\SerializableSymfonyEvent;

class EchoEvent extends SerializableSymfonyEvent
{
    /** @var string **/
    protected $message;

    public function __construct($message){
        $this->message = $message;
    }

    public funciton getMessage(){
        return $this->message;
    }
}
```
Then create a listener
```php
<?php
namespace Acme\EventBundle\Event;

class EchoEventListener
{
    onEchoEvent(EchoEvent $event){
        // don't do such things on production
        echo $event->getMessage();
        echo "\n";
    }
}
```
And register listener in services.xml
```xml
<service id="acme.event_bundle.event.event_listener" class="Acme\EventBundle\Event">
     <tag name="kernel.event_listener" event="event.echo" method="onEchoEvent"/>
</service>
```
### Configuring bands
Add the following lines to your config.yml
```yml
event_band:
     publishers:
         acme.echo.event.publisher:
             events: ["event.echo"]
             transport:
                 amqp:
                     exchange: acme.echo.event.exchange
     consumers:
         acme.echo.event: ~
```
### Adding band information to listener to make it asynchronous
Add parameter "band" with name of consumer to event listener tag to show which consumer it belongs to.
```xml
<service id="acme.event_bundle.event.event_listener" class="Acme\EventBundle\Event">
     <tag name="kernel.event_listener" event="event.echo" method="onEchoEvent" band="acme.echo.event"/>
</service>
```
### Creating AMQP config
This step is not required, but it's very useful to have this config.
Under the "event_band" space add the following lines to your config.yml
```yml
    transports:
         amqp:
             driver: amqplib
             connections:
                 default:
                     exchanges:
                         acme.echo.event.exchange: ~
                     queues:
                         acme.echo.event:
                             bind:
                                 acme.echo.event.exchange: ['event.echo']
```
Now you can call `app/console event-band:setup amqp:default` command and all the exchanges, queues and bindings will be
automatically created/altered.
### Using asynchronous event
Somewhere in your code use event dispatcher to dispatch the EchoEvent as you usually do.
```php
$dispatcher = $this->getContainer->get('event_dispatcher');
$dispatcher->dispatch('event.echo', new EchoEvent('Hi, guys!'));
```
When you run this code, event will be pushed to the acme.echo.event queue.
Now run console command "event-band:dispatch" with the name of your consumer - "acme.echo.event".
```bash
sh$ app/cosole event-band:dispatch acme.echo.event
Hi, guys!
```
...
Profit.