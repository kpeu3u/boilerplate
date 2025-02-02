<?php

namespace agungsugiarto\boilerplate\Entities;

use CodeIgniter\Entity\Entity;
use Config\Database;

/**
 * Class MenuEntity.
 */
class MenuEntity extends Entity
{
    /**
     * Define properties that are automatically converted to Time instances.
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * Array of field names and the type of value to cast them as
     * when they are accessed.
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Activate menu.
     */
    public function activate(): MenuEntity
    {
        $this->attributes['active'] = 1;

        return $this;
    }

    /**
     * Unactivate menu.
     */
    public function deactivate(): MenuEntity
    {
        $this->attributes['active'] = 0;

        return $this;
    }

    /**
     * Checks to see if a menu is active.
     */
    public function isActivated(): bool
    {
        return isset($this->attributes['active']) && $this->attributes['active'] === true;
    }

    /**
     * Check to see max value from fields sequence.
     */
    public function sequence(): int
    {
        $db      = Database::connect();
        $builder = $db->table('Menu');
        $builder->selectMax('sequence');
        $query = $builder->get();

        return $query->getRow()->sequence;
    }
}
