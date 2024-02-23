<?php

namespace Talk\Post\Filter;

use Talk\Filter\FilterInterface;
use Talk\Filter\FilterState;
use Talk\Filter\ValidateFilterTrait;
use Talk\User\UserRepository;

class AuthorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * @var \Talk\User\UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $usernames = $this->asStringArray($filterValue);

        $ids = $this->users->query()->whereIn('username', $usernames)->pluck('id');

        $filterState->getQuery()->whereIn('posts.user_id', $ids, 'and', $negate);
    }
}
