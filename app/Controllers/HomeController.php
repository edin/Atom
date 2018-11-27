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

    public final function onGet(UserRepository $repository) {
        return $repository->findAll();
    }

    public final function onPost() {
        return ["result" => "Executed onPost method."];
    }

    public final function onPut($id = 0) {
        return ["result" => "Executed onPut method.", "id" => $id];
    }

    public final function onPatch() {
        return ["result" => "Executed onPatch method."];
    }

    public final function onDelete() {
        return ["result" => "Executed onDelete method."];
    }

    public final function onOptions() {
        return ["result" => "Executed onOptions method."];
    }

    public final function onHead() {
        return [];
    }
}