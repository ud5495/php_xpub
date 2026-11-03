<?php

declare(strict_types=1);

namespace Bedud5495\XPub;

use Bedud5495\XPub\Exception\InvalidDerivationPathException;

/**
 * @implements \IteratorAggregate<int, ChildNumber>
 */
final class DerivationPath implements \IteratorAggregate, \Countable
{
    /** @var list<ChildNumber> */
    private readonly array $numbers;

    /**
     * @param list<ChildNumber> $numbers
     */
    private function __construct(array $numbers)
    {
        $this->numbers = $numbers;
    }

    /**
     * @param int|array<array-key, mixed>|self $path
     */
    public static function from(int|array|self $path): self
    {
        if ($path instanceof self) {
            return $path;
        }

        $values = is_array($path) ? $path : [$path];
        $numbers = [];

        foreach ($values as $value) {
            if (!is_int($value)) {
                throw new InvalidDerivationPathException('Every child index must be an integer.');
            }

            $numbers[] = ChildNumber::fromInt($value);
        }

        return new self($numbers);
    }

    /**
     * @return list<ChildNumber>
     */
    public function numbers(): array
    {
        return $this->numbers;
    }

    /**
     * @return \Traversable<int, ChildNumber>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->numbers);
    }

    public function count(): int
    {
        return count($this->numbers);
    }

    public function isEmpty(): bool
    {
        return $this->numbers === [];
    }
}
