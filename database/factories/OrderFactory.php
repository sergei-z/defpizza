<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Order;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'email' => $faker->email,
        'number' => $faker->unique()->randomNumber($nbDigits = 8),
        'phone' => $faker->boolean(85) ? $faker->phoneNumber : null,
        'owner_id' => $faker->boolean(50) ? App\Models\User::all()->random()->id : null,
        'state_id' => App\Models\OrderState::all()->random()->id,
        'applied_promo_id' => $faker->boolean(30) ? App\Models\Promo::all()->random()->id : null,
    ];
});