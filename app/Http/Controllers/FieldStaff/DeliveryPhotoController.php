<?php

namespace App\Http\Controllers\FieldStaff;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\ChalanPhotoStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryPhotoController extends Controller
{
    public function show(Delivery $delivery, ChalanPhotoStorage $photoStorage): RedirectResponse|StreamedResponse
    {
        abort_unless((int) $delivery->created_by_user_id === (int) auth()->id(), 404);

        return $this->photoResponse($delivery, $photoStorage);
    }

    public function showForAdmin(Delivery $delivery, ChalanPhotoStorage $photoStorage): RedirectResponse|StreamedResponse
    {
        abort_unless(auth()->user()?->role === UserRole::Admin, 404);

        return $this->photoResponse($delivery, $photoStorage);
    }

    private function photoResponse(Delivery $delivery, ChalanPhotoStorage $photoStorage): RedirectResponse|StreamedResponse
    {
        abort_unless(filled($delivery->chalan_disk) && filled($delivery->chalan_path), 404);

        return $photoStorage->response($delivery->chalan_disk, $delivery->chalan_path);
    }
}
