<?php

namespace Talk\Group\Command;

use Talk\Foundation\DispatchEventsTrait;
use Talk\Group\Event\Saving;
use Talk\Group\Group;
use Talk\Group\GroupRepository;
use Talk\Group\GroupValidator;
use Talk\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class EditGroupHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Talk\Group\GroupRepository
     */
    protected $groups;

    /**
     * @var GroupValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param GroupRepository $groups
     * @param GroupValidator $validator
     */
    public function __construct(Dispatcher $events, GroupRepository $groups, GroupValidator $validator)
    {
        $this->events = $events;
        $this->groups = $groups;
        $this->validator = $validator;
    }

    /**
     * @param EditGroup $command
     * @return Group
     * @throws PermissionDeniedException
     */
    public function handle(EditGroup $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $actor->assertCan('edit', $group);

        $attributes = Arr::get($data, 'attributes', []);

        if (isset($attributes['nameSingular']) && isset($attributes['namePlural'])) {
            $group->rename($attributes['nameSingular'], $attributes['namePlural']);
        }

        if (isset($attributes['color'])) {
            $group->color = $attributes['color'];
        }

        if (isset($attributes['icon'])) {
            $group->icon = $attributes['icon'];
        }

        if (isset($attributes['isHidden'])) {
            $group->is_hidden = $attributes['isHidden'];
        }

        $this->events->dispatch(
            new Saving($group, $actor, $data)
        );

        $this->validator->assertValid($group->getDirty());

        $group->save();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
