<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Core\Traits\Arrayable;

class Error
{
    use Arrayable;

    public ?string $message;
    public int $code;
    protected const ERROR_DESCRIPTIONS = [
        '1000' => 'Undocumented mistake (please ask support)',
        '1001' => 'Internal error',
        '1003' => 'Authorization error',
        '1010' => 'Phone number error',
        '1011' => 'Sender ID error',
        '1012' => 'Message text error',
        '1013' => 'IP error (request from blacklisted IP)',
        '1014' => 'User message ID error',
        '1015' => 'Balance error - not enough funds',
        '1016' => 'Delivery failure (routing is not configured)',
        '1018' => "You've already sent message with same user message ID",
        '1019' => 'Phone number is in the black list',
        '1020' => "You've reached max. Subscribers per request limit",
        '1021' => 'Invalid argument',
        '1022' => 'Delivery is only opened to the number registered for you account',
        '1025' => 'Wrong sms ID format',
        '1026' => 'Wrong user message ID format',
        '1027' => 'There should be at least one sms ID in the request',
        '1029' => 'Message with this ID was not found',
        '1030' => 'Routing error',
        '1031' => 'Invalid content type',
        '1033' => 'Wrong TTL for messengers',
        '1034' => 'Wrong time-out ("time-to-live") for Viber',
        '1035' => 'Wrong set of parameters for your content (text, image, button)',
        '1036' => 'Wrong image URL',
        '1037' => 'Wrong button URL',
        '1040' => 'Wrong time-out ("time-to-live") for SMS',
        '1042' => 'Missed title for the template',
        '1043' => 'Missed template text',
        '1050' => 'Template does not exist',
        '1051' => "You don't have access to the template",
        '1052' => 'There was an error verifying fields values',
        '1053' => 'Missed value that is obligatory',
        '1054' => 'Wrong DATE format',
        '1056' => 'Wrong value for this field type',
        '1058' => "User can't make that action",
        '1100' => 'Unknown error (VK.com)',
        '1101' => 'Exceeding the limit (VK.com)',
        '1102' => 'Message delivery is blocked by the VK.com user',
        '1200' => 'No existing transactional messages delivered to the user at the moment (Viber)',
        '1201' => 'Message delivery is blocked by the Viber user',
        '1202' => 'Viber app is not installed',
        '1203' => 'Old version of Viber app',
    ];

    static function getErrorDescription($code): ?string
    {
        return self::ERROR_DESCRIPTIONS[$code] ?? null;
    }
}