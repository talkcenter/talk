<?php

namespace Talk\User;

use Talk\Discussion\Access\DiscussionPolicy;
use Talk\Discussion\Discussion;
use Talk\Foundation\AbstractServiceProvider;
use Talk\Foundation\ContainerUtil;
use Talk\Group\Access\GroupPolicy;
use Talk\Group\Group;
use Talk\Http\Access\AccessTokenPolicy;
use Talk\Http\AccessToken;
use Talk\Post\Access\PostPolicy;
use Talk\Post\Post;
use Talk\Settings\SettingsRepositoryInterface;
use Talk\User\Access\ScopeUserVisibility;
use Talk\User\DisplayName\DriverInterface;
use Talk\User\DisplayName\UsernameDriver;
use Talk\User\Event\EmailChangeRequested;
use Talk\User\Event\Registered;
use Talk\User\Event\Saving;
use Talk\User\Throttler\EmailActivationThrottler;
use Talk\User\Throttler\EmailChangeThrottler;
use Talk\User\Throttler\PasswordResetThrottler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerDisplayNameDrivers();
        $this->registerPasswordCheckers();

        $this->container->singleton('talk.user.group_processors', function () {
            return [];
        });

        $this->container->singleton('talk.policies', function () {
            return [
                Access\AbstractPolicy::GLOBAL => [],
                AccessToken::class => [AccessTokenPolicy::class],
                Discussion::class => [DiscussionPolicy::class],
                Group::class => [GroupPolicy::class],
                Post::class => [PostPolicy::class],
                User::class => [Access\UserPolicy::class],
            ];
        });

        $this->container->extend('talk.api.throttlers', function (array $throttlers, Container $container) {
            $throttlers['emailChangeTimeout'] = $container->make(EmailChangeThrottler::class);
            $throttlers['emailActivationTimeout'] = $container->make(EmailActivationThrottler::class);
            $throttlers['passwordResetTimeout'] = $container->make(PasswordResetThrottler::class);

            return $throttlers;
        });
    }

    protected function registerDisplayNameDrivers()
    {
        $this->container->singleton('talk.user.display_name.supported_drivers', function () {
            return [
                'username' => UsernameDriver::class,
            ];
        });

        $this->container->singleton('talk.user.display_name.driver', function (Container $container) {
            $drivers = $container->make('talk.user.display_name.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('display_name_driver', '');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $container->make($driverClass)
                : $container->make(UsernameDriver::class);
        });

        $this->container->alias('talk.user.display_name.driver', DriverInterface::class);
    }

    protected function registerPasswordCheckers()
    {
        $this->container->singleton('talk.user.password_checkers', function (Container $container) {
            return [
                'standard' => function (User $user, $password) use ($container) {
                    if ($container->make('hash')->check($password, $user->password)) {
                        return true;
                    }
                }
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, Dispatcher $events)
    {
        foreach ($container->make('talk.user.group_processors') as $callback) {
            User::addGroupProcessor(ContainerUtil::wrapCallback($callback, $container));
        }

        /**
         * @var \Illuminate\Container\Container $container
         */
        User::setHasher($container->make('hash'));
        User::setPasswordCheckers($container->make('talk.user.password_checkers'));
        User::setGate($container->makeWith(Access\Gate::class, ['policyClasses' => $container->make('talk.policies')]));
        User::setDisplayNameDriver($container->make('talk.user.display_name.driver'));

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);
        $events->subscribe(TokensClearer::class);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
        User::registerPreference('locale');

        User::registerVisibilityScoper(new ScopeUserVisibility(), 'view');
    }
}
