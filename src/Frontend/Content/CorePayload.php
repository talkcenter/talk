<?php

namespace Talk\Frontend\Content;

use Talk\Frontend\Document;
use Talk\Http\RequestUtil;
use Talk\Locale\LocaleManager;
use Psr\Http\Message\ServerRequestInterface as Request;

class CorePayload
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
        $document->payload = array_merge(
            $document->payload,
            $this->buildPayload($document, $request)
        );
    }

    private function buildPayload(Document $document, Request $request)
    {
        $data = $this->getDataFromApiDocument($document->getSiteApiDocument());

        return [
            'resources' => $data,
            'session' => [
                'userId' => RequestUtil::getActor($request)->id,
                'csrfToken' => $request->getAttribute('session')->token()
            ],
            'locales' => $this->locales->getLocales(),
            'locale' => $request->getAttribute('locale')
        ];
    }

    private function getDataFromApiDocument(array $apiDocument): array
    {
        $data[] = $apiDocument['data'];

        if (isset($apiDocument['included'])) {
            $data = array_merge($data, $apiDocument['included']);
        }

        return $data;
    }
}
