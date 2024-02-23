<?php

namespace Talk\Frontend;

use Talk\Api\Client;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Frontend
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var callable[]
     */
    protected $content = [];

    public function __construct(Factory $view, Client $api)
    {
        $this->view = $view;
        $this->api = $api;
    }

    /**
     * @param callable $content
     */
    public function content(callable $content)
    {
        $this->content[] = $content;
    }

    public function document(Request $request): Document
    {
        $siteDocument = $this->getSiteDocument($request);

        $document = new Document($this->view, $siteDocument, $request);

        $this->populate($document, $request);

        return $document;
    }

    protected function populate(Document $document, Request $request)
    {
        foreach ($this->content as $content) {
            $content($document, $request);
        }
    }

    private function getSiteDocument(Request $request): array
    {
        return $this->getResponseBody(
            $this->api->withParentRequest($request)->get('/')
        );
    }

    private function getResponseBody(Response $response): array
    {
        return json_decode($response->getBody(), true);
    }
}
