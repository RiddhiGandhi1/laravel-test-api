<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function searchUsers(Request $request)
    {
        $query = User::query();

        if ($request->has('searchQuery') && $request->searchQuery) {
            $searchQuery = $request->searchQuery;
            $query->where(function ($q) use ($searchQuery) {
                $q->where('first_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('last_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        if ($request->has('minAge') && $request->minAge) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= ?', [$request->minAge]);
        }

        if ($request->has('maxAge') && $request->maxAge) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= ?', [$request->maxAge]);
        }

        if ($request->has('city') && $request->city) {
            $city = $request->city;
            $query->whereHas('address', function ($q) use ($city) {
                $q->where('city', 'like', '%' . $city . '%');
            });
        }

        $users = $query->with('address')->get();

        $data = [
            'filter' => count($users),
            'data' => $users,
        ];

        return response()->json($data);
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'mobile_number' => 'nullable|digits:10|unique:users,mobile_number,' . $id,
            'birth_date' => 'nullable|date',
            'addresses' => 'nullable|array|min:1',
            'addresses.*.id' => 'nullable|exists:addresses,id',
            'addresses.*.address_line1' => 'nullable|string|max:255',
            'addresses.*.address_line2' => 'nullable|string|max:255',
            'addresses.*.pincode' => 'nullable|digits_between:4,6',
            'addresses.*.city' => 'nullable|string|max:255',
            'addresses.*.state' => 'nullable|string|max:255',
            'addresses.*.type' => 'nullable|in:home,office',
        ]);

        $user = User::with('address')->findOrFail($id);

        $userData = $request->only(['first_name', 'last_name', 'email', 'mobile_number', 'birth_date']);
        $user->update(array_filter($userData));

        if ($request->has('addresses')) {
            foreach ($request->addresses as $addressData) {
                if (isset($addressData['id'])) {
                    $address = Address::findOrFail($addressData['id']);
                    $address->update([
                        'address_line1' => $addressData['address_line1'],
                        'address_line2' => $addressData['address_line2'] ?? null,
                        'pincode' => $addressData['pincode'],
                        'city' => $addressData['city'],
                        'state' => $addressData['state'],
                        'type' => $addressData['type'],
                    ]);
                } else {
                    Address::create([
                        'user_id' => $user->id,
                        'address_line1' => $addressData['address_line1'],
                        'address_line2' => $addressData['address_line2'] ?? null,
                        'pincode' => $addressData['pincode'],
                        'city' => $addressData['city'],
                        'state' => $addressData['state'],
                        'type' => $addressData['type'],
                    ]);
                }
            }
        }

        $user->load('address');

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }
}
