<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\FoodSchedule;
use App\Models\NonWorkingDate;
use App\Models\School;
use App\Models\User;
use App\Repositories\DeliveryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeliveryManagementService
{
    public function __construct(
        private readonly DeliveryRepository $deliveries,
        private readonly StudentCountResolver $studentCounts,
        private readonly DemandCalculator $demandCalculator,
        private readonly ChalanPhotoStorage $photoStorage,
    ) {}

    /** @return array{schedule: FoodSchedule, items: Collection<int, FoodItem>, demand: array<string, int>} */
    public function creationContext(School $school, string $date): array
    {
        $this->ensureDateIsEligible($school, $date);

        $schedule = $this->deliveries->scheduleForDate($date);
        if ($schedule === null) {
            throw ValidationException::withMessages(['date' => 'Admin has not set a food schedule for this date.']);
        }

        $items = FoodItem::query()->orderBy('id')->get();
        $demand = [];
        foreach ($items as $item) {
            $demand[$item->key] = $this->demandCalculator->forSchoolDateItem($school, $date, $item)->quantity;
        }

        return compact('schedule', 'items', 'demand');
    }

    public function create(array $data, UploadedFile $photo, User $creator): Delivery
    {
        $school = School::query()->findOrFail((int) $data['school_id']);
        $this->ensureDateIsEligible($school, $data['date']);

        if ($this->deliveries->hasEntryForSchoolDate($school->id, $data['date'])) {
            throw ValidationException::withMessages(['school_id' => 'A delivery entry already exists for this school and date.']);
        }

        $photoReference = $this->photoStorage->store($photo);

        try {
            return DB::transaction(fn (): Delivery => $this->deliveries->create([
                'school_id' => $school->id,
                'date' => $data['date'],
                'bun_quantity' => (int) $data['bun_quantity'],
                'egg_quantity' => (int) $data['egg_quantity'],
                'banana_quantity' => (int) $data['banana_quantity'],
                'chalan_number' => $data['chalan_number'],
                'chalan_date' => $data['chalan_date'],
                'chalan_disk' => $photoReference['disk'],
                'chalan_path' => $photoReference['path'],
                'created_by_user_id' => $creator->id,
            ]));
        } catch (UniqueConstraintViolationException $exception) {
            $this->deleteUploadedPhoto($photoReference);

            throw ValidationException::withMessages([
                'school_id' => 'A delivery entry already exists for this school and date.',
            ]);
        } catch (Throwable $exception) {
            $this->deleteUploadedPhoto($photoReference);
            throw $exception;
        }
    }

    public function update(Delivery $delivery, array $data, ?UploadedFile $replacementPhoto, User $editor): Delivery
    {
        $newPhotoReference = $replacementPhoto === null ? null : $this->photoStorage->store($replacementPhoto);

        try {
            return DB::transaction(function () use ($delivery, $data, $newPhotoReference, $editor): Delivery {
                $current = $this->deliveries->lockForUpdate($delivery->id);
                abort_unless((int) $current->created_by_user_id === (int) $editor->id, 404);

                $previous = [
                    'bun_quantity' => $current->bun_quantity,
                    'egg_quantity' => $current->egg_quantity,
                    'banana_quantity' => $current->banana_quantity,
                    'chalan_number' => $current->chalan_number,
                    'chalan_date' => $current->chalan_date?->toDateString(),
                    'chalan_disk' => $current->chalan_disk,
                    'chalan_path' => $current->chalan_path,
                ];
                $next = [
                    'bun_quantity' => (int) $data['bun_quantity'],
                    'egg_quantity' => (int) $data['egg_quantity'],
                    'banana_quantity' => (int) $data['banana_quantity'],
                    'chalan_number' => $data['chalan_number'],
                    'chalan_date' => $data['chalan_date'],
                    'chalan_disk' => $newPhotoReference['disk'] ?? $current->chalan_disk,
                    'chalan_path' => $newPhotoReference['path'] ?? $current->chalan_path,
                    'updated_by_user_id' => $editor->id,
                ];

                $changed = $newPhotoReference !== null
                    || $previous['bun_quantity'] !== $next['bun_quantity']
                    || $previous['egg_quantity'] !== $next['egg_quantity']
                    || $previous['banana_quantity'] !== $next['banana_quantity'];
                $changed = $changed || $previous['chalan_number'] !== $next['chalan_number'];
                $changed = $changed || $previous['chalan_date'] !== $next['chalan_date'];

                if ($changed) {
                    $this->deliveries->recordCorrection([
                        'delivery_id' => $current->id,
                        'previous_bun_quantity' => $previous['bun_quantity'],
                        'previous_egg_quantity' => $previous['egg_quantity'],
                        'previous_banana_quantity' => $previous['banana_quantity'],
                        'previous_chalan_number' => $previous['chalan_number'],
                        'previous_chalan_date' => $previous['chalan_date'],
                        'previous_chalan_disk' => $previous['chalan_disk'],
                        'previous_chalan_path' => $previous['chalan_path'],
                        'editor_user_id' => $editor->id,
                        'edited_at' => now(),
                    ]);
                }

                return $this->deliveries->save($current, $next);
            });
        } catch (Throwable $exception) {
            if ($newPhotoReference !== null) {
                $this->deleteUploadedPhoto($newPhotoReference);
            }

            throw $exception;
        }
    }

    private function ensureDateIsEligible(School $school, string $date): void
    {
        if (now()->startOfDay()->lt($date)) {
            throw ValidationException::withMessages(['date' => 'Future delivery dates are not allowed.']);
        }

        if (NonWorkingDate::query()->whereDate('date', $date)->exists()) {
            throw ValidationException::withMessages(['date' => 'Delivery cannot be entered on a non-working date.']);
        }

        if ($this->deliveries->scheduleForDate($date) === null) {
            throw ValidationException::withMessages(['date' => 'Admin has not set a food schedule for this date.']);
        }

        if ($this->studentCounts->forDate($school, $date) === null) {
            throw ValidationException::withMessages(['school_id' => 'This school has no student count effective on the selected date.']);
        }
    }

    /** @param array{disk: string, path: string} $photoReference */
    private function deleteUploadedPhoto(array $photoReference): void
    {
        try {
            $this->photoStorage->delete($photoReference['disk'], $photoReference['path']);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }
    }
}
