<?php

namespace Nexus\Message\Sdk;

class Config
{
    public static ?int $vk_group_id = null;
    const BASE_URL = 'https://api.imobis.ru';
    const SANDBOX_BASE_URL = 'https://sandbox.imobis.ru';
    const API_VERSION = 'v3';
    const UUID_REGEX = "/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i";
    const TEST_API_KEY = '14fed124-2eea-4690-bd32-b7c6cc27f5fb';
    const TEMPLATE_REGEX = "/[^\\\[\]\~|]+/gmu";
    const HTTP_CONNECT_TIMEOUT = 3.0;
    const HTTP_TIMEOUT = 90.0;
    const LOGGER_NAME = 'NEXUS-MESSAGE-SDK';
    const LOGGER_STREAM = 'php://stdout';
    const CURRENCY = 'RUB';
    const MIN_TTL = 60;
    const MAX_TTL = 172800;
    const MEDIUM_TTL = 86400;
    const DEFAULT_TTL = 600;
    const COLLECTION_MODE = 'collection';
    const DATA_MODE = 'data';
    const VK_GROUP_ID = 5965316;

    public static function getVKGroupId(): int
    {
        return is_int(self::$vk_group_id) ? self::$vk_group_id : self::VK_GROUP_ID;
    }
}