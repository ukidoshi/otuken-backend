<?php

namespace Tests\Feature\Feature;

use App\Mail\DistrictInterestLeadMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DistrictInterestLeadTest extends TestCase
{

    public function test_submits_district_interest_and_sends_mail_to_manager(): void
    {
        Mail::fake();

        config(['landing.leads_manager_email' => 'manager@example.com']);

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

    public function test_returns_validation_error_for_missing_name(): void
    {
        config(['landing.leads_manager_email' => 'manager@example.com']);

        $this->postJson('/api/leads/district-interest', [
            'name' => 'A',
            'phone' => '+7 (913) 123-45-67',
            'districtId' => 'q1',
            'districtTitle' => 'Квартал',
        ])
            ->assertUnprocessable();
    }

    public function test_returns_503_when_manager_email_not_configured(): void
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
