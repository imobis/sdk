# IMOBIS SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imobis/sdk.svg?style=flat-square)](https://packagist.org/packages/imobis/api-sdk)
[![Tests](https://img.shields.io/github/actions/workflow/status/imobis/sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/imobis/sdk/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/imobis/sdk.svg?style=flat-square)](https://packagist.org/packages/imobis/api-sdk)

Библиотека для отправки сообщений через сервисы imobis.ru

## Поддерживаемые версии PHP

Эта библиотека поддерживает следующие реализации PHP:

 * PHP 7.4
 * PHP 8.0
 * PHP 8.1
 * PHP 8.2
 * PHP 8.3
 * PHP 8.4

## Требования

 * ext-json: *
 * guzzlehttp: >= 7.0
 * monolog: >= 2.10 || >= 3.7

## Установка

Вы можете установить пакет с помощью composer:

```bash
composer require imobis/api-sdk
```

## Использование

Режим песочницы активируется с своим токеном перед созданием клиента:
```php
$sandboxApiKey = '...';
$sandbox = Nexus\Message\Sdk\Client::enableSandbox($sandboxApiKey);
```

Проверка активности песочницы:
```php
$activated = $sandbox->active();
```

Отключение песочницы:
```php
Nexus\Message\Sdk\Client::disableSandbox();
```

Создаем клиент с указанием ключа доступа:
```php
use Nexus\Message\Sdk\Exceptions\CollectionException;
use Nexus\Message\Sdk\Exceptions\ConnectionException;
use Nexus\Message\Sdk\Exceptions\HttpInvalidArgumentException;
use Nexus\Message\Sdk\Exceptions\LowBalanceException;
use Nexus\Message\Sdk\Exceptions\TokenException;
use Nexus\Message\Sdk\Exceptions\ViolationIntegrityEntityException;

try {
    $apiKey = '...';
    $client = new Nexus\Message\Sdk\Client($apiKey);
    $login = $client->getLogin();
} catch (CollectionException $exception) {
    echo 'CollectionException: ' . $exception->getMessage();
} catch (ConnectionException $exception) {
    echo 'ConnectionException: ' . $exception->getMessage();
} catch (HttpInvalidArgumentException $exception) {
    echo 'HttpInvalidArgumentException: ' . $exception->getMessage();
} catch (TokenException $exception) {
    echo 'TokenException: ' . $exception->getMessage();
} catch (LowBalanceException $exception) {
    echo 'LowBalanceException: ' . $exception->getMessage();
} catch (ViolationIntegrityEntityException $exception) {
    echo 'ViolationIntegrityEntityException: ' . $exception->getMessage();
} catch (\InvalidArgumentException $exception) {
    echo 'InvalidArgumentException: ' . $exception->getMessage();
} catch (\Exception $exception) {
    echo 'Exception: ' . $exception->getMessage();
}
```

Проверяем баланс:
```php
$balance = $client->getBalance();
```

Проверка номеров:
```php
$collection = $client->checkPhones(['79991112233', '...']);

if ($collection->count() > 0) {
    foreach ($collection as $phone) {
        $checked = $phone->checked();
        $valid = $phone->valid();
        $phoneNumber = $phone->getNumber();
        $country = $phone->getCountry();
        $operator = $phone->getOperator();
        $regionId = $phone->getRegionId();
        $regionName = $phone->getRegionName();
        $timezone = $phone->getTimezone();
    }
}
```

Список имен отправителей:
```php
$collection = $client->getSenders();

if ($collection->count() > 0) {
    foreach ($collection as $sender) {
        $checked = $sender->checked();
        $senderName = $sender->getSender();
        $channel = $sender->getChannel();
    }
}
```

### Отправка сообщений:

Перед отправкой сообщений необходимо создать метаданные для работы со статусами:
```php
$reportUrl = 'https://example.com/report'; // На этот URL будут приходить статусы сообщений и ошибки отправки
$replyUrl = 'https://example.com/reply'; // На этот URL будут приходить ответы на сообщения, если канал поддерживает данных функционал
$ttl = 600;
$metadata = Nexus\Message\Sdk\ValueObject\MessageMetadata::create($reportUrl, $replyUrl, $ttl);
```

Отправка кодов подтверждения в Телеграм:
```php
$client->sendTelegram(['79991112233', '...'], 'Текст сообщения', $metadata);
```

Отправка по каналу Вконтакте:
```php
$client->sendVk(['79991112233', '...'], 'Текст сообщения', $metadata);
```

Отправка по каналу Вайбер:
```php
$client->sendViber(['79991112233', '...'], 'Текст сообщения', $metadata);
```

Отправка по каналу СМС:
```php
$client->sendSms(['79991112233', '...'], 'Текст сообщения', $metadata);
```

Отправка каналами по умолчанию:
```php
$client->sendSimple(['79991112233', '...'], 'Текст сообщения', $metadata);
```

Гибридная отправка:
```php
$channels = ['telegram', 'vk', 'sms']; // Порядок каналов для отправки
$client->sendHybrid(['79991112233', '...'], 'Текст сообщения', $channels, $metadata);
```

### Обработка статусов, ответов и ошибок:

Обработка статусов и ошибок:
```php
$post = file_get_contents('php://input');
$handler = Nexus\Message\Sdk\Client::statusHandler($post);
$error = $handler->getError();
$messageId = $handler->getEntityId();
$status = $handler->getStatus();
$channel = $handler->getChannel();
```

Обработка ответов:
```php
$post = file_get_contents('php://input');
$handler = Nexus\Message\Sdk\Client::replyHandler($post);
$messageId = $handler->getMessageId();
$customId = $handler->getCustomId();
$text = $handler->getText();
$date = $handler->getDate();
```

### Работа с шаблонами:

Получить список шаблонов:
```php
$collection = $client->getTemplates();

if ($collection->count() > 0) {
    foreach ($collection as $template) {
        $id = $template->getId();
        $name = $template->getName();
        $text = $template->getText();
        $channels = $template->getChannels();
        $status = $template->getStatus();
        $comment = $template->getComment();
        $activated = $template->getActive();
        $variables = $template->getVariables();
    }
}
```

Создание шаблона:
```php
$template = new Nexus\Message\Sdk\Entity\Template();
$text = 'Текст шаблона';
if ($template->checkText($text)) {
    $template->setName('Название шаблона')
        ->setText($text)
        ->setChannel(Nexus\Message\Sdk\Entity\Channel::SMS)
        ->setGroupUrl('https://vk.com/...') // Для шаблонов Вконтакте
        ->setComment('Комментарий');
    $client->createTemplate($template);
}
```

Переменные шаблона:
```php
$template->addNumericVariable('age');
$template->addNumericSetVariable('year');
$template->addWordVariable('name');
$template->addWordSetVariable('address');
$template->resetVariables();
```

Изменение шаблона:
```php
$template->setName('Новое название шаблона')
    ->setChannel(Nexus\Message\Sdk\Entity\Channel::VK);
$client->updateTemplate($template);
```

Удаление шаблона:
```php
$client->deleteTemplate($template);
```

## Тестирование

```bash
phpunit --coverage-text
```

## История изменений

Смотрите [CHANGELOG](CHANGELOG.md) для получения дополнительной информации о том, что изменилось за последнее время.
