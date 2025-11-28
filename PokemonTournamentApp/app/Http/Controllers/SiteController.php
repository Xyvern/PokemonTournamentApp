<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function cards()
    {
        $cards = Card::all();
        return view('cards.index', ['cards' => $cards]);
    }

    public function cardDetail($id)
    {
        $card = Card::where('api_id', $id)->firstOrFail();
        return view('cards.detail', ['card' => $card]);
    }
}
