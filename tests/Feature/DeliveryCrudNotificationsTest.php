<?php

use App\Models\DeliveryZone;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('sends notifications to admin and super admin for delivery zone crud actions', function (): void {
    [$superAdmin, $admin, $actor] = createAdminAndSuperAdminRecipients();

    Sanctum::actingAs($actor);

    $createResponse = $this->postJson('/api/admin/delivery-zones', [
        'name' => 'CBD Zone',
        'zip_code' => '00100',
        'delivery_fee' => 250,
        'status' => 'active',
        'minimum_order_amount' => 1000,
        'estimated_delivery_minutes' => 45,
        'is_serviceable' => true,
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true);

    $zoneId = $createResponse->json('data.id');

    $updateResponse = $this->putJson("/api/admin/delivery-zones/{$zoneId}", [
        'name' => 'CBD Zone Updated',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true);

    $deleteResponse = $this->deleteJson("/api/admin/delivery-zones/{$zoneId}");

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    foreach ([$superAdmin, $admin] as $recipient) {
        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'delivery_zone_created',
        ]);

        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'delivery_zone_updated',
        ]);

        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'delivery_zone_deleted',
        ]);
    }
});

it('sends notifications to admin and super admin for delivery address crud actions', function (): void {
    [$superAdmin, $admin] = createAdminAndSuperAdminRecipients();
    $customer = User::factory()->create();

    $zone = DeliveryZone::query()->create([
        'name' => 'Westlands',
        'zip_code' => '00800',
        'delivery_fee' => 300,
        'status' => 'active',
        'minimum_order_amount' => 1000,
        'estimated_delivery_minutes' => 30,
        'is_serviceable' => true,
    ]);

    Sanctum::actingAs($customer);

    $createResponse = $this->postJson('/api/delivery-addresses', [
        'label' => 'Home',
        'address_line' => '123 Main St',
        'city' => 'Nairobi',
        'zip_code' => $zone->zip_code,
        'longitude' => 36.8219,
        'latitude' => -1.2921,
        'delivery_notes' => 'Gate B',
        'is_default' => true,
        'status' => 'active',
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true);

    $addressId = $createResponse->json('data.id');

    $updateResponse = $this->putJson("/api/delivery-addresses/{$addressId}", [
        'city' => 'Nairobi West',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true);

    $deleteResponse = $this->deleteJson("/api/delivery-addresses/{$addressId}");

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    foreach ([$superAdmin, $admin] as $recipient) {
        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'delivery_address_created',
        ]);

        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'delivery_address_updated',
        ]);

        $this->assertDatabaseHas('asl_notifications', [
            'user_id' => $recipient->id,
            'type' => 'delivery_address_deleted',
        ]);
    }
});

function createAdminAndSuperAdminRecipients(): array
{
    $superAdminRole = Role::query()->create([
        'name' => 'Super Admin',
        'guard_name' => 'web',
    ]);

    $adminRole = Role::query()->create([
        'name' => 'Admin',
        'guard_name' => 'web',
    ]);

    $superAdmin = User::factory()->create();
    $admin = User::factory()->create();
    $actor = User::factory()->create();

    $superAdmin->assignRole($superAdminRole);
    $admin->assignRole($adminRole);
    $actor->assignRole($adminRole);

    return [$superAdmin, $admin, $actor];
}
