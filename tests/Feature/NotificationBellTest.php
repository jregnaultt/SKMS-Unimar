<?php

namespace Tests\Feature;

use App\Models\Production;
use App\Models\User;
use App\Notifications\ProductionStateChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Production $production;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::firstOrCreate(['name' => 'Estudiante']);

        // 2. Setup User
        $this->student = User::factory()->create();
        $this->student->assignRole('Estudiante');

        // 3. Setup Production
        $this->production = Production::factory()->create();
    }

    /**
     * Test unauthenticated users cannot access notifications.
     */
    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson(route('notifications.index'))
            ->assertStatus(401);
    }

    /**
     * Test user can fetch their notifications and unread count.
     */
    public function test_user_can_fetch_their_notifications(): void
    {
        // Assert unread count is 0 initially
        $this->actingAs($this->student)
            ->getJson(route('notifications.index'))
            ->assertStatus(200)
            ->assertJson([
                'notifications' => [],
                'unreadCount' => 0,
            ]);

        // Send a notification to the student
        $this->student->notify(new ProductionStateChangedNotification(
            $this->production,
            'draft',
            'under_tutor_review',
            'Nueva revisión',
            'Tu trabajo ha sido enviado a revisión.'
        ));

        // Fetch notifications and assert values
        $response = $this->actingAs($this->student)
            ->getJson(route('notifications.index'))
            ->assertStatus(200);

        $response->assertJsonPath('unreadCount', 1);
        $response->assertJsonCount(1, 'notifications');
        $this->assertEquals('Nueva revisión', $response->json('notifications.0.data.title'));
    }

    /**
     * Test user can mark a specific notification as read.
     */
    public function test_user_can_mark_notification_as_read(): void
    {
        // Send notification
        $this->student->notify(new ProductionStateChangedNotification(
            $this->production,
            'draft',
            'under_tutor_review',
            'Correcciones requeridas',
            'Por favor corrige el capítulo I.'
        ));

        $notification = $this->student->unreadNotifications->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        // Mark as read
        $this->actingAs($this->student)
            ->postJson(route('notifications.read', ['id' => $notification->id]))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Assert marked as read in database
        $notification->refresh();
        $this->assertNotNull($notification->read_at);

        // Verify unread count is now 0
        $this->actingAs($this->student)
            ->getJson(route('notifications.index'))
            ->assertStatus(200)
            ->assertJsonPath('unreadCount', 0);
    }

    /**
     * Test user can mark all notifications as read.
     */
    public function test_user_can_mark_all_notifications_as_read(): void
    {
        // Send 3 notifications
        for ($i = 0; $i < 3; $i++) {
            $this->student->notify(new ProductionStateChangedNotification(
                $this->production,
                'draft',
                'under_tutor_review',
                "Alerta {$i}",
                "Detalle de la alerta {$i}"
            ));
        }

        $this->assertEquals(3, $this->student->unreadNotifications()->count());

        // Mark all as read
        $this->actingAs($this->student)
            ->postJson(route('notifications.read-all'))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Assert unread count is 0 in DB
        $this->assertEquals(0, $this->student->unreadNotifications()->count());

        // Verify unread count is now 0 via endpoint
        $this->actingAs($this->student)
            ->getJson(route('notifications.index'))
            ->assertStatus(200)
            ->assertJsonPath('unreadCount', 0);
    }
}
