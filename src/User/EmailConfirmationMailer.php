<?php

namespace Talk\User;

use Talk\Http\UrlGenerator;
use Talk\Mail\Job\SendRawEmailJob;
use Talk\Settings\SettingsRepositoryInterface;
use Talk\User\Event\EmailChangeRequested;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailConfirmationMailer
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(SettingsRepositoryInterface $settings, Queue $queue, UrlGenerator $url, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->queue = $queue;
        $this->url = $url;
        $this->translator = $translator;
    }

    public function handle(EmailChangeRequested $event)
    {
        $email = $event->email;
        $data = $this->getEmailData($event->user, $email);

        $body = $this->translator->trans('talk.email.confirm_email.body', $data);
        $subject = $this->translator->trans('talk.email.confirm_email.subject');

        $this->queue->push(new SendRawEmailJob($email, $subject, $body));
    }

    /**
     * @param User $user
     * @param string $email
     * @return EmailToken
     */
    protected function generateToken(User $user, $email)
    {
        $token = EmailToken::generate($email, $user->id);
        $token->save();

        return $token;
    }

    /**
     * Get the data that should be made available to email templates.
     *
     * @param User $user
     * @param string $email
     * @return array
     */
    protected function getEmailData(User $user, $email)
    {
        $token = $this->generateToken($user, $email);

        return [
            'username' => $user->display_name,
            'url' => $this->url->to('site')->route('confirmEmail', ['token' => $token->token]),
            'site' => $this->settings->get('site_title')
        ];
    }
}
