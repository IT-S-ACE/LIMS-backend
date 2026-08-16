<?php

namespace App\Services;


use App\Models\Notification;
use App\Models\Patient;
use App\Models\Reagent;
use App\Models\ReagentLot;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\ResultReadyMail;


class NotificationService
{

    public function notifyPatient(
        Patient $patient,
        string $message
    ): Notification {


        return DB::transaction(function () use ($patient, $message) {


            $notification = Notification::create([


                'patient_id' => $patient->id,

                'type' => 'result_ready',

                'message' => $message,

                'channel' => 'email',

                'status' => 'pending'


            ]);



            if ($patient->email) {


                Mail::to($patient->email)
                    ->send(
                        new ResultReadyMail($message)
                    );


                $notification->update([
                    'status' => 'sent'
                ]);

            }



            return $notification;


        });


    }

    public function notifySystem(
        string $type,
        string $message
    ): Notification {


        return Notification::create([


            'patient_id' => null,

            'type' => $type,

            'message' => $message,

            'channel' => 'in-app',

            'status' => 'pending'


        ]);

    }

    public function notifyLowStock(
        Reagent $reagent
    ): ?Notification {


        $exists = Notification::where(
            'type',
            'low_stock'
        )
            ->where(
                'message',
                'like',
                "%{$reagent->name}%"
            )
            ->where(
                'status',
                'pending'
            )
            ->exists();



        if ($exists) {

            return null;

        }



        return $this->notifySystem(

            'low_stock',

            "{$reagent->name} is at {$reagent->stock_qty} (min {$reagent->min_stock})."

        );


    }

    public function notifyExpiry(
        Reagent $reagent
    ): Notification {
        return $this->notifySystem(

            'expiry_warning',

            "{$reagent->name} will expire on {$reagent->expiry_date->format('Y-m-d')}."

        );
    }

    public function notifyLotExpiry(ReagentLot $lot): ?Notification
    {
        if (
            $lot->expiry_date->isAfter(today()->addDays(30))
            || (float) $lot->remaining_quantity <= 0
        ) {
            return null;
        }

        $lot->loadMissing('reagent');
        $message = "{$lot->reagent->name} lot {$lot->lot_number} expires on {$lot->expiry_date->format('Y-m-d')}.";

        $exists = Notification::query()
            ->where('type', 'expiry_warning')
            ->where('message', $message)
            ->where('status', 'pending')
            ->exists();

        return $exists ? null : $this->notifySystem('expiry_warning', $message);
    }

    public function send(
        Notification $notification
    ) {


        if (
            $notification->patient &&
            $notification->patient->email
        ) {


            Mail::to(
                $notification->patient->email
            )
                ->send(
                    new ResultReadyMail(
                        $notification->message
                    )
                );


        }


        $notification->update([
            'status' => 'sent'
        ]);


        return $notification;


    }

    public function getForUser(User $user)
    {
        $query = Notification::query();

        if ($user->role === 'patient') {
            $patientId = $user->patient?->id;
            $query->where('patient_id', $patientId ?: '00000000-0000-0000-0000-000000000000');
        } elseif ($user->role === 'lab_technician') {
            $query->whereNull('patient_id');
        }

        return $query
            ->latest()
            ->paginate(20);
    }

    public function markAsRead(Notification $notification, User $user): void
    {
        if (!$this->canAccess($notification, $user)) {
            throw new AuthorizationException(
                'You do not have permission to access this notification.'
            );
        }

        $notification->update(['read_at' => now()]);
    }

    private function canAccess(Notification $notification, User $user): bool
    {
        if (in_array($user->role, ['admin', 'receptionist'], true)) {
            return true;
        }

        if ($user->role === 'lab_technician') {
            return $notification->patient_id === null;
        }

        return $user->role === 'patient'
            && $user->patient
            && $notification->patient_id === $user->patient->id;
    }
}
