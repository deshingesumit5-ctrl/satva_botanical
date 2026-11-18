<?php

namespace Tests\Feature;

use App\Models\QcRequest;
use App\Models\User;
use Tests\TestCase;

class QcModuleTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@gmail.com')->first();
    }

    public function test_can_view_qc_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('qc.dashboard'));
        $response->assertStatus(200);
    }

    public function test_can_view_qc_pending_requests(): void
    {
        $response = $this->actingAs($this->admin)->get(route('qc.pending'));
        $response->assertStatus(200);
    }

    public function test_can_view_qc_history(): void
    {
        $response = $this->actingAs($this->admin)->get(route('qc.history'));
        $response->assertStatus(200);
    }

    public function test_can_view_qc_request_show(): void
    {
        $qcReq = QcRequest::first();
        if ($qcReq) {
            $response = $this->actingAs($this->admin)->get(route('qc.show', $qcReq->id));
            $response->assertStatus(200);
        }
    }
}
