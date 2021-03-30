<?php

namespace App\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class LineManager
 * @package App\Utils
 */

class LineManager
{
    const VERIFY_ID_TOKEN_URL = 'https://api.line.me/oauth2/v2.1/verify';
    const PUSH_MESSAGE_URL = 'https://api.line.me/v2/bot/message/push';

    private static $client = null;

    const LINE_SENDER = [
        'name' => '温泉MaaS事務局',
        // TODO: 仮
        'iconUrl' => 'https://api.onsen-maas.com/logo-maas-4.png'
    ];

    const TITLE_COLOR = [
        'default' => '#09015f',
        'success' => '#55b3b1',
        'error' => '#af0069',
    ];

    /**
     * @return Client
     */
    protected static function client()
    {
        if (empty(self::$client)) {
            self::$client = new Client();
        }

        return self::$client;
    }

    /**
     * @return string
     */
    protected static function getClientId()
    {
        return config('line.client_id');
    }

    /**
     * @return string
     */
    protected static function getChannelAccessToken()
    {
        return config('line.channel_access_token');
    }

    /**
     * @param $idToken
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public static function verifyIdToken($idToken)
    {
        return self::client()->post(self::VERIFY_ID_TOKEN_URL, [
            'form_params' => [
                'id_token' => $idToken,
                'client_id' => self::getClientId(),
            ],
        ]);
    }

    /**
     * @param $lineUserId
     * @param string $message
     * @param string $title
     * @param string $type
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public static function pushMessage($lineUserId, $message = '', $title = '', $type = 'default')
    {
        $sendMessage = [
            'type' => 'flex',
            'altText' => $message,
            'sender' => self::LINE_SENDER,
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'text',
                    'text' => '',
                ],
            ],
        ];

        if ($title) {
            $sendMessage['contents']['hero'] = [
                'type' => 'box',
                'layout' => 'vertical',
                'backgroundColor' => self::getTitleColor($type),
                'paddingTop' => '20px',
                'paddingBottom' => '20px',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $title,
                        'align' => 'center',
                        'size' => 'md',
                        'color' => '#ffffff',
                        'weight' => 'bold',
                        'wrap' => true,
                    ]
                ],
            ];
        }

        if ($message) {
            $sendMessage['contents']['body'] = [
                'type' => 'box',
                'layout' => 'vertical',
                'backgroundColor' => '#f8f8f8',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                        'size' => 'sm',
                        'wrap' => true
                    ]
                ],
            ];
        }

        try {
            return self::client()->post(self::PUSH_MESSAGE_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::getChannelAccessToken(),
                ],
                'json' => [
                    'to' => $lineUserId,
                    'messages' => [
                        $sendMessage
                    ],
                ],
            ]);
        } catch (ClientException $e) {
            logger($e->getResponse()->getBody()->getContents());
            throw $e;
        }
    }

    /**
     * @param $type
     * @return bool|string
     */
    private static function getTitleColor($type)
    {
        $colorType = isset(self::TITLE_COLOR[$type]) ? $type : 'default';

        return self::TITLE_COLOR[$colorType];
    }
}
