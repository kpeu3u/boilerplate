<?php

namespace agungsugiarto\boilerplate\Models;

use CodeIgniter\Database\BaseBuilder;
use Myth\Auth\Models\GroupModel as BaseModel;

/**
 * Class Group.
 */
class GroupModel extends BaseModel
{
    public const ORDERABLE = [
        1 => 'name',
        2 => 'description',
    ];

    /**
     * Get resource data.
     */
    public function getResource(string $search = ''): BaseBuilder
    {
        $builder = $this->builder()
            ->select('id, name, description');

        return empty($search)
            ? $builder
            : $builder->groupStart()
                ->like('name', $search)
                ->orLike('description', $search)
                ->groupEnd();
    }

    /**
     * Returns an array of all groups that a user is a member of.
     */
    public function getAllGroupsForUser(int $userId): array
    {
        $groups = $this->builder()
            ->join('auth_groups_users', 'auth_groups_users.group_id = auth_groups.id', 'left')
            ->where('user_id', $userId)
            ->get()->getResultObject();

        $found = [];

        foreach ($groups as $row) {
            $found[$row->id] = strtolower($row->name);
        }

        return $found;
    }
}
