<?php

namespace Talk\Frontend\Content;

use Talk\Frontend\Document;
use Talk\Locale\LocaleManager;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Meta
{
    /**
     * @var LocaleManager
     */
    private $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    public function __invoke(Document $document, Request $request)
    {
        $document->language = $this->locales->getLocale();
        $document->direction = 'ltr';

        $document->meta = array_merge($document->meta, $this->buildMeta($document));
        $document->head = array_merge($document->head, $this->buildHead($document));
    }

    private function buildMeta(Document $document)
    {
        $siteApiDocument = $document->getSiteApiDocument();

        $meta = [
            'viewport' => 'width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1',
            'description' => Arr::get($siteApiDocument, 'data.attributes.description'),
            'theme-color' => Arr::get($siteApiDocument, 'data.attributes.themePrimaryColor')
        ];

        return $meta;
    }

    private function buildHead(Document $document)
    {
        $head = [];

        if ($faviconUrl = Arr::get($document->getSiteApiDocument(), 'data.attributes.faviconUrl')) {
            $head['favicon'] = '<link rel="shortcut icon" href="'.e($faviconUrl).'">';
        }

        return $head;
    }
}
