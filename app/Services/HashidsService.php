<?php

namespace App\Services;

use Hashids\Hashids;

class HashidsService
{
    private Hashids $hashids;

    public function __construct()
    {
        // Using the exact same configuration as Symfony
        $this->hashids = new Hashids(
            config('app.hashids.salt', 'fRCvFMQn*Zfl=GiyCq0#D_mzFD*.4X'), // salt
            config('app.hashids.length', 13), // minimum length
            config('app.hashids.alphabet', 'abcdefghijklmnopqrstuvwxyz') // alphabet
        );
    }

    public function encode($input): string
    {
        return $this->hashids->encode($input);
    }

    public function decode(string $input)
    {
        $decoded = $this->hashids->decode($input);
        return count($decoded) > 0 ? $decoded[0] : "";
    }
}
