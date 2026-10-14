<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    public function test_billing_overview_is_available_to_guests(): void
    {
        $this->get(route('billing.index'))
            ->assertRedirect(route('profile.show'));
        $this->get(route('billing.orders'))->assertOk()
            ->assertSee('Login untuk melihat')
            ->assertDontSee('>Riwayat Pembayaran</h2>', false);
    }

    public function test_services_overview_links_to_available_customer_features(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Layanan Pesanan')
            ->assertSee('Lacak Pesanan')
            ->assertSee(route('tracking.index'), false)
            ->assertDontSee('>Order</span>', false)
            ->assertSee(route('orders.history'), false);
    }

    public function test_tracking_search_is_separate_from_the_landing_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="tracking-query"', false)
            ->assertSee(route('tracking.index'), false);

        $this->get(route('tracking.index'))
            ->assertOk()
            ->assertSee('Cari pesananmu.')
            ->assertSee('TRACKING PESANAN')
            ->assertDontSee('tracking-trust-section', false)
            ->assertSee('id="tracking-query"', false)
            ->assertDontSee('Group order untuk girl group');
    }
}
