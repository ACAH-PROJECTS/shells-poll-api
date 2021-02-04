<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(['api.admin']);
        $this->middleware(['api.manager'])->only(['index']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (UserResource::collection(User::all()))->additional([
            'meta' => [
                'count' => User::count()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'names' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:ADMIN,MANAGER',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toArray(), 422);
        }


        $request['password'] = Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = User::create($request->toArray());

        return (new UserResource($user))->additional([
            'meta' => [
                'success' => true,
                'message' => "user created"
            ]
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'names' => 'string',
            'lastname' => 'string',
            'role' => 'in:admin,manager'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toArray(), 422);
        }

        if ($request->has('names')) {
            $user->names = $request->names;
        }

        if ($request->has('lastname')) {
            $user->lastname = $request->lastname;
        }

        if ($user->isClean()) {
            return response([
                'message' => 'nothing to change'
            ], 304);
        }

        if ($user->isDirty()) {
            $user->save();
            return response([
                'data' => $user,
                'meta' => [
                    'success' => true,
                    'message' => 'updated'
                ]
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response([
            'message' => 'user deleted'
        ], 204);
    }
}
