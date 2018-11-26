<?php

namespace App\Controllers;

use Atom\ViewInfo;

class HomeController extends Controller
{
    public function index() {
        $items = [];
        foreach(\range(1, 15) as $index) {
            $items[] = (object)[
                "title" => "Item $index"
            ];
        }

        return new ViewInfo('home/index', [
            'items' => $items
        ]);
    }

    public function item() {

        $item = (object)[
            "title" => "Item"
        ];

        return new ViewInfo('home/item', [
            'item' => $item
        ]);
    }
}