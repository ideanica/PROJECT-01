<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfileEditTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $member = Member::factory()->create(['address' => 'Alamat lama', 'notes' => 'Catatan admin rahasia']);

        return User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
    }

    private function data(): array
    {
        return ['display_name' => 'Nama Baru', 'username' => 'customer.baru', 'email' => 'baru@example.test',
            'phone' => '+62 8123456789', 'address' => 'Alamat baru'];
    }

    public function test_customer_can_edit_own_profile_and_login_identity_stays_synchronized(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee(route('profile.detail'), false);
        $this->get(route('profile.edit'))->assertOk()->assertSee('Alamat lama')->assertDontSee('Catatan admin rahasia')->assertDontSee('Kode customer');
        $this->put(route('profile.update'), $this->data())->assertSessionHasNoErrors()->assertRedirect(route('profile.detail'));
        $this->assertDatabaseHas('members', $this->data() + ['id' => $user->member_id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'username' => 'customer.baru', 'email' => 'baru@example.test']);
        $this->assertSame('Catatan admin rahasia', $user->member->fresh()->notes);
        $this->post('/logout');
        $this->post('/login', ['username' => 'customer.baru', 'password' => 'password'])->assertRedirect(route('profile.show'));
    }

    public function test_profile_details_are_readonly_and_editing_requires_following_edit_link(): void
    {
        $user = $this->customer();
        $this->get(route('profile.detail'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('profile.detail'))->assertOk()
            ->assertSee('Alamat lama')->assertSee('Username LINE')->assertDontSee('Username akun')->assertDontSee('Username / ID LINE')->assertSee(route('profile.edit'), false)
            ->assertDontSee('<form', false)->assertDontSee('type="file"', false)
            ->assertDontSee('Catatan admin rahasia')->assertDontSee('Kode customer');
        $this->get(route('profile.edit'))->assertOk()->assertSee('name="profile_photo"', false)->assertSee('Username LINE')->assertDontSee('name="line_username"', false);
        $this->actingAs(User::factory()->create())->get(route('profile.detail'))->assertOk()
            ->assertSee('Email login')->assertDontSee('Alamat lengkap')->assertDontSee('<form', false);
    }

    public function test_address_change_preserves_shipping_address_but_not_future_order_address(): void
    {
        $user = $this->customer();
        $shipping = OrderStatus::factory()->create(['code' => 'dikirim-ke-customer', 'is_final' => false]);
        $batch = Batch::factory()->create(['current_status_id' => $shipping->id]);
        $order = MemberOrder::factory()->create(['member_id' => $user->member_id, 'batch_id' => $batch->id]);
        $pendingStatus = OrderStatus::factory()->create(['code' => 'pending-test']);
        $pending = MemberOrder::factory()->create(['member_id' => $user->member_id, 'batch_id' => Batch::factory()->create(['current_status_id' => $shipping->id])->id, 'override_status_id' => $pendingStatus->id]);
        $otherUser = $this->customer();
        $otherOrder = MemberOrder::factory()->create(['member_id' => $otherUser->member_id, 'batch_id' => $batch->id]);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('1 pesanan sedang dalam pengiriman');
        $this->put(route('profile.update'), $this->data())->assertSessionHasNoErrors()->assertSessionHas('status', fn ($status) => str_contains($status, 'alamat sebelumnya'));
        $this->assertSame('Alamat lama', $order->fresh()->shipping_address);
        $this->assertNull($pending->fresh()->shipping_address);
        $this->assertNull($otherOrder->fresh()->shipping_address);
        $this->put(route('profile.update'), array_replace($this->data(), ['address' => 'Alamat ketiga']))->assertSessionHasNoErrors();
        $this->assertSame('Alamat lama', $order->fresh()->shipping_address);

        $this->actingAs(User::factory()->create())->get(route('admin.member-orders.show', $order))->assertOk()->assertSee('Alamat lama');
    }

    public function test_customer_cannot_claim_other_identity_or_change_admin_owned_fields(): void
    {
        $user = $this->customer();
        User::factory()->create(['username' => 'taken.user', 'email' => 'taken@example.test']);
        $this->actingAs($user)->put(route('profile.update'), array_replace($this->data(), ['username' => ' TAKEN.USER ', 'email' => 'taken@example.test']))
            ->assertSessionHasErrors(['username', 'email']);
        $group = CustomerGroup::create(['name' => 'Restricted Group']);
        $this->put(route('profile.update'), $this->data() + ['role' => 'admin', 'customer_group_id' => $group->id, 'notes' => 'Changed'])
            ->assertSessionHasErrors(['role', 'customer_group_id', 'notes']);
        $this->assertSame('Alamat lama', $user->member->fresh()->address);
    }

    public function test_guests_and_admin_cannot_edit_customer_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->put(route('profile.update'), $this->data())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk()->assertSee('Profil admin')->assertDontSee('name="address"', false);
        $this->put(route('profile.update'), $this->data())->assertForbidden();
    }

    public function test_customer_can_upload_replace_and_remove_own_profile_photo(): void
    {
        Storage::fake('local');
        $user = $this->customer();
        $this->actingAs($user)->put(route('profile.update'), $this->data() + ['profile_photo' => UploadedFile::fake()->image('avatar.png')])
            ->assertSessionHasNoErrors();
        $member = $user->member->fresh();
        $oldPath = $member->profile_photo_path;
        Storage::disk('local')->assertExists($oldPath);
        $this->assertSame($member->avatar_url, $user->fresh()->avatar_url);
        $this->actingAs($user->fresh())->get(route('profile.show'))->assertOk()->assertSee($member->avatar_url, false);
        $this->get(route('profile.photo', $member))->assertOk()->assertHeader('Content-Type', 'image/png');

        $this->put(route('profile.update'), $this->data() + ['profile_photo' => UploadedFile::fake()->image('replacement.jpg')])
            ->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($oldPath);
        $newPath = $member->fresh()->profile_photo_path;
        Storage::disk('local')->assertExists($newPath);

        $this->put(route('profile.update'), $this->data() + ['remove_photo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($newPath);
        $this->assertNull($member->fresh()->avatar_url);
        $this->assertNull($user->fresh()->avatar_url);
        $this->get(route('profile.photo', $member))->assertNotFound();
    }

    public function test_photo_access_is_restricted_to_owner_and_admin(): void
    {
        Storage::fake('local');
        $user = $this->customer();
        $this->actingAs($user)->put(route('profile.update'), $this->data() + ['profile_photo' => UploadedFile::fake()->image('avatar.png')])->assertSessionHasNoErrors();
        $photoUrl = route('profile.photo', $user->member);
        $this->actingAs($this->customer())->get($photoUrl)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($photoUrl)->assertOk();
        $this->post('/logout');
        $this->get($photoUrl)->assertRedirect(route('login'));
    }

    public function test_invalid_or_oversized_photos_do_not_change_profile(): void
    {
        Storage::fake('local');
        $user = $this->customer();
        $this->actingAs($user);
        foreach ([UploadedFile::fake()->create('avatar.txt', 1, 'text/plain'), UploadedFile::fake()->image('avatar.png')->size(2049)] as $photo) {
            $this->put(route('profile.update'), $this->data() + ['profile_photo' => $photo])->assertSessionHasErrors('profile_photo');
        }
        $this->assertSame('Alamat lama', $user->member->fresh()->address);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-photos'));
    }

    public function test_new_photo_is_removed_when_database_transaction_fails(): void
    {
        Storage::fake('local');
        $user = $this->customer();
        $this->mock(CustomerAccountService::class, fn ($mock) => $mock->shouldReceive('sync')->once()->andThrow(new \RuntimeException('Simulated save failure')));
        $this->actingAs($user)->put(route('profile.update'), $this->data() + ['profile_photo' => UploadedFile::fake()->image('avatar.png')])->assertStatus(500);
        $this->assertNull($user->member->fresh()->profile_photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-photos'));
    }
}
