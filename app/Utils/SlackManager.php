<?php

namespace App\Utils;

use \Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class SlackManager
 * @package App\Utils
 */

class SlackManager
{
    /**
     * https://www.webfx.com/tools/emoji-cheat-sheet/
     */

    const NOTIFY_MESSAGE = [
        'text' => '',
        'icon_emoji' => ':star:',
        'attachments' => [
            'color' => 'good'
        ]
    ];

    const ERROR_MESSAGE = [
        'text' => '',
        'icon_emoji' => ':x:',
        'attachments' => [
            'color' => 'danger'
        ]
    ];

    const NOTIFY_MESSAGE_MAX_COUNT = 1800;

    private $env;

    /**
     * SlackManager constructor.
     */
    public function __construct()
    {
        if (RequestManager::isLocal()) {
            return;
        }

        $this->env = 'production' !== config('app.env') ? 'development' : 'production';
    }

    /**
     * エラー通知用のURLを取得
     *
     * @return ApiClientManager|null
     */
    private function getErrorApiClient()
    {
        if ($this->env && $url = config('slack.error.url')) {
            return new ApiClientManager($url);
        }

        return null;
    }

    /**
     * 通知用のURLを取得
     *
     * @return ApiClientManager|null
     */
    private function getNotifyApiClient()
    {
        if ($this->env && $url = config('slack.notify.url')) {
            return new ApiClientManager($url);
        }

        return null;
    }

    /**
     * 指定のSlackチャンネルへ通知を送る
     *
     * @param Exception|Throwable $e
     * @return bool|mixed
     */
    public function sendError($e)
    {
        $client = $this->getErrorApiClient();
        if (empty($client)) {
            return false;
        }

        try {
            $body = array_merge(self::ERROR_MESSAGE, [
                'text' => $this->formatExceptionMessage($e),
            ]);
            return $client->post('', $body);
        } catch (Exception $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * @param $body
     * @return false|mixed
     */
    public function notify($body)
    {
        $client = $this->getNotifyApiClient();
        if (empty($client)) {
            return false;
        }

        try {
            $body = array_merge(self::NOTIFY_MESSAGE, [
                'text' => $body,
            ]);
            return $client->post('', $body);
        } catch (Exception $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * @param Exception $e
     * @return string
     */
    private function formatExceptionMessage($e)
    {
        $messages = [];
        $messages[] = config('app.url');
        $messages[] = $e->getFile() . ' L.' . $e->getLine();
        $messages[] = $e->getMessage();
        $messages[] = '```' . $this->formatTrace($e->getTraceAsString()) . '```';

        return implode("\n", $messages);
    }

    /**
     * @param $text
     * @return false|string
     */
    private function formatTrace($text)
    {
        return substr($text, 0, self::NOTIFY_MESSAGE_MAX_COUNT);
    }
}
