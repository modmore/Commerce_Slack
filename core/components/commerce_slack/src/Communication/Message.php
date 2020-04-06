<?php

namespace modmore\Commerce_Slack\Communication;

final class Message
{
    private $blocks = [];
    /**
     * @var string
     */
    private $fallback;

    public function __construct(string $fallback)
    {

        $this->fallback = $fallback;
    }

    /**
     * @param array $data
     */
    public function addBlock(array $data): void
    {
        $this->blocks[] = $data;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'text' => $this->fallback,
            'blocks' => $this->getBlocks()
        ];
    }

    /**
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}