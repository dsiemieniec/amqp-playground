# Publikowanie wiadomości z wykorzystaniem pluginu "Delayed message exchange"

## RabbitMQ Management UI
### Konfiguracja exchange
1. Po zalogowaniu do panelu administracyjnego przechodzimy do zakładki "Exchanges"
2. Rozwijamy sekcję "Add a new exchange"
3. Ustawiamy następujące parametry
   1. Name: jako nazwę naszego exchange wpisujemy "delayed_exchange".
   2. Type: z listy rozwijanej wybieramy wartość "x-delayed-message".  
   3. W sekcji "Arguments" konfigurujemy paramert "x-delayed-type", która określa w jaki sposób exchange opublikuje wiadomość po upływie czasu opóźnienia. Możliwe wartości to direct, fanout i topic. W naszym przykładzie wybieramy typ direct.
   4. Klikamy przycisk "Add exchange" i nasz exchange został zdefiniowany.

[<img src="img/declare_exchange.png" width="700"/>](img/declare_exchange.png)

### Konfiguracja kolejki
1. Przechodzimy do sekcji "Queues".
2. Rozwijamy sekcję "Add a new queue" i definiujemy kolejkę o nazwie "target_queue".

[<img src="img/declare_queue.png" width="700"/>](img/declare_queue.png)

### Powiązanie exchange i kolejki
1. Wracamy do sekcji "Exchanges".
2. Na liście znajdujemy wcześniej zdefiniowany exchange o nazwie "delayed_exchange" i przechodzimy do jego kofiguracji.
3. W sekcji "Bindings" (którą należy rozwinąć jeśli jest domyślnie zwinięta) konfigurujemy powiązanie między exchange i kolejką
   1. Z listy rozwijanej wybieramy wartość "To queue" i wpisujemy nazwę wcześniej zadeklarowanej kolejki, czyli "target_queue"
   2. W polu "Routing key" wpisujemy wartość "target_queue". Wartość ta nie musi w żaden sposób zawierać w sobie nazwy kolejki. W związku z tym że w trakcie deklaracji exchange ustawiliśmy wartość argumentu "x-delayed-type" na direct to nasz exchange będzie wybierał kolejkę do której przekaże wiadomość po upływie czasu opóźnienia na podstawie wartości "Routing key".
   3. Klikamy "Bind" i nasze połączenia zostało zdefiniowane.

[<img src="img/exchange_queue_binding.png" width="700"/>](img/exchange_queue_binding.png)

### Publikowanie wiadomości
1. Pozostajemy w konfiguracji naszego exchange i przechodzi do sekcji "Publish message".
2. Wpisujemy następujące wartości
   1. W sekcji routing key podajemy wartość ustawioną podczas tworzenia połączenia między kolejką i exchange. W naszym przypadku jest to wartość "target_queue".
   2. W sekcji headers dodajemy nagłówek "x-delay", który umożliwia nam zdefiniowanie czasu opóźnienia z jakim wiadomość zostanie przekazana na docelową kolejkę. Wartość opóźnienia podajemy w milisekundach. W naszym wypadku chcemy aby wiadomość została przekazana na kolejkę po 10s czyli wpisujemy wartość 10000.
   3. Naszą wiadomość podajemy w sekcji "Payload". W tym przypadku jest to po prostu "Test message".
[<img src="img/publish_message.png" width="700"/>](img/publish_message.png)
   4. Po kliknięciu przycisku "Publish message" pojawi się informacja o tym, że wiadomość została pomyślnie dostarczona do exchange, ale w związku z tym że podaliśmy wartość "x-delay" to nie została ona jeszcze przekazana na docelową kolejkę.
[<img src="img/published_confirmation.png" width="700"/>](img/published_confirmation.png)
   5. W sekcji "Details", która znajduje się pod wykresem przedstawiającym przebieg czasowy liczby wiadomości wchodzący i wychodzących, możemy odczytać ile aktualnie wiadomości oczekuje w exchange (messages delayed).
[<img src="img/details_messages_delayed.png" width="700"/>](img/details_messages_delayed.png)
   6. Na wykresie możemy zauważyć że wiadomość została "zwolniona" z exchange po upływie 10s.
[<img src="img/message_rates.png" width="700"/>](img/message_rates.png)

## php-amqplib
### Instalacja
```shell
composer require php-amqplib/php-amqplib
```
### Utworzenie połączenia z rabbitmq
```php
$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();
```
### Konfiguracja exchange
```php
$channel->exchange_declare(
    'delayed_exchange',
    'x-delayed-message',
    durable: true,
    auto_delete: false,
    arguments: new AMQPTable([
        'x-delayed-type' => 'direct'
    ])
);
```
### Konfiguracja kolejki
```php
$channel->queue_declare('target_queue', false, true, false, false);
```
### Powiązanie exchange i kolejki
```php
$channel->queue_bind('target_queue', 'delayed_exchange', 'target_queue');
```
### Publikowanie wiadomości
```php
$headers = ['x-delay' => 10000];
$msg = new AMQPMessage('Hello World!', ['application_headers' => new AMQPTable($headers)]);
$channel->basic_publish($msg, 'delayed_exchange', 'target_queue');
```
### Zamknięcie połączenia
```php
$channel->close();
$connection->close();
```
## Symfony Messenger

Teraz przyjrzymy się w jaki sposób należy skonfigurować symfony messenger, aby wykorzystać funkcjonalność pluginu Delayed message exchange.

### Instalacja symfony messenger
```shell
composer require symfony/messenger symfony/amqp-messenger
```
### Uzupełnie wartość w pliku .env
```
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
```
### Definicja wiadomości jako klasy SimpleMessage
```php
<?php

namespace App\Message;

class SimpleMessage
{
    public function __construct(
        private string $message
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
```
### Definicja "handlera" dla wiadomości
```php
<?php

namespace App\Handler;

use App\Message\SimpleMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SimpleMessageHandler
{
    public function __invoke(SimpleMessage $message): void
    {
        echo $message->getMessage() . PHP_EOL;
    }
}
```
### Konfiguracja w pliku messenger.yaml
```yaml
framework:
  messenger:
    transports:
      async:
        dsn: "%env(MESSENGER_TRANSPORT_DSN)%"
          options:
            exchange:
              name: messenger_delayed_exchange
              type: x-delayed-message
              arguments:
                x-delayed-type: direct
            queues:
              messenger_target_queue:
                binding_keys: [target_queue]
    
    routing:
      'App\Message\SimpleMessage': async
```
### Publikowanie wiadomości
```php
/** @var Symfony\Component\Messenger\MessageBusInterface $messageBus */
$messageBus->dispatch(
    new SimpleMessage('Hello'),
    [
        new AmqpStamp(
            routingKey: 'target_queue',
            attributes: [
                'headers' => [
                    'x-delay' => 10000
                ]
            ]
        )
    ]
);
```

## Amqp Message Bus

W ostatniej części tego artykułu chciałbym przedstawić w jaki sposób można wykorzystać paczkę amqp message bus, której jestem autorem, we współpracy z rozszerzeniem delayed message exchange. Kod źródłowy oraz więcej informacji na temat zaimplementowanych rozwiązań w amqp mmessage bus znajduje się w repozytorium paczki https://github.com/dsiemieniec/amqp-message-bus.

### Instalacja
1. Dodanie repozytorium w composer.json
```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/dsiemieniec/amqp-message-bus" }
]
```
2. Komenda instalacyjna
```shell
composer require dsiemieniec/amqp-message-bus
```
3. Wartości w .env
```
###> dsiemieniec/amqp-message-bus ###
RABBIT_CONNECTION=rabbitmq
RABBIT_PORT=5672
RABBIT_USER=guest
RABBIT_PASSWORD=guest
###< dsiemieniec/amqp-message-bus ###
```
4. Plik `config/packages/amqp_message_bus.yaml` z podstawową konfiguracją
```yaml
amqp_message_bus:
  connections:
    default:
      host: '%env(RABBIT_CONNECTION)%'
      port: '%env(RABBIT_PORT)%'
      user: '%env(RABBIT_USER)%'
      password: '%env(RABBIT_PASSWORD)%'
```
5. Aktywacja bundle

`config/bundles.php`
```php
<?php

return [
    ...
    Siemieniec\AmqpMessageBus\AmqpMessageBus::class => ['all' => true],
];
```
### Definicja wiadomości jako klasy SimpleMessage
```php
<?php

namespace App\Message;

class SimpleMessage
{
    public function __construct(
        private string $message
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
```
Klasa ta niczym nie różni się od tej którą definiowaliśmy w sekcji symfony messenger.

### Definicja "handlera" dla wiadomości
```php
<?php

namespace App\Handler;

use App\Message\SimpleMessage;
use Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler;

#[AsMessageHandler]
class SimpleMessageHandler
{
    public function __invoke(SimpleMessage $message): void
    {
        echo $message->getMessage() . PHP_EOL;
    }
}
```
Jedyna różnica w stosunku do "handlera" definiowanego w sekcji symmfony messenger to zmiana atrybutu na `Siemieniec\AmqpMessageBus\Attributes\AsMessageHandler`.
### Konfiguracja w pliku amqp_message_bus.yaml
```yaml
amqp_message_bus:
  connections:
    default:
      host: '%env(RABBIT_CONNECTION)%'
      port: '%env(RABBIT_PORT)%'
      user: '%env(RABBIT_USER)%'
      password: '%env(RABBIT_PASSWORD)%'
  queues:
    target_queue:
      name: message_bus_target_queue
  exchanges:
    delayed_exchange:
      name: message_bus_delayed_exchange
      type: x-delayed-message
      arguments:
        x-delayed-type: direct
      queue_bindings:
        - { queue: target_queue, routing_key: target_queue }
  messages:
    App\Message\SimpleMessage:
      publisher:
        exchange:
          name: delayed_exchange
          routing_key: target_queue
```
1. W sekcji `connections` definiujemy wymagane konfiguracje domyślnego połączenia.
2. W sekcji `queues` dodajemy minimalną konfigurację kolejki do której będą trafiały wiadomości po upływie zadanego podczas publikacji czasu opóźnienia.
3. W sekcji `exchanges` definiujemy konfigurację naszego exchange.
4. W sekcji `messages` konfigurujemy "publisher" naszych wiadomości tak, aby wszystkie obiekty klasy `App\Message\SimpleMessage` były wysyłane do exchange `message_bus_delayed_exchange` z parametrem `routing_key` równym `target_queue`.

### Deklaracja skonfigurowanego exchange i kolejki w rabbitmq
Auto deklaracja kolejek oraz exchange'y podczas publikowania wiadomości jest domyślnie nieaktywna w konfiguracji amqp message bus, dlatego należy wykonać poniższe polecenie aby je zdefiniować
```shell
bin/console amqp-message-bus:setup-rabbit
```
### Publikowanie wiadomości
```php
$builder = \Siemieniec\AmqpMessageBus\Message\Properties\MessageProperties::builder();
$properties = $builder->addHeader('x-delay', 1000)->build();
/** @var Siemieniec\AmqpMessageBus\Message\MessagePublisherInterface $publisher */
$publisher->publish(new SimpleMessage('Hello'), $properties);
```
Aby opublikować wiadomość z zadanym opóźniem musimy utworzyć obiekt klasy `Siemieniec\AmqpMessageBus\Message\Properties\MessageProperties` i dodać parametr `x-delay` który zostanie wysłany w nagłówku naszej wiadomości. Do utworzenia obiektu klasy `MessageProperties` wykorzystany został builder dostarczony w paczce, który zamyśle ma ułatwiać implementację po stronie aplikacji.

## Zakończenie

Dzięki za poświęcenie Twojego czasu i doczytanie do tego fragmentu. Miłego dnia :)

## Linki
- [Artykuł na oficjalnym blogu RabbitMQ o pluginie "delayed message exchange"](https://blog.rabbitmq.com/posts/2015/04/scheduling-messages-with-rabbitmq)
- [Dokumentacja RabbitMQ](https://www.rabbitmq.com/documentation.html)
- [php-amqplib](https://github.com/php-amqplib/php-amqplib)
- [Dodatkowe informacje o symfony messenger](https://symfony.com/doc/current/components/messenger.html)
- [Repozytorium amqp message bus](https://github.com/dsiemieniec/amqp-message-bus)