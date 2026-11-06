<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Api\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Fields the admin UI is allowed to sort by.
     *
     * @var array<int, string>
     */
    private const SORTABLE_FIELDS = ['id', 'name', 'email', 'created_at', 'updated_at'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $search = trim((string) $request->input('search', ''));

        $sortField = (string) $request->input('sort_field', 'updated_at');
        if (!in_array($sortField, self::SORTABLE_FIELDS, true)) {
            $sortField = 'updated_at';
        }

        $sortDirection = strtolower((string) $request->input('sort_direction', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $query = User::query();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $users = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\CreateUserRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        $data = $request->validated();
        $data['is_admin'] = true;
        $data['email_verified_at'] = now();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateUserRequest $request
     * @param \App\Models\Api\User                 $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Api\User     $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete the account you are signed in with.',
            ], 422);
        }

        $user->delete();

        return response()->noContent();
    }
}
