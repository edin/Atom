<?php

namespace App\Controllers;

use App\Models\UserRepository;
use Atom\View\ViewInfo;

final class HomeController extends Controller
{
    public $Database;
    public $UserRepository;
    public $View;
    public $Response;
    public $Request;
    public $Container;

    public final function index($id = 0, UserRepository $repository, \App\Application $app)
    {
        // return ["Hello"];

        $items = $repository->findAll();

        return new ViewInfo('home/index', [
            'items' => $items
        ]);
    }

    public final function json(UserRepository $repository)
    {
        $items = $repository->findAll();
        return $items;
    }

    public final function item() {
        $item = (object)[
            "title" => "Item"
        ];
        return new ViewInfo('home/item', ['item' => $item]);
    }

    public final function onGet() {
        return "Handled GET";
    }

    public final function onPost() {
        return "Handled POST";
    }

    public final function onPut() {
        return "Handled PUT";
    }

    public final function onPatch() {
        return "Handled PATCH";
    }
}