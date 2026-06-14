<?php

namespace Tests\Feature\Feature;

use App\Mail\DistrictInterestLeadMail;
use App\Models\SiteSetting;
use App\Services\Leads\LeadRecipientList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DistrictInterestLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_submits_district_interest_and_sends_mail_to_manager(): void
    {
        Mail::fake();

        LeadRecipientList::store(['manager@example.com']);

        $this->postJson('/api/leads/district-interest', [
            'name' => 'Иван Петров',
            'phone' => '+7 (913) 123-45-67',
            'districtId' => 'q1',
            'districtTitle' => 'Квартал «Центральный»',
            'districtType' => 'Жилой',
            'source' => 'district-modal',
            'page' => 'https://отукен.рф/',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Спасибо! Мы получили вашу заявку и свяжемся с вами.',
            ]);

        Mail::assertSent(DistrictInterestLeadMail::class, function (DistrictInterestLeadMail $mail): bool {
            return $mail->hasTo('manager@example.com')
                && $mail->clientName === 'Иван Петров'
                && $mail->phone === '+7 (913) 123-45-67'
                && $mail->districtTitle === 'Квартал «Центральный»';
        });
    }

    public function test_sends_mail_to_multiple_recipients(): void
    {
        Mail::fake();

        LeadRecipientList::store([
            'manager@example.com',
            'sales@example.com',
        ]);

        $this->postJson('/api/leads/district-interest', [
            'name' => 'Иван',
            'phone' => '+7 (913) 123-45-67',
            'districtId' => 'royal',
            'districtTitle' => 'Квартал "Королевский"',
        ])->assertOk();

        Mail::assertSent(DistrictInterestLeadMail::class, function (DistrictInterestLeadMail $mail): bool {
            return $mail->hasTo('manager@example.com') && $mail->hasTo('sales@example.com');
        });
    }

    public function test_falls_back_to_env_when_admin_list_is_empty(): void
    {
        Mail::fake();

        config(['landing.leads_manager_email' => 'legacy@example.com, backup@example.com']);

        $this->postJson('/api/leads/district-interest', [
            'name' => 'Иван',
            'phone' => '+7 (913) 123-45-67',
            'districtId' => 'q1',
            'districtTitle' => 'Квартал',
        ])->assertOk();

        Mail::assertSent(DistrictInterestLeadMail::class, function (DistrictInterestLeadMail $mail): bool {
            return $mail->hasTo('legacy@example.com') && $mail->hasTo('backup@example.com');
        });

        $this->assertNull(SiteSetting::getValue(LeadRecipientList::SETTING_KEY));
    }

    public function test_returns_validation_error_for_missing_name(): void
    {
        LeadRecipientList::store(['manager@example.com']);

        $this->postJson('/api/leads/district-interest', [
            'name' => 'A',
            'phone' => '+7 (913) 123-45-67',
            'districtId' => 'q1',
            'districtTitle' => 'Квартал',
        ])
            ->assertUnprocessable();
    }

    public function test_returns_503_when_no_recipients_configured(): void
    {
        config(['landing.leads_manager_email' => null]);

        $this->postJson('/api/leads/district-interest', [
            'name' => 'Иван',
            'phone' => '+7 (913) 123-45-67',
            'districtId' => 'q1',
            'districtTitle' => 'Квартал',
        ])
            ->assertStatus(503);
    }
}
