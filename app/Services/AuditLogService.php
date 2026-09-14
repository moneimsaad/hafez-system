<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(?int $userId, string $action, Model|string $entity, ?int $recordId = null, mixed $old = null, mixed $new = null): void
    {
        $table = $entity instanceof Model ? $entity->getTable() : (new $entity)->getTable();
        $recordId ??= $entity instanceof Model ? $entity->getKey() : null;
        if ($userId === null || $recordId === null) {
            return;
        }
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_value' => $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
            'new_value' => $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
