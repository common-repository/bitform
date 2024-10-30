<?php

namespace BitForm\Common;

class Permission
{

    public static function getAllCapabilities()
    {
        return ['bf_all'];
    }

    public static function install()
    {
        $role = get_role('administrator');
        if ($role instanceof \WP_Role) {
            foreach (self::getAllCapabilities() as $cap) {
                $role->add_cap($cap);
            }
        }
        $user = wp_get_current_user();
        if ($user instanceof \WP_User) {
            // Refresh current user capabilities
            $user->get_role_caps();
        }
    }

    public static function uninstall()
    {
        $caps = self::getAllCapabilities();
        $roles = wp_roles()->get_names();
        foreach ($roles as $k => $v) {
            $role = get_role($k);
            if (!$role instanceof \WP_Role) {
                continue;
            }
            foreach ($caps as $cap) {
                if ($role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    public static function hasAllCapabilities()
    {
        return current_user_can('bf_all');
    }
}
