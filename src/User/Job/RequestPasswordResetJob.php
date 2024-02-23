<?php

namespace Talk\User\Job;

use Talk\Http\UrlGenerator;
use Talk\Mail\Job\SendRawEmailJob;
use Talk\Queue\AbstractJob;
use Talk\Settings\SettingsRepositoryInterface;
use Talk\User\PasswordToken;
use Talk\User\UserRepository;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestPasswordResetJob extends AbstractJob
{
    /**
     * @var string
     */
    protected $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        TranslatorInterface $translator,
        UserRepository $users,
        Queue $queue
    ) {
        $user = $users->findByEmail($this->email);

        if (! $user) {
            return;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        $data = [
            'username' => $user->display_name,
            'url' => $url->to('site')->route('resetPassword', ['token' => $token->token]),
            'site' => $settings->get('site_title'),
        ];

        $body = $translator->trans('talk.email.reset_password.body', $data);
        $subject = $translator->trans('talk.email.reset_password.subject');

        $queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
