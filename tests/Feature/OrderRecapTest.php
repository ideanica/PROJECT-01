<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\LegacySpreadsheetImportService;
use App\Services\StatusTransitionService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_status(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.order-statuses.store'), [
            'name' => 'Menunggu Pelunasan',
            'color' => '#2563eb',
            'status_type' => 'process',
            'scope' => 'member_order',
            'is_active' => '1',
        ]);

        $status = OrderStatus::where('code', 'menunggu-pelunasan')->sole();

        $response->assertRedirect(route('admin.order-statuses.show', $status));
        $this->assertSame(10, $status->sequence);
    }

    public function test_used_status_is_deactivated_instead_of_deleted(): void
    {
        $admin = User::factory()->create();
        $status = OrderStatus::factory()->create(['scope' => 'batch']);
        Batch::factory()->create(['current_status_id' => $status->id]);

        $this->actingAs($admin)->delete(route('admin.order-statuses.destroy', $status))->assertRedirect();

        $this->assertDatabaseHas('order_statuses', ['id' => $status->id, 'is_active' => false]);
    }

    public function test_order_override_status_is_only_available_after_order_is_created(): void
    {
        $admin = User::factory()->create();
        $activeStatus = OrderStatus::factory()->create(['name' => 'Status Aktif', 'scope' => 'member_order', 'is_active' => true]);
        OrderStatus::factory()->create(['name' => 'Status Nonaktif', 'scope' => 'member_order', 'is_active' => false]);
        $order = MemberOrder::factory()->create();

        $this->actingAs($admin)->get(route('admin.member-orders.create'))
            ->assertOk()
            ->assertDontSee('name="override_status_id"', false)
            ->assertDontSee('Status Aktif')
            ->assertDontSee('Status Nonaktif');

        $this->actingAs($admin)->get(route('admin.member-orders.show', $order))
            ->assertOk()
            ->assertSee('Status Khusus Pesanan')
            ->assertSee('Pilih status khusus')
            ->assertDontSee('Hapus status khusus')
            ->assertSee('Status Aktif')
            ->assertDontSee('Status Nonaktif');

        $this->actingAs($admin)->post(route('admin.member-orders.status', $order), [
            'status_id' => $activeStatus->id,
            'note' => 'Pesanan perlu progress berbeda.',
        ])->assertRedirect();

        $this->assertDatabaseHas('member_orders', [
            'id' => $order->id,
            'override_status_id' => $activeStatus->id,
        ]);

        $this->actingAs($admin)->get(route('admin.member-orders.show', $order->fresh()))
            ->assertOk()
            ->assertSee('Status khusus pada pesanan')
            ->assertSee('Status Aktif')
            ->assertDontSee('order-special-status-list', false)
            ->assertSee('Hapus status khusus')
            ->assertDontSee('Pilih status khusus')
            ->assertDontSee('Tidak ada status khusus');
    }

    public function test_order_edit_keeps_its_inactive_payment_status_available(): void
    {
        $admin = User::factory()->create();
        $inactivePaymentStatus = OrderStatus::factory()->create([
            'name' => 'Pembayaran Lama',
            'scope' => 'payment',
            'is_active' => false,
        ]);
        $order = MemberOrder::factory()->create(['payment_status_id' => $inactivePaymentStatus->id]);

        $this->actingAs($admin)
            ->get(route('admin.member-orders.edit', $order))
            ->assertOk()
            ->assertSee('Pembayaran Lama');
    }

    public function test_batch_detail_uses_orders_instead_of_manual_member_attachment(): void
    {
        $admin = User::factory()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.batches.show', $batch))
            ->assertOk()
            ->assertSee('Master Batch Pembelian')
            ->assertDontSee('Tambah Pesanan')
            ->assertSee('data-status-modal-open="batch-progress"', false)
            ->assertSee('data-status-modal="batch-progress"', false)
            ->assertSee('Belum ada riwayat perubahan progress untuk batch ini.')
            ->assertDontSee('Tambahkan Member ke Batch');
    }

    public function test_batch_filter_is_marked_for_automatic_submission(): void
    {
        $admin = User::factory()->create();
        Batch::factory()->create(['batch_name' => 'Batch Operasional', 'is_archived' => false]);
        Batch::factory()->create(['batch_name' => 'Batch Lama Arsip', 'is_archived' => true]);

        $this->actingAs($admin)
            ->get(route('admin.batches.index'))
            ->assertOk()
            ->assertSee('data-auto-filter', false)
            ->assertSee('Batch Aktif')
            ->assertSee('Arsip')
            ->assertSee('Batch Operasional')
            ->assertDontSee('Batch Lama Arsip')
            ->assertDontSee('>Filter</button>', false);

        $this->actingAs($admin)
            ->get(route('admin.batches.index', ['view' => 'archived']))
            ->assertOk()
            ->assertSee('Batch Lama Arsip')
            ->assertDontSee('Batch Operasional')
            ->assertSee('name="view" value="archived"', false);
    }

    public function test_batch_number_is_generated_automatically(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)->get(route('admin.batches.create'))
            ->assertOk()
            ->assertDontSee('Catatan perubahan status')
            ->assertDontSee('Pengarsipan')
            ->assertDontSee('Arsipkan batch');

        $this->actingAs($admin)->post(route('admin.batches.store'), [
            'batch_name' => 'Batch Arsip Paksa',
            'product_ids' => [$product->id],
            'is_archived' => '1',
        ])->assertSessionHasErrors('is_archived');

        $this->actingAs($admin)->post(route('admin.batches.store'), [
            'batch_name' => 'Batch Otomatis',
            'started_at' => now()->format('Y-m-d H:i:s'),
            'product_ids' => [$product->id],
        ])->assertRedirect();

        $batch = Batch::where('batch_name', 'Batch Otomatis')->sole();

        $this->assertMatchesRegularExpression('/^BTH-\d{4}-\d{4}$/', $batch->batch_number);
        $this->assertFalse($batch->is_archived);
    }

    public function test_status_categories_are_available_as_folder_tabs_on_one_page(): void
    {
        $admin = User::factory()->create();
        OrderStatus::factory()->create(['name' => 'Status Batch Folder', 'scope' => 'batch']);
        OrderStatus::factory()->create(['name' => 'Status Payment Folder', 'scope' => 'payment']);

        $this->actingAs($admin)
            ->get(route('admin.order-statuses.index', ['scope' => 'batch']))
            ->assertOk()
            ->assertSee('data-status-folder-tab="tracking"', false)
            ->assertSee('data-status-folder-tab="payment"', false)
            ->assertDontSee('data-status-folder-tab="groups"', false)
            ->assertDontSee('data-status-folder-tab="member_order"', false)
            ->assertDontSee('data-status-folder-tab="order_item"', false)
            ->assertSee('Tracking Perjalanan')
            ->assertSee('Status Pembayaran')
            ->assertSee('<th>No.</th>', false)
            ->assertSee('Status Batch Folder')
            ->assertSee('Status Payment Folder');
    }

    public function test_status_display_position_is_assigned_automatically(): void
    {
        $admin = User::factory()->create();
        OrderStatus::factory()->create(['scope' => 'member_order', 'sequence' => 70]);

        $this->actingAs($admin)
            ->get(route('admin.order-statuses.create'))
            ->assertOk()
            ->assertDontSee('data-status-code-preview', false)
            ->assertDontSee('name="code"', false)
            ->assertDontSee('name="sequence"', false)
            ->assertDontSee('Nomor urutan')
            ->assertSee('Identitas dan Tampilan Status')
            ->assertSee('status-form-primary-row', false)
            ->assertSee('lg:grid-cols-3', false)
            ->assertSee('Cakupan dan Keterangan Status')
            ->assertDontSee('lg:border-l', false)
            ->assertSee('md:grid-cols-4', false);

        $this->actingAs($admin)->post(route('admin.order-statuses.store'), [
            'name' => 'Status Otomatis',
            'color' => '#2563eb',
            'sequence' => 999,
            'status_type' => 'process',
            'scope' => 'member_order',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('order_statuses', [
            'code' => 'status-otomatis',
            'sequence' => 80,
        ]);
    }

    public function test_status_code_is_generated_and_cannot_be_overridden_by_admin(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.order-statuses.store'), [
            'name' => 'Status Pengujian',
            'code' => 'kode-paksa',
            'color' => '#2563eb',
            'status_type' => 'process',
            'scope' => 'batch',
            'is_active' => '1',
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('order_statuses', ['code' => 'kode-paksa']);

        $this->actingAs($admin)->post(route('admin.order-statuses.store'), [
            'name' => 'Status Pengujian',
            'color' => '#2563eb',
            'status_type' => 'process',
            'scope' => 'batch',
            'is_active' => '1',
        ])->assertRedirect();

        $status = OrderStatus::where('code', 'status-pengujian')->sole();

        $this->actingAs($admin)->put(route('admin.order-statuses.update', $status), [
            'name' => 'Nama Status Diubah',
            'color' => '#7c3aed',
            'status_type' => 'process',
            'scope' => 'batch',
            'is_active' => '1',
        ])->assertRedirect(route('admin.order-statuses.show', $status));

        $this->assertSame('status-pengujian', $status->refresh()->code);
    }

    public function test_default_statuses_are_named_and_grouped_for_the_feature_that_uses_them(): void
    {
        $this->seed(OrderStatusSeeder::class);

        $this->assertDatabaseHas('order_statuses', [
            'code' => 'sudah-dipesan',
            'name' => 'Sudah Dipesan ke Supplier',
            'scope' => 'batch',
            'locks_order_editing' => true,
        ]);
        $this->assertDatabaseHas('order_statuses', [
            'code' => 'secured',
            'name' => 'Pesanan Berhasil Diamankan',
            'scope' => 'member_order',
        ]);
        $this->assertDatabaseHas('order_statuses', [
            'code' => 'barang-rusak',
            'name' => 'Item Rusak',
            'scope' => 'order_item',
        ]);
        $this->assertDatabaseHas('order_statuses', [
            'code' => 'lunas',
            'name' => 'Pembayaran Lunas',
            'scope' => 'payment',
        ]);
    }

    public function test_status_detail_and_edit_are_available_as_separate_pages(): void
    {
        $admin = User::factory()->create();
        $status = OrderStatus::factory()->create(['name' => 'Status Detail', 'scope' => 'batch']);
        Batch::factory()->create(['current_status_id' => $status->id]);

        $this->actingAs($admin)
            ->get(route('admin.order-statuses.index', ['scope' => 'batch']))
            ->assertOk()
            ->assertSee('Status Detail')
            ->assertDontSee('data-status-modal', false)
            ->assertDontSee('status-modal-surface', false);

        $this->actingAs($admin)
            ->get(route('admin.order-statuses.show', $status))
            ->assertOk()
            ->assertSee('Detail Status')
            ->assertSee('detail-record-field-violet', false)
            ->assertSee('detail-record-field-blue', false)
            ->assertSee('detail-record-field-cyan', false)
            ->assertSee('detail-record-field-amber', false)
            ->assertSee('Edit Status')
            ->assertSee('Nonaktifkan Status');

        $this->actingAs($admin)
            ->get(route('admin.order-statuses.edit', $status))
            ->assertOk()
            ->assertSee('Edit Status')
            ->assertDontSee('data-status-code-preview', false)
            ->assertDontSee('name="code"', false);
    }

    public function test_admin_can_select_managed_payment_status_for_order(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        $product = Product::factory()->create();
        $batch->products()->attach($product);
        $paymentStatus = OrderStatus::factory()->create([
            'name' => 'Lunas',
            'scope' => 'payment',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'payment_status_id' => $paymentStatus->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ])->assertRedirect();

        $order = MemberOrder::whereBelongsTo($member)->whereBelongsTo($batch)->sole();

        $this->assertSame($paymentStatus->id, $order->payment_status_id);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{6}$/', $order->order_code);
    }

    public function test_progress_status_cannot_be_used_as_payment_status(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        $product = Product::factory()->create();
        $batch->products()->attach($product);
        $progressStatus = OrderStatus::factory()->create([
            'scope' => 'member_order',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'payment_status_id' => $progressStatus->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ])->assertSessionHasErrors('payment_status_id');

        $this->assertDatabaseMissing('member_orders', [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
        ]);
    }

    public function test_refund_status_is_not_available_when_creating_order(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        $product = Product::factory()->create();
        $batch->products()->attach($product);
        $refund = OrderStatus::factory()->create([
            'name' => 'Refund',
            'code' => 'refund',
            'scope' => 'payment',
            'status_type' => 'failed',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.member-orders.create'))
            ->assertOk()
            ->assertDontSee('Refund');

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'payment_status_id' => $refund->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ])->assertSessionHasErrors('payment_status_id');
    }

    public function test_order_can_only_use_products_assigned_to_selected_batch(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        $allowedProduct = Product::factory()->create(['name' => 'Produk Batch']);
        $otherProduct = Product::factory()->create(['name' => 'Produk Lain']);
        $batch->products()->attach($allowedProduct);

        $this->actingAs($admin)
            ->get(route('admin.member-orders.create', ['batch_id' => $batch->id]))
            ->assertOk()
            ->assertSee('Produk Batch')
            ->assertDontSee('Produk Lain');

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'items' => [[
                'product_id' => $otherProduct->id,
                'quantity' => 1,
            ]],
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_status_transition_creates_history(): void
    {
        $admin = User::factory()->create();
        $old = OrderStatus::factory()->create(['scope' => 'batch']);
        $new = OrderStatus::factory()->create(['scope' => 'batch']);
        $batch = Batch::factory()->create(['current_status_id' => $old->id]);

        app(StatusTransitionService::class)->transition($batch, $new, $admin, 'Tiba di gudang.');

        $this->assertDatabaseHas('status_histories', [
            'trackable_type' => Batch::class,
            'trackable_id' => $batch->id,
            'old_status_id' => $old->id,
            'new_status_id' => $new->id,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_member_order_follows_batch_status_by_default(): void
    {
        $status = OrderStatus::factory()->create(['scope' => 'batch']);
        $batch = Batch::factory()->create(['current_status_id' => $status->id]);
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id, 'override_status_id' => null]);

        $order->load('batch.currentStatus', 'overrideStatus');

        $this->assertTrue($order->effective_status->is($status));
    }

    public function test_member_order_override_replaces_batch_status(): void
    {
        $batchStatus = OrderStatus::factory()->create(['scope' => 'batch']);
        $override = OrderStatus::factory()->create(['scope' => 'member_order']);
        $batch = Batch::factory()->create(['current_status_id' => $batchStatus->id]);
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id, 'override_status_id' => $override->id]);

        $order->load('batch.currentStatus', 'overrideStatus');

        $this->assertTrue($order->effective_status->is($override));
    }

    public function test_item_override_only_affects_that_item(): void
    {
        $batchStatus = OrderStatus::factory()->create(['scope' => 'batch']);
        $itemStatus = OrderStatus::factory()->create(['scope' => 'order_item']);
        $batch = Batch::factory()->create(['current_status_id' => $batchStatus->id]);
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id]);
        $specialItem = OrderItem::factory()->create(['member_order_id' => $order->id, 'override_status_id' => $itemStatus->id]);
        $normalItem = OrderItem::factory()->create(['member_order_id' => $order->id, 'override_status_id' => null]);

        $specialItem->load('overrideStatus', 'order.batch.currentStatus', 'order.overrideStatus');
        $normalItem->load('overrideStatus', 'order.batch.currentStatus', 'order.overrideStatus');

        $this->assertTrue($specialItem->effective_status->is($itemStatus));
        $this->assertTrue($normalItem->effective_status->is($batchStatus));
    }

    public function test_clearing_override_returns_to_parent_status(): void
    {
        $admin = User::factory()->create();
        $batchStatus = OrderStatus::factory()->create(['scope' => 'batch']);
        $override = OrderStatus::factory()->create(['scope' => 'member_order']);
        $batch = Batch::factory()->create(['current_status_id' => $batchStatus->id]);
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id, 'override_status_id' => $override->id]);

        app(StatusTransitionService::class)->clearOverride($order, $admin);
        $order->load('batch.currentStatus', 'overrideStatus');

        $this->assertTrue($order->effective_status->is($batchStatus));
    }

    public function test_member_cannot_view_other_member_order(): void
    {
        $memberA = Member::factory()->create();
        $memberB = Member::factory()->create();
        $orderB = MemberOrder::factory()->create(['member_id' => $memberB->id]);

        $url = URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), [
            'memberCode' => $memberA->member_code,
            'memberOrder' => $orderB,
        ]);

        $this->get($url)->assertForbidden();
    }

    public function test_public_can_track_one_order_with_order_code(): void
    {
        $order = MemberOrder::factory()->create(['order_code' => 'ORD-PUBLIC-001']);
        OrderItem::factory()->create(['member_order_id' => $order->id, 'item_name' => 'NCT Wish Album']);

        $response = $this->post(route('tracking.lookup'), ['lookup' => 'ORD-PUBLIC-001'])
            ->assertRedirect();
        $signedUrl = $response->headers->get('Location');
        $this->assertStringContainsString('signature=', $signedUrl);

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee('ORD-PUBLIC-001')
            ->assertSee('NCT Wish Album')
            ->assertDontSee($order->member->email);
    }

    public function test_smart_search_shows_tracking_result_inline_for_order_code(): void
    {
        $order = MemberOrder::factory()->create(['order_code' => 'ORD-SMART-001']);
        OrderItem::factory()->create(['member_order_id' => $order->id, 'item_name' => 'Ocean Lightstick']);

        $this->post(route('tracking.search'), ['query' => 'ord-smart-001'])
            ->assertOk()
            ->assertSee('public-landing-main', false)
            ->assertSee('data-smart-search-result="tracking"', false)
            ->assertSee('ORD-SMART-001')
            ->assertSee('Ocean Lightstick');
    }

    public function test_customer_can_find_all_order_history_with_registered_line_username(): void
    {
        $member = Member::factory()->create(['username' => 'buyer.line']);
        $order = MemberOrder::factory()->create([
            'member_id' => $member->id,
            'order_code' => 'ORD-HISTORY-001',
        ]);

        $response = $this->post(route('tracking.history.lookup'), ['username' => ' Buyer.Line '])
            ->assertRedirect();
        $signedUrl = $response->headers->get('Location');
        $this->assertStringContainsString('signature=', $signedUrl);

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee('Riwayat Pembelian')
            ->assertSee($order->order_code);
    }

    public function test_smart_search_shows_order_history_inline_for_line_username(): void
    {
        $member = Member::factory()->create([
            'display_name' => 'Ocean Buyer',
            'username' => 'ocean.line',
        ]);
        MemberOrder::factory()->create([
            'member_id' => $member->id,
            'order_code' => 'ORD-OCEAN-001',
        ]);

        $this->post(route('tracking.search'), ['query' => ' @Ocean.Line '])
            ->assertOk()
            ->assertSee('public-landing-main', false)
            ->assertSee('data-smart-search-result="history"', false)
            ->assertSee('Ocean Buyer')
            ->assertSee('ORD-OCEAN-001');
    }

    public function test_unregistered_line_username_does_not_show_history(): void
    {
        $this->from(route('tracking.index'))
            ->post(route('tracking.history.lookup'), ['username' => 'missing.line'])
            ->assertRedirect(route('tracking.index'))
            ->assertSessionHasErrors('username');
    }

    public function test_admin_can_create_customer_contact_data(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'display_name' => 'Kim Buyer',
            'username' => 'Kim.Line',
            'phone' => '081234567890',
            'address' => 'Jl. Kpop No. 7, Jakarta',
        ])->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseHas('members', [
            'display_name' => 'Kim Buyer',
            'username' => 'kim.line',
            'phone' => '081234567890',
            'address' => 'Jl. Kpop No. 7, Jakarta',
        ]);

        $member = Member::where('username', 'kim.line')->sole();
        $this->assertNotNull($member->member_code);

        $this->actingAs($admin)->get(route('admin.members.edit', $member))
            ->assertOk()
            ->assertSee('name="is_active"', false)
            ->assertSee('Kode pelanggan')
            ->assertSee('readonly', false)
            ->assertDontSee('Kode internal');
    }

    public function test_order_form_hydrates_item_from_selected_product(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Wish Bakery Plush',
            'variant' => 'Sakuya',
            'default_price' => 395000,
        ]);
        $batch->products()->attach($product);

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => '',
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'item_name' => 'Wish Bakery Plush',
            'variant' => 'Sakuya',
            'quantity' => 2,
            'unit_price' => 395000,
            'subtotal' => 790000,
        ]);
    }

    public function test_order_code_is_generated_by_system_and_cannot_be_changed(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        $product = Product::factory()->create();
        $batch->products()->attach($product);

        $this->actingAs($admin)
            ->get(route('admin.member-orders.create'))
            ->assertOk()
            ->assertSee('Kode pesanan otomatis')
            ->assertDontSee('name="order_code"', false)
            ->assertDontSee('Status item');

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ])->assertRedirect();

        $order = MemberOrder::whereBelongsTo($member)->whereBelongsTo($batch)->sole();
        $originalCode = $order->order_code;

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{6}$/', $originalCode);

        $this->actingAs($admin)->put(route('admin.member-orders.update', $order), [
            'order_code' => 'ORD-DIUBAH-PAKSA',
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'items' => [[
                'id' => $order->items()->sole()->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ])->assertSessionHasErrors('order_code');

        $this->assertSame($originalCode, $order->refresh()->order_code);
    }

    public function test_order_override_can_be_cleared_to_follow_batch_again(): void
    {
        $admin = User::factory()->create();
        $batchStatus = OrderStatus::factory()->create(['scope' => 'batch']);
        $overrideStatus = OrderStatus::factory()->create(['scope' => 'member_order']);
        $batch = Batch::factory()->create(['current_status_id' => $batchStatus->id]);
        $order = MemberOrder::factory()->create([
            'batch_id' => $batch->id,
            'override_status_id' => $overrideStatus->id,
        ]);

        $this->actingAs($admin)->post(route('admin.member-orders.status', $order), [
            'status_id' => '',
            'note' => 'Kembali mengikuti progress batch.',
        ])->assertRedirect();

        $this->assertNull($order->refresh()->override_status_id);
        $this->assertTrue($order->load('batch.currentStatus', 'overrideStatus')->effective_status->is($batchStatus));
    }

    public function test_processed_batch_locks_customer_batch_and_order_items(): void
    {
        $admin = User::factory()->create();
        $customer = Member::factory()->create();
        $otherCustomer = Member::factory()->create();
        $processingStatus = OrderStatus::factory()->create([
            'scope' => 'batch',
            'locks_order_editing' => true,
        ]);
        $batch = Batch::factory()->create(['current_status_id' => $processingStatus->id]);
        $product = Product::factory()->create();
        $batch->products()->attach($product);
        $order = MemberOrder::factory()->create([
            'member_id' => $customer->id,
            'batch_id' => $batch->id,
        ]);
        $item = OrderItem::factory()->create([
            'member_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.member-orders.edit', $order))
            ->assertOk()
            ->assertSee('Customer tidak dapat diganti')
            ->assertSee('Komposisi pesanan sudah dikunci')
            ->assertDontSee('data-items', false);

        $this->actingAs($admin)->put(route('admin.member-orders.update', $order), [
            'member_id' => $otherCustomer->id,
            'batch_id' => $batch->id,
            'items' => [[
                'id' => $item->id,
                'product_id' => $product->id,
                'quantity' => 9,
            ]],
        ])->assertSessionHasErrors(['member_id', 'items']);

        $this->assertSame($customer->id, $order->refresh()->member_id);
        $this->assertSame(1, $item->refresh()->quantity);
    }

    public function test_final_batch_status_locks_all_further_progress_changes(): void
    {
        $admin = User::factory()->create();
        $finalStatus = OrderStatus::factory()->create([
            'name' => 'Selesai Final',
            'scope' => 'batch',
            'is_final' => true,
        ]);
        $nextStatus = OrderStatus::factory()->create([
            'name' => 'Status Lanjutan',
            'scope' => 'batch',
            'is_final' => false,
        ]);
        $batch = Batch::factory()->create(['current_status_id' => $finalStatus->id]);
        $product = Product::factory()->create();
        $batch->products()->attach($product);

        $this->actingAs($admin)->get(route('admin.batches.show', $batch))
            ->assertOk()
            ->assertSee('Progress batch telah selesai')
            ->assertDontSee('data-status-modal-open="batch-progress"', false)
            ->assertDontSee('data-status-modal="batch-progress"', false);

        $this->actingAs($admin)->get(route('admin.batches.edit', $batch))
            ->assertOk()
            ->assertSee('admin-form-readonly', false)
            ->assertSee('Progress final tidak dapat diubah', false)
            ->assertDontSee('<select name="current_status_id">', false);

        $this->actingAs($admin)->post(route('admin.batches.status', $batch), [
            'status_id' => $nextStatus->id,
        ])->assertSessionHasErrors('status_id');

        $this->actingAs($admin)->put(route('admin.batches.update', $batch), [
            'batch_name' => $batch->batch_name,
            'current_status_id' => $nextStatus->id,
            'product_ids' => [$product->id],
        ])->assertSessionHasErrors('current_status_id');

        $this->assertSame($finalStatus->id, $batch->refresh()->current_status_id);
        $this->assertDatabaseCount('status_histories', 0);
    }

    public function test_archived_batch_is_readonly_and_cannot_be_updated(): void
    {
        $admin = User::factory()->create();
        $status = OrderStatus::factory()->create(['scope' => 'batch']);
        $nextStatus = OrderStatus::factory()->create(['scope' => 'batch']);
        $batch = Batch::factory()->create([
            'batch_name' => 'Batch Arsip Readonly',
            'current_status_id' => $status->id,
            'is_archived' => true,
        ]);
        $product = Product::factory()->create();
        $batch->products()->attach($product);

        $this->actingAs($admin)->get(route('admin.batches.show', $batch))
            ->assertOk()
            ->assertSee('Batch telah diarsipkan')
            ->assertDontSee('Edit Batch')
            ->assertDontSee('data-status-modal-open="batch-progress"', false)
            ->assertDontSee('data-status-modal="batch-progress"', false);

        $this->actingAs($admin)->get(route('admin.batches.edit', $batch))
            ->assertRedirect(route('admin.batches.show', $batch))
            ->assertSessionHasErrors('batch');

        $this->actingAs($admin)->put(route('admin.batches.update', $batch), [
            'batch_name' => 'Nama yang Dipaksakan',
            'current_status_id' => $status->id,
            'product_ids' => [$product->id],
        ])->assertRedirect(route('admin.batches.show', $batch))
            ->assertSessionHasErrors('batch');

        $this->actingAs($admin)->post(route('admin.batches.status', $batch), [
            'status_id' => $nextStatus->id,
        ])->assertSessionHasErrors('status_id');

        $this->assertSame('Batch Arsip Readonly', $batch->refresh()->batch_name);
        $this->assertSame($status->id, $batch->current_status_id);
        $this->assertDatabaseCount('status_histories', 0);
    }

    public function test_order_status_override_summary_and_form_are_available_on_detail(): void
    {
        $admin = User::factory()->create();
        $order = MemberOrder::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.member-orders.show', $order))
            ->assertOk()
            ->assertSee('Status asli dari batch saat ini')
            ->assertSee('Status khusus pada pesanan')
            ->assertSee('Tidak ada')
            ->assertSee('Ubah Status')
            ->assertDontSee('Sinkronisasi pesanan')
            ->assertDontSee('Riwayat status khusus')
            ->assertDontSee('order-special-status-list', false)
            ->assertSee('data-order-status-modal', false)
            ->assertSee('data-order-status-override-form', false)
            ->assertDontSee('data-order-status-override-toggle', false)
            ->assertDontSee('data-order-status-override-form hidden', false)
            ->assertSee('Kembali ke Pesanan');
    }

    public function test_updated_special_order_status_is_rendered_directly_in_the_second_card(): void
    {
        $admin = User::factory()->create();
        $batchStatus = OrderStatus::factory()->create([
            'name' => 'Progress Batch Berjalan',
            'scope' => 'batch',
        ]);
        $specialStatus = OrderStatus::factory()->create([
            'name' => 'Ditahan Khusus Customer',
            'scope' => 'member_order',
        ]);
        $batch = Batch::factory()->create(['current_status_id' => $batchStatus->id]);
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($admin)->post(route('admin.member-orders.status', $order), [
            'status_id' => $specialStatus->id,
            'note' => 'Menunggu konfirmasi customer.',
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('admin.member-orders.show', $order))
            ->assertOk()
            ->assertSee('Status asli dari batch saat ini')
            ->assertSee('Progress Batch Berjalan')
            ->assertSee('Status khusus pada pesanan')
            ->assertSee('Ditahan Khusus Customer')
            ->assertDontSee('order-special-status-list', false);
    }

    public function test_member_and_batch_combination_cannot_duplicate(): void
    {
        $member = Member::factory()->create();
        $batch = Batch::factory()->create();
        MemberOrder::factory()->create(['member_id' => $member->id, 'batch_id' => $batch->id]);

        $this->expectException(QueryException::class);
        MemberOrder::factory()->create(['member_id' => $member->id, 'batch_id' => $batch->id]);
    }

    public function test_spreadsheet_import_preview_carries_previous_member_name(): void
    {
        $preview = app(LegacySpreadsheetImportService::class)->preview([
            'A-E' => [
                ['NAMA', 'BATCH'],
                ['Fadhia', '1604'],
                ['', '1666'],
                ['', '1729'],
            ],
        ]);

        $this->assertSame(3, $preview['stats']['valid']);
        $this->assertSame('Fadhia', $preview['rows'][1]['member_name']);
        $this->assertSame('1666', $preview['rows'][1]['batch_number']);
    }

    public function test_spreadsheet_import_does_not_duplicate_rows(): void
    {
        $result = app(LegacySpreadsheetImportService::class)->import([
            'A-E' => [
                ['NAMA', 'BATCH'],
                ['Fadhia', '1604'],
                ['', '1604'],
            ],
        ]);

        $this->assertSame(1, $result['stats']['valid']);
        $this->assertSame(1, $result['stats']['duplicates']);
        $this->assertDatabaseCount('member_orders', 1);
    }

    public function test_only_admin_can_access_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_product_is_activated_automatically_and_status_is_managed_from_detail(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Album Otomatis Aktif',
            'variant' => 'Limited',
            'default_price' => 250000,
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Album Otomatis Aktif')->sole();
        $this->assertTrue($product->is_active);

        $this->actingAs($admin)->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('Edit Produk')
            ->assertDontSee('Nonaktifkan Produk');

        $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="is_active"', false)
            ->assertSee('Produk aktif');

        $this->actingAs($admin)->patch(route('admin.products.status', $product), [
            'is_active' => false,
        ])->assertRedirect();

        $this->assertFalse($product->refresh()->is_active);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Album Tetap Nonaktif',
            'variant' => 'Limited',
            'default_price' => 250000,
            'is_active' => '0',
        ])->assertRedirect(route('admin.products.show', $product));

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_form_navbar_back_is_hidden_and_preselected_member_in_dropdown(): void
    {
        $admin = User::factory()->create();
        $member = Member::factory()->create([
            'display_name' => 'Customer Pilihan',
            'username' => 'customer.pilihan',
            'line_user_id' => 'line-customer-123',
            'is_active' => true,
        ]);

        User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);

        $this->actingAs($admin)->get(route('admin.member-orders.create', ['member_id' => $member->id]))
            ->assertOk()
            ->assertDontSee('admin-navbar-back', false)
            ->assertSee('Customer Pilihan')
            ->assertSee('customer.pilihan')
            ->assertSee('Akun terdaftar — Eligible');
    }

    public function test_batch_and_status_details_use_the_compact_reorganized_layout(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create();
        $batch = Batch::factory()->create(['notes' => 'Catatan tunggal batch']);
        $batch->products()->attach($product);
        $status = OrderStatus::factory()->create(['scope' => 'batch']);
        $batch->update(['current_status_id' => $status->id]);

        $this->actingAs($admin)->get(route('admin.batches.edit', $batch))
            ->assertOk()
            ->assertSee('Variasi Barang')
            ->assertSee('data-admin-variant-builder', false)
            ->assertSee('data-admin-variant-list', false)
            ->assertSee('name="variants[0][name]"', false)
            ->assertSee('Catatan tunggal batch')
            ->assertDontSee('Keterangan Internal')
            ->assertDontSee('admin-navbar-back', false);

        $this->actingAs($admin)->get(route('admin.batches.show', $batch))
            ->assertOk()
            ->assertSee('detail-record-field-violet', false)
            ->assertSee('Variasi')
            ->assertSee('Catatan tunggal batch');

        $this->actingAs($admin)->get(route('admin.order-statuses.show', $status))
            ->assertOk()
            ->assertSee('status-detail-usage', false)
            ->assertSee('Nonaktifkan Status')
            ->assertDontSee('Kembali ke Daftar');
    }

    public function test_create_customer_is_always_active_and_only_edit_form_shows_status_control(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.members.create'))
            ->assertOk()
            ->assertDontSee('name="is_active"', false);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'display_name' => 'Pelanggan Otomatis Aktif',
            'username' => 'Aktif.Line',
            'phone' => '081234567890',
            'address' => 'Semarang',
        ])->assertRedirect(route('admin.members.index'));

        $member = Member::where('username', 'aktif.line')->sole();
        $this->assertTrue($member->is_active);

        $this->actingAs($admin)->get(route('admin.members.edit', $member))
            ->assertOk()
            ->assertSee('name="is_active"', false)
            ->assertSee(route('admin.members.show', $member, false), false);
    }

    public function test_order_form_shows_eligible_members_for_new_order(): void
    {
        $admin = User::factory()->create();
        $batch = Batch::factory()->create();

        // Create eligible member (LINE-connected + active)
        $eligibleMember = Member::factory()->create([
            'display_name' => 'Eligible Member',
            'username' => 'eligible.line',
            'line_user_id' => 'line-eligible-123',
            'is_active' => true,
        ]);

        User::factory()->create(['role' => 'customer', 'member_id' => $eligibleMember->id]);

        // Create legacy member (no line_user_id)
        $legacyMember = Member::factory()->create([
            'display_name' => 'Legacy Member',
            'username' => 'legacy.line',
            'line_user_id' => null,
            'is_active' => true,
        ]);

        // Create inactive member
        $inactiveMember = Member::factory()->create([
            'display_name' => 'Inactive Member',
            'username' => 'inactive.line',
            'line_user_id' => 'line-inactive-456',
            'is_active' => false,
        ]);

        $this->actingAs($admin)->get(route('admin.member-orders.create'))
            ->assertOk()
            ->assertSee('Pilih Member')
            ->assertSee('Eligible Member')
            ->assertSee('eligible.line')
            ->assertSee('Akun terdaftar — Eligible')
            ->assertDontSee('Legacy Member')
            ->assertDontSee('Inactive Member');
    }

    public function test_refund_payment_stops_order_and_item_progress_at_refunded(): void
    {
        $admin = User::factory()->create();
        $batchStatus = OrderStatus::factory()->create(['scope' => 'batch']);
        $nextBatchStatus = OrderStatus::factory()->create([
            'name' => 'Batch Tetap Berjalan Setelah Refund',
            'scope' => 'batch',
        ]);
        $itemOverride = OrderStatus::factory()->create(['scope' => 'order_item']);
        $refund = OrderStatus::factory()->create([
            'name' => 'Dana Dikembalikan',
            'code' => 'refund',
            'scope' => 'payment',
            'status_type' => 'failed',
            'is_final' => true,
        ]);
        $batch = Batch::factory()->create(['current_status_id' => $batchStatus->id]);
        $product = Product::factory()->create();
        $batch->products()->attach($product);
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id]);
        $item = OrderItem::factory()->create([
            'member_order_id' => $order->id,
            'product_id' => $product->id,
            'override_status_id' => $itemOverride->id,
        ]);

        $this->actingAs($admin)->put(route('admin.member-orders.update', $order), [
            'member_id' => $order->member_id,
            'batch_id' => $batch->id,
            'payment_status_id' => $refund->id,
            'items' => [[
                'id' => $item->id,
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'override_status_id' => $itemOverride->id,
            ]],
        ])->assertRedirect(route('admin.member-orders.show', $order));

        $order->refresh()->load(['overrideStatus', 'batch.currentStatus']);
        $this->assertSame('refunded', $order->overrideStatus->code);
        $this->assertSame('Selesai (Refund)', $order->overrideStatus->name);

        app(StatusTransitionService::class)->transition($batch, $nextBatchStatus, $admin);
        $this->assertSame('refunded', $order->refresh()->load(['overrideStatus', 'batch.currentStatus'])->effective_status->code);
        $this->assertSame('refunded', $item->refresh()->load(['overrideStatus', 'order.overrideStatus', 'order.batch.currentStatus'])->effective_status->code);

        $otherStatus = OrderStatus::factory()->create(['scope' => 'member_order']);
        $this->actingAs($admin)->post(route('admin.member-orders.status', $order), [
            'status_id' => $otherStatus->id,
        ])->assertSessionHasErrors('status_id');

        $this->assertSame('refunded', $order->refresh()->overrideStatus->code);

        $this->actingAs($admin)->get(route('admin.member-orders.show', $order))
            ->assertOk()
            ->assertSee('Pesanan selesai karena refund')
            ->assertDontSee('data-order-status-modal', false)
            ->assertDontSee('data-order-status-modal-open', false);

        $this->post(route('tracking.search'), ['query' => $order->order_code])
            ->assertOk()
            ->assertSee('background-color: #15803d14', false)
            ->assertDontSee('Selesai (Refund)')
            ->assertDontSee('Batch Tetap Berjalan Setelah Refund');

        $trackingUrl = URL::temporarySignedRoute('tracking.progress', now()->addMinutes(15), [
            'orderCode' => $order->order_code,
        ]);
        $this->get($trackingUrl)
            ->assertOk()
            ->assertSee('Status terkini')
            ->assertSee('Selesai')
            ->assertDontSee('Selesai (Refund)')
            ->assertDontSee('Batch Tetap Berjalan Setelah Refund');

        $order->refresh()->load(['overrideStatus', 'paymentStatus', 'batch.currentStatus']);
        $this->assertSame('selesai', $order->tracking_status->code);
        $this->assertSame('refunded', $order->effective_status->code);

        $anotherItemStatus = OrderStatus::factory()->create(['scope' => 'order_item']);
        try {
            app(StatusTransitionService::class)->transition($item, $anotherItemStatus, $admin);
            $this->fail('Item pesanan refund masih menerima perubahan status.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status_id', $exception->errors());
        }
    }

    public function test_order_and_batch_lists_filter_by_created_date_range(): void
    {
        $admin = User::factory()->create();
        $insideBatch = Batch::factory()->create(['batch_name' => 'Batch Dalam Range', 'created_at' => '2026-04-15 10:00:00']);
        Batch::factory()->create(['batch_name' => 'Batch Luar Range', 'created_at' => '2025-12-15 10:00:00']);
        MemberOrder::factory()->create(['batch_id' => $insideBatch->id, 'order_code' => 'ORD-DALAM-RANGE', 'created_at' => '2026-04-20 10:00:00']);
        MemberOrder::factory()->create(['order_code' => 'ORD-LUAR-RANGE', 'created_at' => '2025-12-20 10:00:00']);

        $this->actingAs($admin)->get(route('admin.batches.index', ['date_from' => '2026-01-01', 'date_to' => '2026-12-31']))
            ->assertOk()
            ->assertSee('Batch Dalam Range')
            ->assertDontSee('Batch Luar Range');

        $this->actingAs($admin)->get(route('admin.member-orders.index', ['date_from' => '2026-01-01', 'date_to' => '2026-12-31']))
            ->assertOk()
            ->assertSee('ORD-DALAM-RANGE')
            ->assertDontSee('ORD-LUAR-RANGE');
    }

    public function test_non_admin_role_is_denied_from_admin_routes(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.products.store'), [
            'name' => 'Tidak Boleh Dibuat',
        ])->assertForbidden();

        $this->assertDatabaseMissing('products', ['name' => 'Tidak Boleh Dibuat']);
    }

    public function test_public_detail_urls_require_signatures_and_sensitive_responses_are_not_cached(): void
    {
        $order = MemberOrder::factory()->create(['order_code' => 'ORD-SECURE-001']);

        $this->get(route('tracking.progress', $order->order_code))->assertForbidden();

        $response = $this->post(route('tracking.search'), ['query' => $order->order_code])
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $response->assertSee('signature=', false);
    }

    public function test_production_https_setting_redirects_plain_http_requests(): void
    {
        config(['app.force_https' => true]);

        $this->get('http://localhost/')
            ->assertStatus(301)
            ->assertRedirect('https://localhost');
    }

    public function test_wrong_scope_status_cannot_be_selected(): void
    {
        $batch = Batch::factory()->create();
        $itemStatus = OrderStatus::factory()->create(['scope' => 'order_item']);

        $this->expectException(ValidationException::class);
        app(StatusTransitionService::class)->transition($batch, $itemStatus);
    }

    public function test_invalid_status_transition_is_atomic(): void
    {
        $old = OrderStatus::factory()->create(['scope' => 'batch']);
        $wrong = OrderStatus::factory()->create(['scope' => 'order_item']);
        $batch = Batch::factory()->create(['current_status_id' => $old->id]);

        try {
            app(StatusTransitionService::class)->transition($batch, $wrong);
        } catch (ValidationException) {
            //
        }

        $this->assertSame($old->id, $batch->refresh()->current_status_id);
        $this->assertDatabaseCount('status_histories', 0);
    }
}
