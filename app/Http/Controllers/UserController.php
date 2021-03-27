<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

use App\Models\User;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $vk_id = $request->launchParams['vk_user_id'];

        $user = User::where('vk_id', $vk_id)->first();

        if ($user)
        {
            return response()->json($user);
        }

        $user = User::create(['vk_id' => $vk_id]);

        $user->start_date = null;
        $user->years_count = null;
        $user->private = 1;

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $vk_id = $request->launchParams['vk_user_id'];

        $user = User::where('vk_id', $vk_id)->first();

        if ($user)
        {
            $start_date  = $request->start_date;
            $years_count = $request->years_count;
            $private     = $request->private;

            $validator = Validator::make($request->all(), [
                'start_date'  => 'date',
                'years_count' => 'integer|between:1,2',
                'private'     => 'boolean',
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($start_date) 
            {
                $user->start_date = $start_date;
            }

            if ($years_count)
            {
                $user->years_count = $years_count;
            }

            if (isset($private))
            {
                $user->private = $private;
            }

            $user->save();

            return response()->json($user);
        }

        return response()->json(['errors' => ['user not created']], 401);
    }

    public function show($user_id)
    {
        $user = User::where(['vk_id' => $user_id])->first();

        if ($user)
        {
            if ($user->private)
            {
                unset($user->date_start);
                unset($user->years_count);
            }

            $vk_user = Http::get('https://api.vk.com/method/users.get', [
                'v'            => '5.130',
                'user_ids'     => $user_id,
                'fields'       => 'photo_200',
                'access_token' => env('VK_SERVICE'),
            ]);

            if ($vk_user->failed())
            {
                return response()->json(['errors' => ['¯\_(ツ)_/¯']], 500);
            }

            $user_array = json_decode(json_encode($user), true);
            $vk_user_array = $vk_user->json()['response'][0];

            return response()->json(array_merge($user_array, $vk_user_array));
        }

        return response()->json(['errors' => ['user not found']], 404);
    }
}
