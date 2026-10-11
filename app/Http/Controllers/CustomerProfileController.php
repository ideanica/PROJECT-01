<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Models\Member;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerProfileController extends Controller
{
    public function edit(Request $request)
    {
        return $this->profilePage($request, true);
    }

    public function detail(Request $request)
    {
        return $this->profilePage($request, false);
    }

    private function profilePage(Request $request, bool $editing)
    {
        if ($request->user()->isAdmin()) {
            return view($editing ? 'profile.admin-edit' : 'profile.detail', ['user' => $request->user(), 'member' => null]);
        }
        abort_unless($request->user()->role === 'customer' && $request->user()->member?->isEligibleForNewOrder(), 403);
        $member = $request->user()->member->load('customerGroup');
        $shippingCount = $member->orders()->with(['batch.currentStatus', 'overrideStatus', 'paymentStatus'])->get()
            ->filter(fn ($order) => ! $order->is_refunded && $order->effective_status?->code === 'dikirim-ke-customer')->count();

        return view($editing ? 'profile.edit' : 'profile.detail', ['user' => $request->user(), 'member' => $member, 'shippingCount' => $shippingCount]);
    }

    public function update(UpdateCustomerProfileRequest $request, CustomerAccountService $accounts)
    {
        $newPhotoPath = null;
        $oldPhotoPath = null;
        try {
            if ($request->hasFile('profile_photo')) {
                try {
                    $newPhotoPath = $request->file('profile_photo')->store('profile-photos/'.$request->user()->member_id, 'local');
                } catch (Throwable $exception) {
                    report($exception);
                    throw ValidationException::withMessages(['profile_photo' => 'Foto gagal disimpan. Silakan coba lagi.']);
                }
                if (! $newPhotoPath) {
                    throw ValidationException::withMessages(['profile_photo' => 'Foto gagal disimpan. Silakan coba lagi.']);
                }
            }
            $shippingAddressPreserved = DB::transaction(function () use ($request, $accounts, $newPhotoPath, &$oldPhotoPath): bool {
                $member = Member::whereKey($request->user()->member_id)->lockForUpdate()->firstOrFail();
                $data = Arr::except($request->validated(), ['profile_photo', 'remove_photo']);
                $preserved = false;

                if ($data['address'] !== $member->address) {
                    $orders = $member->orders()->with(['batch.currentStatus', 'overrideStatus', 'paymentStatus'])->lockForUpdate()->get();
                    foreach ($orders as $order) {
                        if (! $order->is_refunded && $order->effective_status?->code === 'dikirim-ke-customer') {
                            // Preserve the delivery destination even when the previous profile address was empty.
                            if ($order->shipping_address === null) {
                                $order->shipping_address = $member->address ?? '';
                                $order->save();
                            }
                            $preserved = true;
                        }
                    }
                }

                $member->update($data);
                if ($newPhotoPath || $request->boolean('remove_photo')) {
                    $oldPhotoPath = $member->profile_photo_path;
                    $member->profile_photo_path = $newPhotoPath;
                    $member->avatar_url = $newPhotoPath ? route('profile.photo', $member, false) : null;
                    $member->save();
                    $member->user->update(['avatar_url' => $member->avatar_url]);
                }
                $accounts->sync($member);

                return $preserved;
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }
            throw $exception;
        }
        if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return redirect()->route('profile.detail')->with('status', 'Profil berhasil diperbarui.'.($shippingAddressPreserved
            ? ' Alamat pesanan yang sedang dikirim tetap menggunakan alamat sebelumnya. Hubungi admin jika ingin mengubah tujuan pengiriman.'
            : ''));
    }

    public function photo(Request $request, Member $member)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->member_id === $member->id, 403);
        abort_unless($member->profile_photo_path && Storage::disk('local')->exists($member->profile_photo_path), 404);

        return Storage::disk('local')->response($member->profile_photo_path, null, ['Cache-Control' => 'private, no-store']);
    }
}
