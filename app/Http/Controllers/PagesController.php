<?php

namespace App\Http\Controllers;

class PagesController extends Controller
{
    public function tables()
    {
        return view('pages.tables');
    }

    public function charts()
    {
        return view('pages.charts');
    }
}
