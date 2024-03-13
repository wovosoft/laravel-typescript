<?php

namespace Wovosoft\LaravelTypescript\Helpers;

use Illuminate\Support\Collection;

class Column {
    public string  $name;
    public string  $type_name;
    public string  $type;
    public ?string $collation;
    public bool    $nullable;
    public ?string $default;
    public ?string $comment;
    public bool    $auto_increment;
    public ?string $generation;

    public function getName(): string {
        return $this->name;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getTypeName(): string {
        return $this->type_name;
    }

    public function getNotNull(): bool {
        return !$this->nullable;
    }

    public static function fromArray(array $data): static {
        $item = new static();
        $item->name = $data['name'];
        $item->type_name = $data['type_name'];
        $item->type = $data['type'];
        $item->collation = $data['collation'];
        $item->nullable = $data['nullable'];
        $item->default = $data['default'];
        $item->comment = $data['comment'];
        $item->auto_increment = $data['auto_increment'];
        $item->generation = $data['generation'];
        return $item;
    }

    public function toArray(): array {
        return [
            'name'           => $this->name,
            'type_name'      => $this->type_name,
            'type'           => $this->type,
            'collation'      => $this->collation,
            'nullable'       => $this->nullable,
            'default'        => $this->default,
            'comment'        => $this->comment,
            'auto_increment' => $this->auto_increment,
            'generation'     => $this->generation,
        ];
    }

    public function toJson(): false|string {
        return json_encode($this->toArray());
    }

    public function toCollection(): Collection {
        return collect($this->toArray());
    }
}
