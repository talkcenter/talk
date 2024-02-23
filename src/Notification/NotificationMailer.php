<?php

namespace Talk\Notification;

use Talk\Settings\SettingsRepositoryInterface;
use Talk\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Mail\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationMailer
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var TranslatorInterface&Translator
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param TranslatorInterface&Translator $translator
     */
    public function __construct(Mailer $mailer, TranslatorInterface $translator, SettingsRepositoryInterface $settings)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    /**
     * @param MailableInterface $blueprint
     * @param User $user
     */
    public function send(MailableInterface $blueprint, User $user)
    {
        // Ensure that notifications are delivered to the user in their default language, if they've selected one.
        // If the selected locale is no longer available, the site default will be used instead.
        $this->translator->setLocale($user->getPreference('locale') ?? $this->settings->get('default_locale'));

        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('blueprint', 'user'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->display_name)
                        ->subject($blueprint->getEmailSubject($this->translator));
            }
        );
    }
}
