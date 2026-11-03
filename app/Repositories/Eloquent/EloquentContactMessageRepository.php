<?php

namespace App\Repositories\Eloquent;

use App\Models\ContactMessage;
use App\Repositories\Contracts\ContactMessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentContactMessageRepository implements ContactMessageRepository
{
    public function create(array $attributes): ContactMessage
    {
        return ContactMessage::query()->create($attributes);
    }

    public function paginateForAdmin(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->orderByRaw("CASE status WHEN 'NEW' THEN 0 WHEN 'IN_PROGRESS' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        $counts = ContactMessage::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'new' => (int) ($counts['NEW'] ?? 0),
            'in_progress' => (int) ($counts['IN_PROGRESS'] ?? 0),
            'resolved' => (int) ($counts['RESOLVED'] ?? 0),
            'spam' => (int) ($counts['SPAM'] ?? 0),
        ];
    }

    public function findForAdmin(int $id): ContactMessage
    {
        return ContactMessage::query()->findOrFail($id);
    }

    public function update(ContactMessage $message, array $attributes): ContactMessage
    {
        $message->fill($attributes)->save();

        return $message->refresh();
    }
}
