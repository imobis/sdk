<?php

namespace Nexus\Message\Sdk;

use Nexus\Message\Sdk\Controllers\BalanceController;
use Nexus\Message\Sdk\Controllers\Controller;
use Nexus\Message\Sdk\Controllers\MessageController;
use Nexus\Message\Sdk\Controllers\PhoneController;
use Nexus\Message\Sdk\Controllers\SenderController;
use Nexus\Message\Sdk\Controllers\TemplateController;
use Nexus\Message\Sdk\Controllers\TokenController;
use Nexus\Message\Sdk\Core\Collections\ChannelRouteCollection;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Core\Collections\HybridRouteCollection;
use Nexus\Message\Sdk\Core\Collections\RouteCollection;
use Nexus\Message\Sdk\Core\Collections\SimpleRouteCollection;
use Nexus\Message\Sdk\Core\Message;
use Nexus\Message\Sdk\Entity\Balance;
use Nexus\Message\Sdk\Entity\Channel;
use Nexus\Message\Sdk\Entity\Error;
use Nexus\Message\Sdk\Entity\Phone;
use Nexus\Message\Sdk\Entity\Reply;
use Nexus\Message\Sdk\Entity\Sandbox;
use Nexus\Message\Sdk\Entity\Sender;
use Nexus\Message\Sdk\Entity\Status;
use Nexus\Message\Sdk\Entity\Telegram;
use Nexus\Message\Sdk\Entity\Template;
use Nexus\Message\Sdk\Entity\Token;
use Nexus\Message\Sdk\Entity\Viber;
use Nexus\Message\Sdk\Entity\Vk;
use Nexus\Message\Sdk\Exceptions\CollectionException;
use Nexus\Message\Sdk\Exceptions\LowBalanceException;
use Nexus\Message\Sdk\Exceptions\TokenException;
use Nexus\Message\Sdk\Exceptions\ViolationIntegrityEntityException;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;
use Nexus\Message\Sdk\Entity\Sms;
use Nexus\Message\Sdk\Core\Collections\MixedRouteCollection;

class Client
{
    private ?string $login = null;
    private Token $token;
    private TokenController $tokenController;
    private BalanceController $balanceController;
    private PhoneController $phoneController;
    private SenderController $senderController;
    private TemplateController $templateController;
    private MessageController $messageController;

    public function __construct(string $apiKey)
    {
        $this->token = new Token($apiKey);
        $currentToken = $this->getToken();
        $this->tokenController = new TokenController($currentToken);
        $this->tokenController->read();

        if ($currentToken->getActive() === false) {
            throw new TokenException($currentToken);
        } else {
            $this->login = $currentToken->getLogin();
        }

        $this->balanceController = new BalanceController($currentToken);
        $this->phoneController = new PhoneController($currentToken);
        $this->senderController = new SenderController($currentToken);
        $this->templateController = new TemplateController($currentToken);
        $this->messageController = new MessageController($currentToken);
    }

    private function getToken(): Token
    {
        $sandbox = Sandbox::getInstance();

        return $sandbox->active() && $sandbox->token() instanceof Token ? $sandbox->token() : $this->token;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setController(Controller $controller): void
    {
        if ($controller instanceof TokenController) {
            $this->tokenController = $controller;
        } elseif ($controller instanceof BalanceController) {
            $this->balanceController = $controller;
        } elseif ($controller instanceof PhoneController) {
            $this->phoneController = $controller;
        } elseif ($controller instanceof SenderController) {
            $this->senderController = $controller;
        } elseif ($controller instanceof TemplateController) {
            $this->templateController = $controller;
        } elseif ($controller instanceof MessageController) {
             $this->messageController = $controller;
        }
    }

    public function getBalance(): Balance
    {
        try {
            $this->balanceController->read();
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        } finally {
            $balance = Balance::getInstance();

            if ($balance->isFresh() && $balance->getBalance() <= 0) {
                throw new LowBalanceException($balance->getBalance());
            }

            return $balance;
        }
    }

    public function checkPhones(array $phones): Collection
    {
        $preparedData = array_map(function($phone) {
            return ['number' => $phone];
        }, array_unique($phones));

        $collection = new Collection(Phone::class, $preparedData);

        try {
            $this->phoneController->read($collection);
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        } finally {
            return $collection;
        }
    }

    public function getSenders(string $channel = 'all'): Collection
    {
        $collection = new Collection(Sender::class);

        try {
            $this->senderController->read($collection, ['channel' => $channel]);
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        } finally {
            return $collection;
        }
    }

    public function getTemplates(): Collection
    {
        $collection = new Collection(Template::class);

        try {
            $this->templateController->read($collection);
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        } finally {
            return $collection;
        }
    }

    public function createTemplate(Template $template): void
    {
        try {
            $this->templateController->create($template);
        } catch (ViolationIntegrityEntityException $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
            throw $exception;
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        }
    }

    public function updateTemplate(Template $template): void
    {
        try {
            $this->templateController->update($template);
        } catch (ViolationIntegrityEntityException $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
            throw $exception;
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        }
    }

    public function deleteTemplate(Template $template): void
    {
        try {
            $this->templateController->delete($template);
        } catch (ViolationIntegrityEntityException $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
            throw $exception;
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        }
    }

    /**
     * @throws CollectionException
     */
    public function sendMessage(RouteCollection $collection): array
    {
        try {
            if ($collection->count() === 0) {
                throw new CollectionException($collection);
            }

            $this->getBalance();

            return $this->messageController->send($collection);
        } catch (CollectionException $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
            throw $exception;
        } catch (LowBalanceException | \Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        }

        return [];
    }

    protected function getMessageObject(string $channel, string $phone, string $text, string $sender = '', ?MessageMetadata $metadata = null, array $options = []): Message
    {
        switch ($channel) {
            case 'telegram':
                $message = new Telegram($phone, $text, $metadata);
                break;
            case 'vk':
                $message = new Vk(Config::getVKGroupId(), $phone, $text, $metadata);
                break;
            case 'viber':
                $message = new Viber(
                    $sender,
                    $phone,
                    $text,
                    $metadata,
                    $options['image_url'] ?? '',
                    isset($options['action']['title'], $options['action']['url']) ? $options['action'] : ['title' => '', 'url' => '']
                );
                break;
            default:
                $message = new Sms($sender, $phone, $text, $metadata);
        }

        return $message;
    }

    protected function send(array $phones, string $text, ?MessageMetadata $metadata = null, string $channel = 'sms', array $options = []): ?RouteCollection
    {
        if ($channel === 'sms' || $channel === 'viber') {
            $senders = $this->getSenders($channel);
        } else {
            $withoutSender = true;
        }

        if ((isset($senders) && $senders->count() > 0) || isset($withoutSender)) {
            $phones = $this->checkPhones($phones);
            if ($phones->count() > 0) {
                $sender = isset($senders) && is_object($senders) && method_exists($senders, 'first') ? $senders->first()->getSender() : '';
                foreach (array_chunk($phones->all(), 1000) as $chunk) {
                    $collection = count($chunk) === 1 ? new ChannelRouteCollection() : new MixedRouteCollection();

                    foreach ($chunk as $phone) {
                        if ($phone->checked() && $phone->valid()) {
                            $collection->addObject(
                                $this->getMessageObject(
                                    $channel,
                                    $phone->getNumber(),
                                    $text,
                                    $sender,
                                    is_null($metadata) ? MessageMetadata::create('') : $metadata,
                                    $options
                                )
                            );
                        }
                    }

                    if ($collection->count() > 0) {
                        $this->sendMessage($collection);
                    }
                }
            } else {
                throw new \InvalidArgumentException('The phone list is empty');
            }
        } else {
            throw new \InvalidArgumentException('Unavailable channel');
        }

        return $collection ?? null;
    }

    public function sendSms(array $phones, string $text, ?MessageMetadata $metadata = null): ?RouteCollection
    {
        return $this->send($phones, $text, $metadata);
    }

    public function sendTelegram(array $phones, string $text, ?MessageMetadata $metadata = null): ?RouteCollection
    {
        return $this->send($phones, $text, $metadata, 'telegram');
    }

    public function sendVk(array $phones, string $text, ?MessageMetadata $metadata = null): ?RouteCollection
    {
        return $this->send($phones, $text, $metadata, 'vk');
    }

    public function sendViber(array $phones, string $text, ?MessageMetadata $metadata = null, string $imageUrl = '', array $action = ['title' => '', 'url' => '']): ?RouteCollection
    {
        return $this->send(
            $phones,
            $text,
            $metadata,
            'viber',
            ['image_url' => $imageUrl, 'action' => $action]
        );
    }

    public function sendSimple(string $phone, string $text, ?MessageMetadata $metadata = null): ?RouteCollection
    {
        $senders = $this->getSenders('sms');
        if ($senders->count() > 0) {
            $sender = $senders->first()->getSender();
            $phones = $this->checkPhones([$phone]);
            if ($phones->count() > 0) {
                $collection = new SimpleRouteCollection();
                $phone = $phones->first();
                if ($phone->checked() && $phone->valid()) {
                    $collection->addObject(
                        new Sms(
                            $sender,
                            $phone->getNumber(),
                            $text,
                            is_null($metadata) ? MessageMetadata::create('') : $metadata
                        )
                    );
                }

                if ($collection->count() > 0) {
                    $this->sendMessage($collection);
                }
            } else {
                throw new \InvalidArgumentException('The phone list is empty');
            }
        }

        return $collection ?? null;
    }

    public function sendHybrid(string $phone, string $text, array $channels, ?MessageMetadata $metadata = null): ?RouteCollection
    {
        $phones = $this->checkPhones([$phone]);
        if ($phones->count() > 0) {
            $phone = $phones->first();
            $senders = $this->getSenders();
            if ($senders->count() > 0) {
                $collection = new HybridRouteCollection();
                foreach ($channels as $channel) {
                    foreach ($senders as $sender) {
                        if ($channel === $sender->getChannel() && $phone->checked() && $phone->valid()) {
                            $collection->addObject(
                                $this->getMessageObject(
                                    $channel,
                                    $phone->getNumber(),
                                    $text,
                                    $sender->getSender(),
                                    is_null($metadata) ? MessageMetadata::create('') : $metadata
                                )
                            );

                            continue 2;
                        }
                    }
                }

                if ($collection->count() > 0) {
                    $this->sendMessage($collection);
                }
            }
        } else {
            throw new \InvalidArgumentException('The phone list is empty');
        }

        return $collection ?? null;
    }

    public static function enableSandbox(string $apiKey): Sandbox
    {
        try {
            $token = new Token($apiKey);
            $sandbox = Sandbox::getInstance(
                $token->validate() ? $token : null
            );
            $properties = $sandbox->getProperties();

            if ($properties['active'] === false) {
                $sandbox->activate()->touch();
            }
        } catch (\Exception $exception) {
            Logger::log('debug', $exception->getMessage(), $exception->getTrace());
        } finally {
            return $sandbox ?? Sandbox::getInstance();
        }
    }

    public static function disableSandbox(bool $forget = false): bool
    {
        $sandbox = Sandbox::getInstance();
        $sandbox->deactivate();

        return $forget ? $sandbox->forgetInstance() : !$sandbox->active();
    }

    public static function statusHandler(array $data): Status
    {
        $status = new Status($data['status'] ?? null, $data['id'] ?? null);
        $status->setChannel(
            new Channel($data['channel'] ?? null)
        );

        if (isset($data['error'], $data['code'])) {
            $error = new Error();
            $error->code = $data['code'];
            $error->message = Error::getErrorDescription($data['code']) ?? $data['error'];
            $status->setError($error);
        }

        if (isset($data['price'], $data['parts'])) {
            $status->info['price'] = $data['price'];
            $status->info['parts'] = $data['parts'];
        }

        if (isset($data['custom_id'])) {
            $status->info['custom_id'] = $data['custom_id'];
        }

        return $status;
    }

    public static function replyHandler(array $data): Reply
    {
        return new Reply(
            $data['id'] ?? '',
            $data['text'] ?? '',
            $data['date'] ?? '',
            $data['custom_id'] ?? null
        );
    }
}