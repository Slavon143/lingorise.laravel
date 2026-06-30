<?php

namespace App\Services\Intelligence\Subscription;

use App\Models\Book;
use App\Models\User;

class BookAccessService
{
    public function __construct(
        private readonly EffectiveAiLimitsResolver $limitsResolver,
    ) {}

    public function userCanAccess(User $user, Book $book): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($book->isPublic()) {
            return true;
        }

        if ($book->owner_id === $user->id) {
            return true;
        }

        if ($book->access_type === 'premium') {
            $plan = $this->limitsResolver->resolve($user);

            return $plan->premiumBooksEnabled();
        }

        if ($book->access_type === 'private') {
            return false;
        }

        return false;
    }

    public function userCanRead(User $user, Book $book): bool
    {
        return $this->userCanAccess($user, $book);
    }

    public function userCanAddFromLibrary(User $user, Book $book): bool
    {
        if (! $this->userCanAccess($user, $book)) {
            return false;
        }

        $limits = $this->limitsResolver->resolve($user);
        $limit = $limits->privateBooksLimit();

        if ($limit === null) {
            return true;
        }

        $currentCount = $user->books()
            ->whereNotNull('original_book_id')
            ->count();

        return $currentCount < $limit;
    }
}
