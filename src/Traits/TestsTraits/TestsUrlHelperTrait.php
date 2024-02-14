<?php

namespace Laraneat\Modules\Traits\TestsTraits;

use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\InvalidPath;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait TestsUrlHelperTrait
{
    public function addQueryParametersToUrl(string $url, array $queryParameters = []): string
    {
        $queryString = http_build_query($queryParameters);

        if ($queryString) {
            if (Str::contains($url, '?')) {
                $queryString = '&' . $queryString;
            } else {
                $queryString = '?' . $queryString;
            }
        }

        return rtrim($url, '/') . $queryString;
    }

    public function replaceByKeyValues(string $url, array $replaces): string
    {
        foreach ($replaces as $key => $value) {
            $url = Str::replace($key, $value, $url);
        }

        return $url;
    }

    public function trimSlashes($path): string
    {
        return trim($path, '/');
    }

    public function buildUrl(?string $path = null, array $queryParameters = [], array $replaces = []): string
    {
        $path = $path ?? $this->url ?? null;

        if (! $path) {
            throw InvalidPath::make($path);
        }

        if ($this->isAbsoluteUrl($path)) {
            return $this->addQueryParametersToUrl($path, $queryParameters);
        }

        $appUrl = $this->trimSlashes(config('app.url'));
        $url = $appUrl.'/'.$this->trimSlashes($path);

        if ($replaces) {
            $url = $this->replaceByKeyValues($url, $replaces);
        }

        return $this->addQueryParametersToUrl($url, $queryParameters);
    }

    public function isAbsoluteUrl(string $url): bool
    {
        return Str::startsWith($url, ['http://', 'https://']);
    }
}
