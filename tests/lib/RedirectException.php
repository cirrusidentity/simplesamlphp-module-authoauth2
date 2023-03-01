<?php

namespace Test\SimpleSAML;

class RedirectException extends \Exception
{
    private ?string $url;

    /**
     * @param string|null $url
     */
    public function __construct(string $message, ?string $url = null)
    {
        parent::__construct($message);
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
