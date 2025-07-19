<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SessionInterface;
use App\DataObjects\DataTableQueryParams;
use Psr\Http\Message\ServerRequestInterface;

class RequestService
{
    public function __construct(private readonly SessionInterface $session) {}

    public function getReferer(ServerRequestInterface $request): string
    {
        $referer            = $request->getHeader('referer')[0] ?? '';
        $sessionPreviousUrl = $this->session->get('previousUrl', '');

        if (empty($referer)) {
            $referer = $sessionPreviousUrl;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        if ($refererHost !== $request->getUri()->getHost()) {
            $referer = $sessionPreviousUrl;
        }

        return $referer;
    }

    public function isXhr(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    public function getDataTableQueryParameters(ServerRequestInterface $request): DataTableQueryParams
    {
        $params = $request->getQueryParams();

        return new DataTableQueryParams(
            (int)$params['start'],
            (int)$params['length'],
            $params['columns'][$params['order'][0]['column']]['data'],
            $params['order'][0]['dir'],
            $params['search']['value'],
            (int)$params['draw'],
        );
    }
}