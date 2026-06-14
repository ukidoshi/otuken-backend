<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DistrictInterestRequest;
use App\Mail\DistrictInterestLeadMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DistrictInterestController extends Controller
{
    public function store(DistrictInterestRequest $request): JsonResponse
    {
        $managerEmail = config('landing.leads_manager_email');

        if (! is_string($managerEmail) || trim($managerEmail) === '') {
            Log::error('District interest lead: LEADS_MANAGER_EMAIL is not configured');

            return response()->json([
                'error' => 'Сервис временно недоступен. Попробуйте позже или свяжитесь с нами по телефону на сайте.',
            ], 503);
        }

        $data = $request->validated();

        try {
            Mail::to($managerEmail)->send(new DistrictInterestLeadMail(
                clientName: $data['name'],
                phone: $data['phone'],
                districtTitle: $data['districtTitle'],
                districtType: $data['districtType'] ?? '',
                districtId: $data['districtId'],
                page: $data['page'] ?? null,
            ));
        } catch (Throwable $exception) {
            Log::error('District interest lead mail failed', [
                'exception' => $exception->getMessage(),
                'district_id' => $data['districtId'],
            ]);

            return response()->json([
                'error' => 'Не удалось отправить заявку. Попробуйте ещё раз или свяжитесь с нами по телефону на сайте.',
            ], 502);
        }

        return response()->json([
            'message' => 'Спасибо! Мы получили вашу заявку и свяжемся с вами.',
        ]);
    }
}
