<?php

namespace Talk\Filter;

use Talk\Foundation\ValidationException as TalkValidationException;
use Talk\Locale\Translator;

trait ValidateFilterTrait
{
    /**
     * @throws TalkValidationException
     * @return array<string>|array<array>
     */
    protected function asStringArray($filterValue, bool $multidimensional = false): array
    {
        if (is_array($filterValue)) {
            $value = array_map(function ($subValue) use ($multidimensional) {
                if (is_array($subValue) && ! $multidimensional) {
                    $this->throwValidationException('talk.api.invalid_filter_type.must_not_be_multidimensional_array_message');
                } elseif (is_array($subValue)) {
                    return $this->asStringArray($subValue, true);
                } else {
                    return $this->asString($subValue);
                }
            }, $filterValue);
        } else {
            $value = explode(',', $this->asString($filterValue));
        }

        return $value;
    }

    /**
     * @throws TalkValidationException
     */
    protected function asString($filterValue): string
    {
        if (is_array($filterValue)) {
            $this->throwValidationException('talk.api.invalid_filter_type.must_not_be_array_message');
        }

        return trim($filterValue, '"');
    }

    /**
     * @throws TalkValidationException
     */
    protected function asInt($filterValue): int
    {
        if (! is_numeric($filterValue)) {
            $this->throwValidationException('talk.api.invalid_filter_type.must_be_numeric_message');
        }

        return (int) $this->asString($filterValue);
    }

    /**
     * @throws TalkValidationException
     * @return array<int>
     */
    protected function asIntArray($filterValue): array
    {
        return array_map(function ($value) {
            return $this->asInt($value);
        }, $this->asStringArray($filterValue));
    }

    /**
     * @throws TalkValidationException
     */
    protected function asBool($filterValue): bool
    {
        return $this->asString($filterValue) === '1';
    }

    /**
     * @throws TalkValidationException
     */
    private function throwValidationException(string $messageCode): void
    {
        $translator = resolve(Translator::class);

        throw new TalkValidationException([
            'message' => $translator->trans($messageCode, ['{filter}' => $this->getFilterKey()]),
        ]);
    }
}
