<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\ModelRepositories;

use App\ModelRepositories\Base\ModelRepository;
use App\Models\ActivityLog;
use App\Utils\ConfigHelper;
use App\Utils\CurrentDevice;
use App\Utils\CurrentScreen;

/**
 * Class ActivityLogRepository
 * @package App\ModelRepositories
 * @property ActivityLog $model
 */
class ActivityLogRepository extends ModelRepository
{
    public function __construct($id = null)
    {
        if (!ConfigHelper::get('activity_log_enabled')) {
            $this->abort404('Activity log is not enabled');
        }

        parent::__construct($id);
    }

    public function modelClass()
    {
        return ActivityLog::class;
    }

    public function query()
    {
        return parent::query()->with('device');
    }

    protected function searchOn($query, array $search)
    {
        if (!empty($search['user_id'])) {
            $query->where('user_id', $search['user_id']);
        }
        if (!empty($search['admin'])) {
            $query->with('admin')
                ->where('client', 'admin');
        }
        if (!empty($search['screen'])) {
            $query->where('screen', $search['screen']);
        }
        if (!empty($search['action'])) {
            $query->where('action', $search['action']);
        }
        if (!empty($search['created_from'])) {
            $query->where('created_at', '>=', $search['created_from']);
        }
        if (!empty($search['created_to'])) {
            $query->where('created_at', '<=', $search['created_to']);
        }

        // TODO:

        // TODO

        return parent::searchOn($query, $search);
    }

    public function createWithAction($action, $actedBy, $payload = [])
    {
        return $this->createWithAttributes([
            'user_id' => $this->retrieveId($actedBy),
            'device_id' => CurrentDevice::getId(),
            'client' => CurrentScreen::getClient(),
            'screen' => CurrentScreen::getName(),
            'action' => $action,
            'screens_array_value' => CurrentScreen::getAsStack(),
            'payload_array_value' => $payload,
        ]);
    }
}
