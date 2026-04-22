<?php

namespace App\Http\Controllers;

class PanelController extends Controller
{
    public function userHome()
    {
        return view('user.home', [
            'title' => 'User Panel',
            'moduleCount' => count(config('admin_panel.modules', [])),
        ]);
    }

    public function dashboard()
    {
        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'activeMenu' => 'dashboard',
            'panel' => config('admin_panel'),
        ]);
    }

    public function module(string $module)
    {
        $moduleConfig = config("admin_panel.modules.$module");

        abort_if(!$moduleConfig, 404);

        if (! empty($moduleConfig['crud_route'])) {
            return redirect()->route($moduleConfig['crud_route']);
        }

        return view('admin.module', [
            'title' => $moduleConfig['title'],
            'activeMenu' => $module,
            'moduleKey' => $module,
            'module' => $moduleConfig,
        ]);
    }
}
