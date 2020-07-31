<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Collections\Collection;

class EntityCollection extends Collection
{
    private array $added = [];
    private array $updated = [];
    private array $removed = [];

    /**
     * @return self
     */
    public static function from(iterable $items)
    {
        return new self($items);
    }

    public function remove($item): void
    {
        $this->removed[] = $item;
    }

    public function add($item): void
    {
        $this->added[] = $item;
    }

    public function update($item): void
    {
        $this->updated[] = $item;
    }

    public function load()
    {
        //TODO: load related entities into collection
    }

    public function saveChanges()
    {
        //TODO: Apply changes
    }
}

/*
    //Goal is to support something simple as:

    $user = $userRepository->findById(1);
    $user->phones->remove(1);
    $user->phones->remove(2);

    $user->phones->update(new PhoneNumber(5, "202-100-1"));
    $user->phones->update(new PhoneNumber(6, "202-100-1"));

    $user->phones->add(new PhoneNumber(null, "202-100-2"));
    $user->phones->add(new PhoneNumber(null, "202-100-3"));

    $userRepository->save($user);

    //Above should do something like:
    // 1. Save user
    // 2. Remove phone numbers by id
    // 3. Update phone numbers
    // 4. Attach user_id to PhoneNumber and save phone number
*/
