<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialReportGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_the_five_form_report_generator(): void
    {
        $this->get('/admin/reports')->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['role' => UserRole::FieldStaff]))
            ->get('/admin/reports')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Form 4')
            ->assertSee('Form 7')
            ->assertSee('Form 10')
            ->assertSee('Form 12')
            ->assertSee('Form 13')
            ->assertSee('2026-09');
    }
}
