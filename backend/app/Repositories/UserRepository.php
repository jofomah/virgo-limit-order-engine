<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        parent::__construct(new User());
    }

    public function findByEmail(string $email): ?User
    {
        return $this->newQueryWithRelations()->where('email', $email)->first();
    }
}
