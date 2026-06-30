<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;
use App\Services\Intelligence\Subscription\BookAccessService;

class BookPolicy
{
    public function __construct(
        private readonly BookAccessService $bookAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Book $book): bool
    {
        return $this->bookAccess->userCanAccess($user, $book);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }

    public function delete(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }

    public function restore(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }

    public function forceDelete(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }
}
