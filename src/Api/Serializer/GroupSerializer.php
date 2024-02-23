<?php

namespace Talk\Api\Serializer;

use Talk\Group\Group;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'groups';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param Group $group
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($group)
    {
        if (! ($group instanceof Group)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Group::class
            );
        }

        return [
            'nameSingular' => $this->translateGroupName($group->name_singular),
            'namePlural'   => $this->translateGroupName($group->name_plural),
            'color'        => $group->color,
            'icon'         => $group->icon,
            'isHidden'     => $group->is_hidden
        ];
    }

    /**
     * @param string $name
     * @return string
     */
    private function translateGroupName($name)
    {
        $translation = $this->translator->trans($key = 'talk.group.'.strtolower($name));

        if ($translation !== $key) {
            return $translation;
        }

        return $name;
    }
}
