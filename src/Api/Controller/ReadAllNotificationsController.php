<?php

namespace Talk\Api\Controller;

use Talk\Http\RequestUtil;
use Talk\Notification\Command\ReadAllNotifications;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;

class ReadAllNotificationsController extends AbstractDeleteController
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $this->bus->dispatch(
            new ReadAllNotifications(RequestUtil::getActor($request))
        );
    }
}
