<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Log;
use App\User;

class Helper
{
    public static function saveLog($user_id, $action, $item_id, $item_name, $menu)
    {
        $log = new Log();
        $log->user_id = $user_id;
        $log->action = $action;
        $log->item_id = $item_id;
        $log->item_name = $item_name;
        $log->menu = $menu;
        $log->save();
    }

    public static function get_permissions() {
        $id = Auth::user()->id;
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = array();
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        return $user_permissions;
    }
}