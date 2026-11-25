<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Models\UserLog;

class SettingController extends Controller
{
    public function edit()
    {
        return view('pages.admin.settings', [
            'orderEmail' => Setting::getValue(Setting::ORDER_EMAIL),
            'contactEmail' => Setting::getValue(Setting::CONTACT_EMAIL),
            'contactPhone' => Setting::getValue(Setting::CONTACT_PHONE),
            'instagramUrl' => Setting::getValue(Setting::INSTAGRAM_URL),
        ]);
    }

    public function update(SettingRequest $request)
    {
        Setting::setValue(Setting::ORDER_EMAIL, $request->order_email);
        Setting::setValue(Setting::CONTACT_EMAIL, $request->contact_email);
        Setting::setValue(Setting::CONTACT_PHONE, $request->contact_phone);
        Setting::setValue(Setting::INSTAGRAM_URL, $request->instagram_url);

        UserLog::record('settings.updated', 'Settings saved (order email ' . $request->order_email
            . ', contact email ' . ($request->contact_email ?: 'same as order email')
            . ', phone ' . ($request->contact_phone ?: 'none')
            . ', Instagram ' . ($request->instagram_url ?: 'none') . ')');

        return back()->with('success', 'Settings saved.');
    }
}
