<?php

namespace Talk\Install\Steps;

use Talk\Foundation\Application;
use Talk\Install\Step;
use Talk\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;

class WriteSettings implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var array
     */
    private $custom;

    public function __construct(ConnectionInterface $database, array $custom)
    {
        $this->database = $database;
        $this->custom = $custom;
    }

    public function getMessage()
    {
        return 'Writing default settings';
    }

    public function run()
    {
        $repo = new DatabaseSettingsRepository($this->database);

        $repo->set('version', Application::VERSION);

        foreach ($this->getSettings() as $key => $value) {
            $repo->set($key, $value);
        }
    }

    private function getSettings()
    {
        return $this->custom + $this->getDefaults();
    }

    private function getDefaults()
    {
        return [
            'allow_hide_own_posts' => 'reply',
            'allow_post_editing' => 'reply',
            'allow_renaming' => '10',
            'allow_sign_up' => '1',
            'custom_less' => '',
            'default_locale' => 'zh',
            'default_route' => '/all',
            'display_name_driver' => 'username',
            'extensions_enabled' => '[]',
            'site_title' => '新的 TalkCenter 站点',
            'site_description' => 'TalkCenter是一个探索和学习交流技巧的轻型论坛系统。',
            'mail_driver' => 'mail',
            'mail_from' => 'noreply@localhost',
            'slug_driver_Talk\Discussion\Discussion' => 'default',
            'slug_driver_Talk\User\User' => 'default',
            'theme_colored_header' => '0',
            'theme_dark_mode' => '0',
            'theme_primary_color' => '#4D698E',
            'theme_secondary_color' => '#4D698E',
            'welcome_message' => '令人难以置信的轻量，使用现代技术构建的可扩展讨论社区框架，灵活且快速，可以帮助您更好地构建成功的网站。',
            'welcome_title' => '欢迎来到TalkCenter社区',
        ];
    }
}
