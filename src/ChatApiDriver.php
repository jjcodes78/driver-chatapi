<?php
/**
 * Created by PhpStorm.
 * User: jjsquady
 * Date: 12/21/18
 * Time: 10:59
 */

namespace ChatApiDriver;


use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Attachments\Attachment;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatApiDriver extends HttpDriver
{

    const DRIVER_NAME = 'ChatApi';

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
            $this->event->get('type') == 'chat';

        return $matches;
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $message = $this->event->get('body');
            $userId = $this->event->get('chatId');
            $this->messages = [new IncomingMessage($message, $userId, $userId, $this->payload)];
        }
        return $this->messages;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('token')) && ! empty($this->config->get('endPoint'));
    }

    /**
     * Retrieve User information.
     * @param IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    /**
     * @param IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param string|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return $this|array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $payload = [];
        $payload['chatId'] = $matchingMessage->getSender();
        $payload['body'] = $message->getText();

        if($message instanceof OutgoingMessage) {
            if(!is_null($message->getAttachment())) {
                $attachment = $message->getAttachment();
                $payload['chatId'] = $matchingMessage->getSender();
                $payload['body'] = $this->getSecureAttachmentUrl($attachment);
                $payload['filename'] = $this->getAttachmentFileName($attachment);
            }
        }

        return $payload;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        $action = 'message';
        if(isset($payload['filename'])) {
            $action = 'sendFile';
        }

        $url = $this->config->get('instance_url') . "/{$action}?token={$this->config->get('token')}";
        $response = $this->http->post($url, [], $payload);
        return $response;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {
        $this->payload = (array) json_decode($request->getContent());
        $this->event = isset($this->payload['messages']) ?
            collect($this->payload['messages'][0]) :
            collect([
                'body' => null,
                'chatId' => null
            ]);
        $this->config = Collection::make($this->config->get('chatapi', []));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $url = $this->config->get('instance_url') . "/{$endpoint}?token={$this->config->get('token')}";
        $this->http->post($url, [], $parameters);
    }

    protected function getAttachmentFileName(Attachment $attachment)
    {
        return collect(explode('/', $attachment->getUrl()))->last();
    }

    protected function getSecureAttachmentUrl(Attachment $attachment) {
        return str_replace('http', 'https', $attachment->getUrl());
    }
}