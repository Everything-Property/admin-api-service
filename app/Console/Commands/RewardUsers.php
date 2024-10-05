<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $alreadyCreditedCount = User::where('credited', true)->count();
        $maxCredited = 200;

        if ($alreadyCreditedCount >= $maxCredited) {
            Log::info('Maximum credited users reached.');
            return;
        }

        $users = User::where('kyc_verified', true)
            ->whereNotNull('profile_picture')
            ->where('credited', false)
            ->take($maxCredited - $alreadyCreditedCount)
            ->get();

        foreach ($users as $user) {
            $criteria = [
                'KYC Verified' => $user->kyc_verified,
                'Profile Picture' => !is_null($user->profile_picture),
                'Account Name matches First and Last Name' => $user->account_name == $user->first_name . ' ' . $user->last_name,
                'Has 10 or more properties' => $user->properties()->count() >= 10,
                'Has User Information' => $user->userInformation()->exists(),
            ];

            foreach ($criteria as $criterion => $met) {
                Log::info("User {$user->id} - {$criterion}: " . ($met ? 'YES' : 'NO'));
            }

            if (array_values($criteria)) {
                // All criteria are met
                $wallet = $user->wallet;

                if (!$wallet) {
                    $wallet = new UserWallet();
                    $wallet->user_id = $user->id;
                }

                $wallet->balance += 5000;
                $wallet->save();

                // Update user_wallet_transaction table
                $transaction = new UserWalletTransaction();
                $transaction->user_wallet_id = $wallet->id;
                $transaction->amount = 5000;
                $transaction->type = 'giveaway';
                $transaction->status = 'success';
                $transaction->narration = 'Give-away';
                $transaction->save();

                $user->credited = true;
                $user->save();

                Log::info("User {$user->id} ({$user->first_name} {$user->last_name}) was credited with 10000");
            } else {
                Log::info("User {$user->id} does not meet all criteria.");
            }
        }

        $this->info('Users criteria checked.');
    }
}
