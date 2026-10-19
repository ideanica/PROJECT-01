<?php

namespace Tests\Feature;

use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDirectoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_search_and_status_work_together_and_survive_pagination(): void
    {
        foreach (range(1, 16) as $number) {
            CustomerGroup::create(['name' => "Matching {$number}", 'description' => 'Album buyers', 'color' => '#355a45', 'is_active' => false]);
        }
        CustomerGroup::create(['name' => 'Active match', 'description' => 'Album buyers', 'color' => '#355a45', 'is_active' => true]);
        CustomerGroup::create(['name' => 'Unrelated', 'color' => '#355a45', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.customer-groups.index', ['q' => 'Album', 'status' => 'inactive']))
            ->assertOk()
            ->assertViewHas('groups', fn ($groups) => $groups->total() === 16 && $groups->every(fn ($group) => ! $group->is_active))
            ->assertSee('q=Album', false)
            ->assertSee('status=inactive', false)
            ->assertDontSee('Active match')
            ->assertDontSee('Unrelated');
    }

    public function test_customer_filters_preserve_the_pending_registration_folder(): void
    {
        $group = CustomerGroup::create(['name' => 'Selected Group', 'color' => '#355a45', 'is_active' => true]);
        $pending = Member::factory()->create(['display_name' => 'Pending Match', 'customer_group_id' => $group->id, 'is_active' => false]);
        User::factory()->create(['role' => 'customer', 'member_id' => $pending->id, 'registration_pending' => true]);
        Member::factory()->create(['display_name' => 'Active Match', 'customer_group_id' => $group->id, 'is_active' => true]);
        Member::factory()->create(['display_name' => 'Other Match', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.members.index', ['q' => 'Match', 'customer_group_id' => $group->id, 'status' => 'inactive', 'registration' => 'pending']))
            ->assertOk()
            ->assertViewHas('members', fn ($members) => $members->total() === 1 && $members->first()->is($pending))
            ->assertSee('name="registration" value="pending"', false)
            ->assertSee('Pending Match')
            ->assertDontSee('Active Match')
            ->assertDontSee('Other Match');
    }
}
