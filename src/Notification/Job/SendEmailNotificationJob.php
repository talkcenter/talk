<?php

namespace Talk\Notification\Job;

use Talk\Notification\MailableInterface;
use Talk\Notification\NotificationMailer;
use Talk\Queue\AbstractJob;
use Talk\User\User;

class SendEmailNotificationJob extends AbstractJob
{
    /**
     * @var MailableInterface
     */
    private $blueprint;

    /**
     * @var User
     */
    private $recipient;

    public function __construct(MailableInterface $blueprint, User $recipient)
    {
        $this->blueprint = $blueprint;
        $this->recipient = $recipient;
    }

    public function handle(NotificationMailer $mailer)
    {
        $mailer->send($this->blueprint, $this->recipient);
    }
}
