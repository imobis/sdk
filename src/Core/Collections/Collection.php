<?php

namespace Nexus\Message\Sdk\Core\Collections;

class Collection implements \ArrayAccess, \Iterator, \Countable
{
    protected array $items = [];
    protected string $class;

    public function __construct(string $class, array $items = [])
    {
        $this->class = $class;
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    protected function add(...$args): void
    {
        $this->items[] = new $this->class(...$args);
    }

    public function addObject(object $item): bool
    {
        if ($item instanceof $this->class) {
            $this->items[] = $item;

            return true;
        }

        return false;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function clear(): self
    {
        $this->items = [];

        return $this;
    }

    public function contains($item): bool
    {
        return in_array($item, $this->items, true);
    }

    public function filter(callable $callback): self
    {
        $collection = new static($this->class);
        $items = array_filter($this->items, $callback);

        if (count($items) > 0) {
            foreach ($items as $item) {
                if (is_object($item)) {
                    $collection->addObject($item);
                }
            }
        }

        return $collection;
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function first()
    {
        return $this->items[0] ?? null;
    }

    public function last()
    {
        return !empty($this->items) ? end($this->items) : null;
    }
}
