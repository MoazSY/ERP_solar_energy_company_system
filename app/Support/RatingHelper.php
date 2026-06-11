<?php

namespace App\Support;

use App\Models\Agency_rate_feedback;
use App\Models\Company_rate_feedback;
use App\Models\Customer_rate_feedback;
use App\Models\Agency;
use App\Models\Solar_company;
use App\Models\Project_task;
use Illuminate\Support\Collection;

class RatingHelper
{
    public static function summarize(Collection $feedbacks, ?callable $extraFields = null): array
    {
        $avg = $feedbacks->avg('rate');

        return [
            'average_rating' => $avg !== null ? round((float) $avg, 2) : null,
            'ratings_count' => $feedbacks->count(),
            'feedbacks' => $feedbacks->map(function ($feedback) use ($extraFields) {
                $item = [
                    'id' => $feedback->id,
                    'rate' => (float) $feedback->rate,
                    'feedback' => $feedback->feedback,
                    'created_at' => $feedback->created_at,
                ];

                if ($extraFields) {
                    $item = array_merge($item, $extraFields($feedback));
                }

                return $item;
            })->values()->all(),
        ];
    }

    public static function forEmployee(int $employeeId): array
    {
        $feedbacks = Customer_rate_feedback::query()
            ->whereHas('task', fn ($query) => $query->where('employee_id', $employeeId))
            ->with(['customer:id,first_name,last_name', 'task:id,task_type,task_type_new'])
            ->latest('id')
            ->get();

        return self::summarize($feedbacks, function ($feedback) {
            return [
                'task_id' => $feedback->task_id,
                'task_type' => $feedback->task?->task_type_new ?? $feedback->task?->task_type,
                'customer' => $feedback->customer ? [
                    'id' => $feedback->customer->id,
                    'first_name' => $feedback->customer->first_name,
                    'last_name' => $feedback->customer->last_name,
                ] : null,
            ];
        });
    }

    public static function forCompany(int|Solar_company $company): array
    {
        if ($company instanceof Solar_company) {
            $companyId = $company->id;
            if ($company->relationLoaded('companyRateFeedbacks')) {
                $feedbacks = $company->companyRateFeedbacks;
                if ($feedbacks->isNotEmpty() && !$feedbacks->first()->relationLoaded('customer')) {
                    $feedbacks->load('customer:id,first_name,last_name');
                }

                return self::summarize($feedbacks, self::companyFeedbackExtraFields());
            }
        } else {
            $companyId = $company;
        }

        $feedbacks = Company_rate_feedback::query()
            ->where('company_id', $companyId)
            ->with(['customer:id,first_name,last_name'])
            ->latest('id')
            ->get();

        return self::summarize($feedbacks, self::companyFeedbackExtraFields());
    }

    public static function forAgency(int|Agency $agency): array
    {
        if ($agency instanceof Agency) {
            $agencyId = $agency->id;
            if ($agency->relationLoaded('agencyRateFeedbacks')) {
                $feedbacks = $agency->agencyRateFeedbacks;
                if ($feedbacks->isNotEmpty() && !$feedbacks->first()->relationLoaded('company')) {
                    $feedbacks->load('company:id,company_name');
                }

                return self::summarize($feedbacks, self::agencyFeedbackExtraFields());
            }
        } else {
            $agencyId = $agency;
        }

        $feedbacks = Agency_rate_feedback::query()
            ->where('agency_id', $agencyId)
            ->with(['company:id,company_name'])
            ->latest('id')
            ->get();

        return self::summarize($feedbacks, self::agencyFeedbackExtraFields());
    }

    public static function forTask(Project_task $task): array
    {
        $feedbacks = $task->relationLoaded('customerRateFeedbacks')
            ? $task->customerRateFeedbacks
            : $task->customerRateFeedbacks()->with('customer:id,first_name,last_name')->get();

        if ($feedbacks->isNotEmpty() && !$feedbacks->first()->relationLoaded('customer')) {
            $feedbacks->load('customer:id,first_name,last_name');
        }

        return self::summarize($feedbacks, function ($feedback) {
            return [
                'customer' => $feedback->customer ? [
                    'id' => $feedback->customer->id,
                    'first_name' => $feedback->customer->first_name,
                    'last_name' => $feedback->customer->last_name,
                ] : null,
            ];
        });
    }

    public static function appendTaskRatings(array $payload, Project_task $task): array
    {
        $payload['rating'] = self::forTask($task);

        return $payload;
    }

    private static function companyFeedbackExtraFields(): callable
    {
        return function ($feedback) {
            return [
                'customer' => $feedback->customer ? [
                    'id' => $feedback->customer->id,
                    'first_name' => $feedback->customer->first_name,
                    'last_name' => $feedback->customer->last_name,
                ] : null,
            ];
        };
    }

    private static function agencyFeedbackExtraFields(): callable
    {
        return function ($feedback) {
            return [
                'company' => $feedback->company ? [
                    'id' => $feedback->company->id,
                    'company_name' => $feedback->company->company_name,
                ] : null,
            ];
        };
    }
}
