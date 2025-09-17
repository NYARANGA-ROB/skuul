<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = new User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = Hash::make('password');
$user->email_verified_at = now();
$user->birthday = '1990-01-01';
$user->gender = 'male';
$user->nationality = 'Kenyan';
$user->phone = '+254700000000';
$user->address = 'Nairobi';
$user->city = 'Nairobi';
$user->state = 'Nairobi';
$user->country = 'Kenya';
$user->religion = 'Christian';
$user->blood_group = 'O+';
$user->school_id = 1;
$user->save();

echo 'User created with ID: ' . $user->id;
