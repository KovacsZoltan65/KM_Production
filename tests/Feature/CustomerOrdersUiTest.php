<?php

namespace Tests\Feature;

use App\Enums\CustomerOrderStatus;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\User;
use App\Services\Admin\CustomerOrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerOrdersUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_orders_page(): void
    {
        $this->get('/admin/customer-orders')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_customer_orders_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/customer-orders')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_customer_orders_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/customer-orders')
            ->assertOk();
    }

    public function test_item_options_contain_only_active_finished_products(): void
    {
        $activeFinishedProduct = Item::factory()->finishedProduct()->create([
            'item_number' => 'PRD-ORDERABLE',
            'name' => 'Orderable product',
            'unit' => 'db',
        ]);
        Item::factory()->finishedProduct()->create([
            'item_number' => 'PRD-INACTIVE',
            'is_active' => false,
        ]);
        Item::factory()->purchasedMaterial()->create(['item_number' => 'MAT-ACTIVE']);
        Item::factory()->manufacturedPart()->create(['item_number' => 'PART-ACTIVE']);
        Item::factory()->semiFinishedProduct()->create(['item_number' => 'SEMI-ACTIVE']);

        $this->actingAs($this->superAdmin())
            ->get('/admin/customer-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Admin/CustomerOrders/Index')
                ->has('itemOptions', 1)
                ->where('itemOptions.0', [
                    'id' => $activeFinishedProduct->id,
                    'item_number' => 'PRD-ORDERABLE',
                    'name' => 'Orderable product',
                    'unit' => 'db',
                    'label' => 'PRD-ORDERABLE - Orderable product',
                ]));
    }

    public function test_customer_order_can_be_created(): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create();
        $item = Item::factory()->finishedProduct()->create(['unit' => 'db']);

        $this->actingAs($admin)
            ->post('/admin/customer-orders', $this->payload($customer, $item))
            ->assertRedirect();

        $this->assertDatabaseHas('customer_orders', [
            'customer_id' => $customer->id,
            'status' => CustomerOrderStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('customer_order_items', [
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'db',
        ]);
    }

    public function test_customer_order_cannot_be_created_with_purchased_material(): void
    {
        $this->assertStoreRejectsItem(Item::factory()->purchasedMaterial()->create());
    }

    public function test_customer_order_cannot_be_created_with_inactive_finished_product(): void
    {
        $this->assertStoreRejectsItem(Item::factory()->finishedProduct()->create(['is_active' => false]));
    }

    public function test_customer_order_cannot_be_created_with_nonexistent_item(): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create();
        $item = Item::factory()->finishedProduct()->create();
        $payload = $this->payload($customer, $item);
        $payload['items'][0]['item_id'] = $item->id + 10000;

        $this->actingAs($admin)
            ->post('/admin/customer-orders', $payload)
            ->assertSessionHasErrors('items.0.item_id');

        $this->assertDatabaseCount('customer_orders', 0);
        $this->assertDatabaseCount('customer_order_items', 0);
    }

    public function test_customer_order_can_be_updated(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create();
        $item = Item::factory()->finishedProduct()->create(['unit' => 'pcs']);

        $this->actingAs($admin)
            ->put("/admin/customer-orders/{$customerOrder->id}", $this->payload($customerOrder->customer, $item, [
                'notes' => 'Updated notes',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('customer_orders', [
            'id' => $customerOrder->id,
            'notes' => 'Updated notes',
        ]);
        $this->assertDatabaseHas('customer_order_items', [
            'customer_order_id' => $customerOrder->id,
            'item_id' => $item->id,
            'unit' => 'pcs',
        ]);
    }

    public function test_customer_order_cannot_be_updated_with_purchased_material(): void
    {
        $this->assertUpdateRejectsItem(Item::factory()->purchasedMaterial()->create());
    }

    public function test_customer_order_cannot_be_updated_with_inactive_finished_product(): void
    {
        $this->assertUpdateRejectsItem(Item::factory()->finishedProduct()->create(['is_active' => false]));
    }

    public function test_service_rejects_non_orderable_item_without_http_validation(): void
    {
        $customer = Customer::factory()->create();
        $material = Item::factory()->purchasedMaterial()->create();

        try {
            app(CustomerOrderService::class)->create($this->payload($customer, $material));
            $this->fail('A közvetlen service-hívás nem utasította el az alapanyagot.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.item_id', $exception->errors());
        }

        $this->assertDatabaseCount('customer_orders', 0);
        $this->assertDatabaseCount('customer_order_items', 0);
    }

    public function test_draft_customer_order_can_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Draft]);

        $this->actingAs($admin)
            ->delete("/admin/customer-orders/{$customerOrder->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('customer_orders', ['id' => $customerOrder->id]);
    }

    public function test_confirmed_customer_order_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->from('/admin/customer-orders')
            ->delete("/admin/customer-orders/{$customerOrder->id}")
            ->assertRedirect('/admin/customer-orders')
            ->assertSessionHasErrors('status');

        $this->assertNotSoftDeleted('customer_orders', ['id' => $customerOrder->id]);
    }

    public function test_draft_customer_order_can_be_confirmed(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Draft]);

        $this->actingAs($admin)
            ->patch("/admin/customer-orders/{$customerOrder->id}/confirm")
            ->assertRedirect();

        $this->assertDatabaseHas('customer_orders', [
            'id' => $customerOrder->id,
            'status' => CustomerOrderStatus::Confirmed->value,
        ]);
    }

    public function test_confirmed_customer_order_cannot_be_confirmed_again(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->from('/admin/customer-orders')
            ->patch("/admin/customer-orders/{$customerOrder->id}/confirm")
            ->assertRedirect('/admin/customer-orders')
            ->assertSessionHasErrors('status');
    }

    public function test_customer_order_can_be_cancelled(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->patch("/admin/customer-orders/{$customerOrder->id}/cancel")
            ->assertRedirect();

        $this->assertDatabaseHas('customer_orders', [
            'id' => $customerOrder->id,
            'status' => CustomerOrderStatus::Cancelled->value,
        ]);
    }

    public function test_completed_customer_order_cannot_be_cancelled(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Completed]);

        $this->actingAs($admin)
            ->from('/admin/customer-orders')
            ->patch("/admin/customer-orders/{$customerOrder->id}/cancel")
            ->assertRedirect('/admin/customer-orders')
            ->assertSessionHasErrors('status');
    }

    public function test_status_filter_works(): void
    {
        $admin = $this->superAdmin();
        CustomerOrder::factory()->create(['order_number' => 'SO-2026-DRAFT', 'status' => CustomerOrderStatus::Draft]);
        CustomerOrder::factory()->create(['order_number' => 'SO-2026-CONFIRMED', 'status' => CustomerOrderStatus::Confirmed]);

        $response = $this->actingAs($admin)->get('/admin/customer-orders?status=confirmed');

        $response->assertOk();
        $response->assertSee('SO-2026-CONFIRMED');
        $response->assertDontSee('SO-2026-DRAFT');
    }

    public function test_search_works(): void
    {
        $admin = $this->superAdmin();
        $needleCustomer = Customer::factory()->create(['code' => 'CUST-NEEDLE', 'name' => 'Needle Customer']);
        $otherCustomer = Customer::factory()->create(['code' => 'CUST-OTHER', 'name' => 'Other Customer']);
        CustomerOrder::factory()->create(['order_number' => 'SO-2026-NEEDLE', 'customer_id' => $needleCustomer->id]);
        CustomerOrder::factory()->create(['order_number' => 'SO-2026-OTHER', 'customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($admin)->get('/admin/customer-orders?search=Needle');

        $response->assertOk();
        $response->assertSee('SO-2026-NEEDLE');
        $response->assertDontSee('SO-2026-OTHER');
    }

    public function test_show_page_can_be_loaded(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['order_number' => 'SO-2026-SHOW']);
        CustomerOrderItem::factory()->create(['customer_order_id' => $customerOrder->id]);

        $this->actingAs($admin)
            ->get("/admin/customer-orders/{$customerOrder->id}")
            ->assertOk()
            ->assertSee('SO-2026-SHOW');
    }

    public function test_activity_log_is_created_for_confirm_event(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Draft]);

        $this->actingAs($admin)
            ->patch("/admin/customer-orders/{$customerOrder->id}/confirm")
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'customer_order_confirmed')->exists());
    }

    public function test_activity_log_is_created_for_cancel_event(): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create(['status' => CustomerOrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->patch("/admin/customer-orders/{$customerOrder->id}/cancel")
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'customer_order_cancelled')->exists());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Customer $customer, Item $item, array $overrides = []): array
    {
        return \array_merge([
            'customer_id' => $customer->id,
            'requested_delivery_date' => now()->addMonth()->format('Y-m-d'),
            'notes' => 'Order notes',
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 2,
                    'unit' => $item->unit,
                    'notes' => 'Item notes',
                ],
            ],
        ], $overrides);
    }

    private function assertStoreRejectsItem(Item $item): void
    {
        $admin = $this->superAdmin();
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/customer-orders', $this->payload($customer, $item))
            ->assertSessionHasErrors('items.0.item_id');

        $this->assertDatabaseCount('customer_orders', 0);
        $this->assertDatabaseCount('customer_order_items', 0);
    }

    private function assertUpdateRejectsItem(Item $item): void
    {
        $admin = $this->superAdmin();
        $customerOrder = CustomerOrder::factory()->create();
        $originalItem = CustomerOrderItem::factory()->create([
            'customer_order_id' => $customerOrder->id,
        ]);

        $this->actingAs($admin)
            ->put(
                "/admin/customer-orders/{$customerOrder->id}",
                $this->payload($customerOrder->customer, $item),
            )
            ->assertSessionHasErrors('items.0.item_id');

        $this->assertDatabaseHas('customer_order_items', [
            'id' => $originalItem->id,
            'customer_order_id' => $customerOrder->id,
            'item_id' => $originalItem->item_id,
        ]);
        $this->assertDatabaseMissing('customer_order_items', [
            'customer_order_id' => $customerOrder->id,
            'item_id' => $item->id,
        ]);
    }

    private function superAdmin(string $email = 'customer-orders-admin@example.com'): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super-admin');

        return $user;
    }
}
