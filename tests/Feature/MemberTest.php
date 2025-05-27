<?php

namespace Tests\Feature;

use App\Models\ClassType;
use App\Models\ScheduledClass;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\ClassTypeSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_see_cancel_button() {
        $user = User::factory()->create([
            'role' => 'member'
        ]);
        $this->seed(ClassTypeSeeder::class);
        $scheduledClass = ScheduledClass::create([
            'instructor_id' => User::factory()->create(['role' => 'instructor'])->id,
            'class_type_id' => ClassType::first()->id,
            'date_time' => now()->addHours(3)->minutes(15)->seconds(0)
        ]);

        $response = $this->actingAs($user)
            ->get('instructor/schedule');

        $response->assertDontSeeText('Cancel');
    }

    public function test_member_cannot_see_schedule_class_button() {
        $user = User::factory()->create([
            'role' => 'member'
        ]);

        $response = $this->actingAs($user)
            ->get('instructor/schedule');

        $response->assertDontSeeText('Schedule Class');
    }

    public function test_member_is_redirected_to_member_dashboard() {
        $user = User::factory()->create([
            'role' => 'member'
        ]);

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirectToRoute('member.dashboard');

        $this->followRedirects($response)->assertSeeText("Hey Member");
    }

    public function test_member_can_see_scheduled_classes() {
        $user = User::factory()->create([
            'role' => 'member'
        ]);
        $this->seed(ClassTypeSeeder::class);
        $scheduledClass = ScheduledClass::create([
            'instructor_id' => User::factory()->create(['role' => 'instructor'])->id,
            'class_type_id' => ClassType::first()->id,
            'date_time' => now()->addHours(3)->minutes(15)->seconds(0)
        ]);

        $user->bookings()->attach($scheduledClass->id);

        $response = $this->actingAs($user)
            ->get('member/bookings');

        $response->assertSeeText($scheduledClass->classType->name);
    }

    public function test_member_cannot_schedule_class() {
        $user = User::factory()->create([
            'role' => 'member'
        ]);
        $this->seed(ClassTypeSeeder::class);

        $response = $this->actingAs($user)
            ->post('instructor/schedule', [
                'class_type_id' => ClassType::first()->id,
                'date' => '2026-04-20',
                'time' => '09:00:00'
            ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseMissing('scheduled_classes', [
            'class_type_id' => ClassType::first()->id,
            'date_time' => '2026-04-20 09:00:00'
        ]);
    }

    public function test_member_cannot_cancel_class() {
        $user = User::factory()->create([
            'role' => 'member'
        ]);
        $this->seed(ClassTypeSeeder::class);
        $scheduledClass = ScheduledClass::create([
            'instructor_id' => User::factory()->create(['role' => 'instructor'])->id,
            'class_type_id' => ClassType::first()->id,
            'date_time' => now()->addHours(3)->minutes(15)->seconds(0)
        ]);

        $response = $this->actingAs($user)
            ->delete('/instructor/schedule/'.$scheduledClass->id);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('scheduled_classes', [
            'id' => $scheduledClass->id
        ]);
    }
}
