<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

class RewardUsers extends Command
{
    protected $signature = 'reward:users';
    protected $description = 'Reward users who meet specific conditions with 10000 to their wallet';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $users = User::where('kyc_verified', true)
            ->whereNotNull('profile_picture')
            ->whereHas('properties', function($query) {
                $query->havingRaw('COUNT(*) >= 10');
            })
            ->whereHas('socialMedia')
            ->whereHas('bankAccount', function($query) {
                $query->where('name', 'LIKE', DB::raw('CONCAT(users.first_name, " ", users.last_name)'));
            })
            ->get();

        foreach ($users as $user) {
            $wallet = UserWallet::where('user_id', $user->id)->first();

            if ($wallet) {
                $wallet->balance += 10000;
                $wallet->save();
            } else {
                UserWallet::create([
                    'user_id' => $user->id,
                    'balance' => 10000,
                ]);
            }
        }

        $this->info('Users have been rewarded successfully.');
    }
}
