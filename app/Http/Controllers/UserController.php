<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //


    public function users(){

        $users = User::all();

        // User does kyc (kycverified isTrue)

        //⁠ ⁠user lists 10 properties

        // user uploads profile picture (profile picture column is not null)

        // user updates social media information

        // user updates bank account info and name matches registered name

        
        

    }

    


}
