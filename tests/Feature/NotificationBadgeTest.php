<?php

namespace Tests\Feature;

use App\Models\NotificationRead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationBadgeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_notification_shows_red_badge_and_clears_on_click_per_user(): void
    {
        $superAdmin = User::whereHas('role', fn ($q) => $q->where('role_code', 'super_admin'))->firstOrFail();
        $qcManager = User::whereHas('role', fn ($q) => $q->where('role_code', 'qc_manager'))->firstOrFail();

        // Start with clean read state for both users inside this transaction
        NotificationService::markAllReadForUser($superAdmin);
        NotificationService::markAllReadForUser($qcManager);

        $this->actingAs($superAdmin)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 0]);

        $this->actingAs($qcManager)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 0]);

        // Dashboard renders hidden badge when unread count is 0
        $resZero = $this->actingAs($superAdmin)->get(route('dashboard'));
        $resZero->assertOk();
        $resZero->assertSee('id="desktopNotifBadge"', false);
        $resZero->assertSee('notif-badge badge rounded-pill bg-danger d-none', false);

        // Create 1 new QC notification
        NotificationService::notifyRole(
            'qc_manager',
            'QC Pending',
            'Batch BAT-TEST requires QC review.',
            '/qc',
            'qc_pending'
        );

        // Both Super Admin and QC Manager see count = 1
        $this->actingAs($superAdmin)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->actingAs($qcManager)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        // Navbar renders visible red (1) badge for Super Admin
        $resOne = $this->actingAs($superAdmin)->get(route('dashboard'));
        $resOne->assertOk();
        $resOne->assertSee('class="notif-badge badge rounded-pill bg-danger ">1</span>', false);

        // Super Admin clicks bell icon -> visits /notifications -> badge clears for Super Admin
        $notifPage = $this->actingAs($superAdmin)->get(route('notifications.index'));
        $notifPage->assertOk();
        $notifPage->assertSee('notif-badge badge rounded-pill bg-danger d-none', false);

        $this->actingAs($superAdmin)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 0]);

        // QC Manager STILL sees red (1) badge until QC Manager clicks it
        $this->actingAs($qcManager)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        // QC Manager clicks bell icon -> visits /notifications -> badge clears for QC Manager too
        $qcNotifPage = $this->actingAs($qcManager)->get(route('notifications.index'));
        $qcNotifPage->assertOk();
        $qcNotifPage->assertSee('notif-badge badge rounded-pill bg-danger d-none', false);

        $this->actingAs($qcManager)
            ->getJson(route('notifications.count'))
            ->assertOk()
            ->assertJson(['count' => 0]);
    }
}
