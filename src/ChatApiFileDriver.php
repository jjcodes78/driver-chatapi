<?php
/**
 * Created by PhpStorm.
 * User: jjsquady
 * Date: 12/21/18
 * Time: 18:26
 */

namespace ChatApiDriver;

use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class ChatApiFileDriver extends ChatApiDriver
{

    const DRIVER_NAME = 'ChatApiFile';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $matches = ! is_null($this->event->get('body')) &&
            ! is_null($this->event->get('chatId')) &&
            ! $this->event->get('fromMe') &&
            ! is_null($this->event->get('type')) &&
            ($this->event->get('type') == 'image' || $this->event->get('type') == 'file');

        return $matches;
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        $pattern = $this->event->get('body');

        switch ($this->event->get('type')) {
            case 'image':
                $pattern = Image::PATTERN;
                break;
            case 'file':
                $pattern = File::PATTERN;
                break;
        }

        if (empty($this->messages)) {
            $userId = $this->event->get('chatId');
            $message = new IncomingMessage($pattern, $userId, $userId, $this->payload);
            switch ($pattern) {
                case File::PATTERN:
                    $message->setFiles([new File($this->event->get('body'))]);
                    break;
                case Image::PATTERN:
                    $message->setImages([new Image($this->event->get('body'))]);
                    break;
            }
            $this->messages = [$message];
        }
        return $this->messages;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }

}