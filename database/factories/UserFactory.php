<?php

namespace Database\Factories;

<<<<<<< HEAD
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
=======
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
>>>>>>> 80e3dc5 (First commit)

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
<<<<<<< HEAD
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make(Str::random(10)),
            'remember_token'    => Str::random(10),
            'address'           => $this->faker->address(),
            'birthday'          => $this->faker->date(),
            'address'           => $this->faker->address(),
            'school_id'         => 1,
            'blood_group'       => 'a+',
            'religion'          => 'christian',
            'nationality'       => $this->faker->country(),
            'state'             => 'wyoming',
            'city'              => $this->faker->city(),
            'gender'            => 'male',
=======
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
>>>>>>> 80e3dc5 (First commit)
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
<<<<<<< HEAD

    /**
     * Indicate that the user should have a personal team.
     *
     * @return $this
     */
    public function withPersonalTeam()
    {
        if (!Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Team::factory()
                ->state(function (array $attributes, User $user) {
                    return ['name' => $user->name.'\'s Team', 'user_id' => $user->id, 'personal_team' => true];
                }),
            'ownedTeams'
        );
    }
=======
>>>>>>> 80e3dc5 (First commit)
}
