<?php

namespace App\Controllers;

use Atom\ViewInfo;
use App\Models\UserRepository;

class HomeController extends Controller
{
    //Public fields should be resolved from container
    public $Database;
    public $UserRepository;
    public $Application;
    public $View;
    public $Response;
    public $Request;
    public $Container;

    //Should be resolved from container
    // - primitive types from route params
    public function index($id = 0, UserRepository $repository)
    {
        $items = $repository->findAll();

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

    public function onGet() {
        return "Handled GET";
    }

    public function onPost() {
        return "Handled POST";
    }

    public function onPut() {
        return "Handled PUT";
    }

    public function onPatch() {
        return "Handled PATCH";
    }
}