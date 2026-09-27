<?php

namespace App\Http\Controllers\FieldStaff;

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

        return $photoStorage->response($delivery->chalan_disk, $delivery->chalan_path);
    }
}
