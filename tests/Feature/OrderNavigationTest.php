<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_links_keep_the_current_filtered_page_as_the_return_destination(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
        MemberOrder::factory()->create(['member_id' => $member->id, 'total_amount' => 100000]);

        foreach (['/order-history', '/orders/unpaid', '/billing/orders'] as $path) {
            $response = $this->actingAs($user)->get($path)->assertOk();
            $this->assertMatchesRegularExpression('/return_to='.preg_quote(urlencode($path), '/').'/', $response->getContent());
        }
    }

    public function test_order_detail_returns_to_its_signed_source_including_filters(): void
    {
        $order = MemberOrder::factory()->create();
        $url = URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), [
            'memberCode' => $order->member->member_code,
            'memberOrder' => $order,
            'return_to' => '/order-history?filter=active',
        ]);

        $this->get($url)->assertOk()->assertViewHas('backUrl', '/order-history?filter=active');
        $this->get($url.'&return_to=//example.com')->assertForbidden();
    }

    public function test_order_detail_uses_a_same_origin_referrer_when_opened_from_an_existing_link(): void
    {
        $order = MemberOrder::factory()->create();
        $url = URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), [
            'memberCode' => $order->member->member_code, 'memberOrder' => $order,
        ]);

        $this->withHeader('referer', url('/billing/orders?tab=paid'))
            ->get($url)->assertOk()->assertViewHas('backUrl', '/billing/orders?tab=paid');
    }

    public function test_order_detail_rejects_external_return_destinations(): void
    {
        $order = MemberOrder::factory()->create();

        foreach (['https://example.com', '//example.com', '/\\example.com'] as $returnTo) {
            $url = URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), [
                'memberCode' => $order->member->member_code, 'memberOrder' => $order,
                'return_to' => $returnTo,
            ]);
            $this->withHeader('referer', 'https://example.com')
                ->get($url)->assertOk()->assertViewHas('backUrl', route('tracking.index'));
        }
    }
}
