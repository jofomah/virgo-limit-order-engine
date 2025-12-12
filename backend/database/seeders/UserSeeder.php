<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->line("Seed Traders account" . PHP_EOL);

        // Define the common attributes that determine if a record exists
        // $attributes will be the uniqueness key (email)
        // $values will be the data to set/update

        $usersToSeed = [
            [
                'email'  => 'trader1@example.com',
                'values' => [
                    'name'              => 'Trader 1',
                    'password'          => Hash::make('trader1'),
                    'email_verified_at' => now(), // Verified
                ],
            ],
            [
                'email'  => 'trader2@example.com',
                'values' => [
                    'name'              => 'Trader 2',
                    'password'          => Hash::make('trader2'),
                    'email_verified_at' => now(), // Verified
                ],
            ],
            [
                'email'  => 'trader3@example.com',
                'values' => [
                    'name'              => 'Trader 3',
                    'password'          => Hash::make('trader3'),
                    'email_verified_at' => now(),
                ],
            ],

            [
                'email'  => 'trader4@example.com',
                'values' => [
                    'name'              => 'Trader 4',
                    'password'          => Hash::make('trader4'),
                    'email_verified_at' => now(), // Verified
                ],
            ],
        ];

        foreach ($usersToSeed as $userData) {
            $this->command->line("Creating {$userData['email']} Traders account");
            // Uniqueness key: The email address
            $attributes = ['email' => $userData['email']];

            // Data to insert or update
            $values = $userData['values'];

            // Use updateOrCreate for idempotency
            User::updateOrCreate($attributes, $values);

            $this->command->line("Created {$userData['email']} Traders account " . PHP_EOL);
        }

        $this->command->line("Seeding of Test Trader accounts completed!");
    }
}
