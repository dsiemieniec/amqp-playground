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
### Instalacja
```shell
composer require symfony/messenger symfony/amqp-messenger
```
### Wartość w pliku .env
```
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
```
### Definicja wiadomości jako klasy
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

namespace App\Message;

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
## Linki
- [Artykuł na oficjalnym blogu RabbitMQ o pluginie "delayed message exchange"](https://blog.rabbitmq.com/posts/2015/04/scheduling-messages-with-rabbitmq)
- [Dokumentacja RabbitMQ](https://www.rabbitmq.com/documentation.html)
- [php-amqplib](https://github.com/php-amqplib/php-amqplib)
- [Dodatkowe informacje o symfony messenger](https://symfony.com/doc/current/components/messenger.html)