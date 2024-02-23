<?php

namespace Talk\Api\Serializer;

use Talk\Http\AccessToken;
use Jenssegers\Agent\Agent;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccessTokenSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'access-tokens';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param AccessToken $token
     */
    protected function getDefaultAttributes($token)
    {
        $session = $this->request->getAttribute('session');

        $agent = new Agent();
        $agent->setUserAgent($token->last_user_agent);

        $attributes = [
            'token' => $token->token,
            'userId' => $token->user_id,
            'createdAt' => $this->formatDate($token->created_at),
            'lastActivityAt' => $this->formatDate($token->last_activity_at),
            'isCurrent' => $session && $session->get('access_token') === $token->token,
            'isSessionToken' => in_array($token->type, ['session', 'session_remember'], true),
            'title' => $token->title,
            'lastIpAddress' => $token->last_ip_address,
            'device' => $this->translator->trans('talk.site.security.browser_on_operating_system', [
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
            ]),
        ];

        // Unset hidden attributes (like the token value on session tokens)
        foreach ($token->getHidden() as $name) {
            unset($attributes[$name]);
        }

        // Hide the token value to non-actors no matter who they are.
        if (isset($attributes['token']) && $this->getActor()->id !== $token->user_id) {
            unset($attributes['token']);
        }

        return $attributes;
    }
}
